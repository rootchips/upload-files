<?php

namespace Tests\Feature;

use App\Enums\UploadStatus;
use App\Models\Upload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UploadControllerTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_upload_pending_status_created()
    {
        Queue::fake();

        $file = UploadedFile::fake()->createWithContent('file.csv', 'data');

        $res = $this->postJson('/api/uploads', ['file' => $file]);

        $res->assertStatus(200)->assertJsonFragment(['status' => UploadStatus::Pending->value]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_upload_progress_and_status_flow()
    {
        $upload = Upload::factory()->create(['status' => UploadStatus::Pending->value]);

        $upload->update(['status' => UploadStatus::Processing->value]);

        $upload->refresh();

        $this->assertEquals(UploadStatus::Processing->value, $upload->status);

        $upload->update(['status' => UploadStatus::Completed->value]);

        $this->assertEquals(UploadStatus::Completed->value, $upload->fresh()->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_failed_status_is_saved()
    {
        $upload = Upload::factory()->create(['status' => UploadStatus::Pending->value]);

        $upload->update(['status' => UploadStatus::Failed->value]);

        $this->assertEquals('failed', $upload->fresh()->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_progress_endpoint_returns_correct_value()
    {
        Redis::set('upload:progress:testid', 55);

        $res = $this->getJson('/api/uploads/testid/progress');

        $res->assertJson(['progress' => 55]);
    }
}
