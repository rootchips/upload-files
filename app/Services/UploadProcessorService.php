<?php

namespace App\Services;

use App\Contracts\{UploadProcessorContract, ProductRepositoryContract};
use App\Events\{UploadStatusUpdated, UploadProgressUpdated};
use Illuminate\Support\Facades\Redis;
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
            $records = $this->readCsv($upload);
            $this->processData($upload, $records);
            $this->setStatus($upload, UploadStatus::Completed);

        } catch (Throwable $e) {
            $this->setStatus($upload, UploadStatus::Failed);
            throw $e;

        } finally {
            Redis::del("upload:progress:{$upload->id}");
            broadcast(new UploadStatusUpdated($upload->fresh()));

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

        $records = iterator_to_array($csv->getRecords(), false);

        if (empty($records)) {
            throw new RuntimeException('No records found.');
        }

        return ['headers' => $headers, 'records' => $records];
    }

    private function processData(Upload $upload, array $data): void
    {
        ['headers' => $headers, 'records' => $records] = $data;

        $total = count($records);
        $batch = [];

        foreach ($records as $i => $row) {
            $data = array_change_key_case($row, CASE_UPPER);
            $data = array_map(fn ($v) => TextNormalizer::clean((string)$v), $data);

            if (count($data) !== count($headers) || empty($data['UNIQUE_KEY'])) {
                throw new RuntimeException("Malformed row at line " . ($i + 2));
            }

            $batch[] = $data;

            if (count($batch) >= 500) {
                $this->products->upsert($batch);
                $batch = [];
            }

            $this->updateProgress($upload->id, $i + 1, $total);
        }

        if (!empty($batch)) {
            $this->products->upsert($batch);
        }
    }

    private function updateProgress(string $uploadId, int $processed, int $total): void
    {
        $progress = (int)(($processed / $total) * 100);
        if ($progress % 5 !== 0 && $progress !== 100) {
            return;
        }

        Redis::set("upload:progress:{$uploadId}", $progress);
        broadcast(new UploadProgressUpdated($uploadId, $progress));
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
