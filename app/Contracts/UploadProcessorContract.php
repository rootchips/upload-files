<?php
namespace App\Contracts;

use App\Models\Upload;

interface UploadProcessorContract
{
    public function process(Upload $upload): void;
}