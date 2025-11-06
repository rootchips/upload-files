<?php

namespace App\Repositories;

use App\Contracts\UploadRepositoryContract;
use App\Enums\UploadStatus;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use App\Jobs\ProcessCSV;
use App\Models\Upload;
use Illuminate\Database\Eloquent\Collection;

class UploadRepository implements UploadRepositoryContract
{
    public function __construct(private Upload $model) {}

    public function all(): Collection
    {
        return $this->model->newQuery()->latest()->get();
    }

    public function create(UploadedFile $file): Upload
    {
        return DB::transaction(function () use ($file) {
            $upload = Upload::create([
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
