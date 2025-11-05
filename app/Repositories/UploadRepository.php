<?php

namespace App\Repositories;

use App\Contracts\UploadRepositoryContract;
use App\Enums\UploadStatus;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use App\Jobs\ProcessCSV;
use App\Models\Upload;

class UploadRepository implements UploadRepositoryContract
{
    public function all(): array
    {
        return Upload::query()
            ->latest()
            ->get()
            ->toArray();
    }

    public function create(UploadedFile $file): Upload
    {
        return DB::transaction(function () use ($file) {
            $upload = Upload::create([
                'id'        => (string) Str::uuid(),
                'file_name' => $file->getClientOriginalName(),
                'status'    => UploadStatus::Pending->value,
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
