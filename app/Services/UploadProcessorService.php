<?php
namespace App\Services;

use App\Contracts\{UploadProcessorContract, ProductRepositoryContract};
use App\Events\{UploadStatusUpdated, UploadProgressUpdated};
use Illuminate\Support\Facades\Redis;
use App\Enums\UploadStatus;
use App\Models\Upload;

class UploadProcessorService implements UploadProcessorContract
{
    public function __construct(private ProductRepositoryContract $products)
    {
    }

    public function process(Upload $upload): void
    {
        $path = $upload->getFirstMediaPath('files');

        $upload->update(['status' => UploadStatus::Processing->value]);
        broadcast(new UploadStatusUpdated($upload));

        try {
            $handle = fopen($path, 'r');

            if (!$handle) {
                throw new \Exception('Unable to open CSV file.');
            }

            $headers = fgetcsv($handle);

            if (!$headers || !is_array($headers)) {
                $upload->update(['status' => UploadStatus::Failed->value]);
                broadcast(new UploadStatusUpdated($upload));
                fclose($handle);
                return;
            }

            $headers = array_map(fn ($h) => strtoupper($this->norm((string) $h)), $headers);

            if (!in_array('UNIQUE_KEY', $headers, true)) {
                $upload->update(['status' => UploadStatus::Failed->value]);
                broadcast(new UploadStatusUpdated($upload));
                fclose($handle);
                return;
            }

            $total = max(0, $this->countLines($path) - 1);
            $processed = 0;
            $successes = 0;
            $malformed = 0;
            $upsertErrors = 0;

            while (($row = fgetcsv($handle)) !== false) {
                $processed++;

                $row = array_map(fn ($v) => $this->norm((string) $v), $row);

                if (count($row) !== count($headers)) {
                    $malformed++;
                    
                    $this->updateProgress($upload->id, $processed, $total);
                    continue;
                }

                $data = array_combine($headers, $row);

                if ($data === false || ($data['UNIQUE_KEY'] ?? '') === '') {
                    $malformed++;

                    $this->updateProgress($upload->id, $processed, $total);
                    continue;
                }

                try {
                    $this->products->upsert($data);
                    $successes++;
                } catch (\Throwable $e) {
                    $upsertErrors++;
                }

                $this->updateProgress($upload->id, $processed, $total);
            }

            fclose($handle);

            if ($successes === 0) {
                $upload->update([
                    'status'       => UploadStatus::Failed->value,
                    'processed_at' => now(),
                ]);
            } else {
                $errorRate = $processed > 0 ? (($malformed + $upsertErrors) / $processed) : 0.0;

                if ($errorRate >= 0.90) {
                    $upload->update([
                        'status'       => UploadStatus::Failed->value,
                        'processed_at' => now(),
                    ]);
                } else {
                    $upload->update([
                        'status'       => UploadStatus::Completed->value,
                        'processed_at' => now(),
                    ]);
                }
            }

        } catch (\Throwable $e) {
            $upload->update(['status' => UploadStatus::Failed->value]);
            throw $e;
        } finally {
            Redis::del("upload:progress:{$upload->id}");
            broadcast(new UploadStatusUpdated($upload));
        }
    }

    private function updateProgress(int|string $uploadId, int $processed, int $total): void
    {
        $progress = $total > 0 ? (int) round(($processed / $total) * 100) : 100;
        $progress = max(0, min(100, $progress));

        Redis::set("upload:progress:{$uploadId}", $progress);

        if ($progress % 5 === 0 || $progress === 100) {
            broadcast(new UploadProgressUpdated($uploadId, $progress));
        }
    }

    private function countLines(string $path): int
    {
        $count  = 0;
        $handle = fopen($path, 'r');

        while (!feof($handle)) {
            fgets($handle);
            $count++;
        }

        fclose($handle);
        return $count;
    }

    private function norm(string $s): string
    {
        $enc = mb_detect_encoding($s, mb_detect_order(), true) ?: 'UTF-8';
        $s   = iconv($enc, 'UTF-8//IGNORE', $s) ?: '';

        $s = preg_replace('/[\x{FEFF}\x{200B}\x{200E}\x{200F}\x{00A0}]+/u', '', $s);

        $s = preg_replace('/[^\P{C}\t\r\n]+/u', '', $s);

        return trim($s);
    }
}
