<?php
namespace App\Events;

use App\Models\Upload;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UploadStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(public Upload $upload)
    {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('uploads');
    }
    public function broadcastAs(): string
    {
        return 'UploadStatusUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->upload->id,
            'file_name' => $this->upload->file_name,
            'status' => $this->upload->status,
        ];
    }
}
