<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;
use App\Contracts\UploadRepositoryContract;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use App\Enums\UploadStatus;
use App\Jobs\ProcessCSV;
use App\Models\Upload;

class UploadRepository implements UploadRepositoryContract
{
    public function __construct(private Upload $model) {}

    public function all(): Collection
    {
        return $this->model
            ->newQuery()
            ->latest()
            ->get();
    }

    public function create(UploadedFile $file): Upload
    {
        return DB::transaction(function () use ($file) {
            $upload = $this->model
            ->create([
                'id' => (string) Str::uuid(),
                'file_name' => $file->getClientOriginalName(),
                'status' => UploadStatus::Pending->value,
            ]);

            $upload->addMedia($file)
                ->usingFileName($file->getClientOriginalName())
                ->toMediaCollection('files');

            ProcessCSV::dispatch($upload);
            
            return $upload->refresh();
        });
    }

    public function updateStatus(Upload $upload, UploadStatus $status): void
    {
        $upload->update(['status' => $status->value]);
    }
}
