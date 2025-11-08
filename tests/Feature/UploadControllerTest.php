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
    public function it_upload_pending_status_created()
    {
        Queue::fake();
        $file = UploadedFile::fake()->createWithContent('file.csv', 'data');
        $res = $this->postJson('/api/uploads', ['file' => $file]);

        $res->assertStatus(201)
            ->assertJsonFragment(['status' => UploadStatus::PENDING]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_upload_progress_and_status_flow()
    {
        $upload = Upload::factory()->create(['status' => UploadStatus::PENDING]);
        $upload->update(['status' => UploadStatus::PROCESSING]);
        $this->assertEquals(UploadStatus::PROCESSING, $upload->fresh()->status);

        $upload->update(['status' => UploadStatus::COMPLETED]);
        $this->assertEquals(UploadStatus::COMPLETED, $upload->fresh()->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_failed_status_is_saved()
    {
        $upload = Upload::factory()->create(['status' => UploadStatus::PENDING]);
        $upload->update(['status' => UploadStatus::FAILED]);
        $this->assertEquals(UploadStatus::FAILED, $upload->fresh()->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_uploads_index_returns_transformed_items()
    {
        Upload::factory()->create(['file_name' => 'a.csv', 'status' => UploadStatus::PENDING]);
        Upload::factory()->create(['file_name' => 'b.csv', 'status' => UploadStatus::COMPLETED]);

        $res = $this->getJson('/api/uploads');

        $res->assertOk()
            ->assertJsonStructure([['id','file_name','status','progress','processed_at','created_at','updated_at']])
            ->assertJsonFragment(['file_name' => 'a.csv'])
            ->assertJsonFragment(['file_name' => 'b.csv']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_progress_endpoint_returns_correct_value()
    {
        Redis::set('upload:progress:testid', 55);
        $res = $this->getJson('/api/uploads/testid/progress');
        $res->assertJson(['progress' => 55]);
    }
}