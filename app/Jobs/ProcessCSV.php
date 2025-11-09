<?php

namespace App\Jobs;

use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Contracts\UploadProcessorContract;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Queueable;
use App\Models\Upload;

class ProcessCSV implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Upload $upload) {}

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->upload->id)];
    }

    public function handle(UploadProcessorContract $processor): void
    {
        $processor->process($this->upload);
    }
}
