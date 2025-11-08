<?php

namespace App\Services;

use App\Contracts\{UploadProcessorContract, ProductRepositoryContract};
use App\Events\{UploadStatusUpdated, UploadProgressUpdated};
use Illuminate\Support\Facades\{Bus, Redis};
use App\Helpers\TextNormalizer;
use App\Enums\UploadStatus;
use App\Models\Upload;
use League\Csv\Reader;
use RuntimeException;
use Throwable;

class UploadProcessorService implements UploadProcessorContract
{
    public function __construct(private ProductRepositoryContract $products) {}

    public function process(Upload $upload): void
    {
        $this->setStatus($upload, UploadStatus::Processing);

        try {
            $data = $this->readCsv($upload);
            $this->dispatchBatchJobs($upload, $data);
        } catch (Throwable $e) {
            $this->setStatus($upload, UploadStatus::Failed);
            Redis::del("upload:progress:{$upload->id}");
            broadcast(new UploadStatusUpdated($upload->fresh()));
            throw $e;
        }
    }

    private function readCsv(Upload $upload): array
    {
        $csv = Reader::createFromPath($upload->getFirstMediaPath('files'), 'r');
        $csv->setHeaderOffset(0);

        $headers = array_map(fn ($h) => strtoupper(TextNormalizer::clean($h)), $csv->getHeader());
        if (!in_array('UNIQUE_KEY', $headers, true)) {
            throw new RuntimeException('Missing UNIQUE_KEY column.');
        }

        $data = iterator_to_array($csv->getRecords(), false);
        if (empty($data)) {
            throw new RuntimeException('No data found.');
        }

        return $data;
    }

    private function dispatchBatchJobs(Upload $upload, array $data): void
    {
        $total = count($data);
        $chunks = collect($data)->chunk(500);
        $uploadId = $upload->id;
        $productClass = get_class($this->products);

        $batch = Bus::batch(
            $chunks->map(
                fn ($chunk, $i) =>
                function () use ($productClass, $chunk, $total, $uploadId, $i) {
                    app($productClass)->upsert($chunk);

                    $processed = min(($i + 1) * 500, $total);
                    $progress = (int)(($processed / $total) * 100);
                    Redis::set("upload:progress:{$uploadId}", $progress);

                    if ($progress % 5 === 0 || $progress === 100) {
                        broadcast(new UploadProgressUpdated($uploadId, $progress));
                    }
                }
            )
        )
        ->name("Upload {$upload->id}")
        ->onQueue('upload-sequence')
        ->then(fn () => $this->setStatus($upload, UploadStatus::Completed))
        ->allowFailures(true)
        ->catch(function () use ($upload) {
            $uploadId = $upload->id;
            Redis::set("upload:progress:{$uploadId}", 100);
            broadcast(new UploadProgressUpdated($uploadId, 100));

            $this->setStatus($upload, UploadStatus::Failed);
        })
        ->finally(fn () => Redis::del("upload:progress:{$upload->id}"))
        ->dispatch();

        Redis::set("upload:batch:{$upload->id}", $batch->id);
    }

    private function setStatus(Upload $upload, UploadStatus $status): void
    {
        $upload->update([
            'status' => $status->value,
            'processed_at' => now(),
        ]);

        broadcast(new UploadStatusUpdated($upload));
    }
}
