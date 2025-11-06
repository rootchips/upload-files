<?php
namespace App\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use App\Enums\UploadStatus;
use App\Models\Upload;

interface UploadRepositoryContract
{
    public function all(): Collection;
    public function create(UploadedFile $file): Upload;
    public function updateStatus(Upload $upload, UploadStatus $status): void;
}