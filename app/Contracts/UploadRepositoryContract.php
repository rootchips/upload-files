<?php
namespace App\Contracts;

use Illuminate\Http\UploadedFile;
use App\Enums\UploadStatus;
use App\Models\Upload;

interface UploadRepositoryContract
{
    public function all(): array;
    public function create(UploadedFile $file): Upload;
    public function updateStatus(Upload $upload, UploadStatus $status): void;
}