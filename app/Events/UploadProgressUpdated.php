<?php
namespace App\Events;

use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\Channel;

class UploadProgressUpdated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(public string $uploadId, public int $progress)
    {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('uploads');
    }
    public function broadcastAs(): string
    {
        return 'UploadProgressUpdated';
    }

    public function broadcastWith(): array
    {
        return ['id' => $this->uploadId, 'progress' => $this->progress];
    }
}
