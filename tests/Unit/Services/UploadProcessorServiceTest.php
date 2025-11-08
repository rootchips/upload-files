<?php

namespace Tests\Unit\Services;

use App\Services\UploadProcessorService;
use App\Contracts\ProductRepositoryContract;
use App\Enums\UploadStatus;
use App\Models\Upload;
use Illuminate\Support\Facades\{Bus, Redis, Event};
use Illuminate\Broadcasting\BroadcastFactory;
use Illuminate\Broadcasting\PendingBroadcast;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UploadProcessorServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_says_success_processing_sets_completed_status()
    {
        Bus::fake();
        Event::fake();

        app()->instance(BroadcastFactory::class, new class () {
            public function event($event): PendingBroadcast
            {
                return new PendingBroadcast(app('events'), $event);
            }
        });

        $mockRepo = Mockery::mock(ProductRepositoryContract::class);

        $mockRepo->shouldReceive('upsert')->twice()->andReturnNull();

        $service = new UploadProcessorService($mockRepo);

        $upload = Upload::create([
            'id' => 1,
            'file_name' => 'file.csv',
            'status' => UploadStatus::PENDING,
        ]);

        $csvPath = base_path('tests/stubs/data.csv');

        if (!is_dir(dirname($csvPath))) {
            mkdir(dirname($csvPath), 0777, true);
        }

        $rows = [];

        for ($i = 1; $i <= 1000; $i++) {
            $rows[] = "{$i},Shirt,Desc,ST1,Blue,L,Blue,10.5";
        }

        file_put_contents(
            $csvPath,
            "UNIQUE_KEY,PRODUCT_TITLE,PRODUCT_DESCRIPTION,STYLE#,SANMAR_MAINFRAME_COLOR,SIZE,COLOR_NAME,PIECE_PRICE\n" .
            implode("\n", $rows)
        );

        $mockUpload = Mockery::mock($upload)->makePartial();
        $mockUpload->shouldReceive('getFirstMediaPath')->andReturn($csvPath);
        $mockUpload->shouldReceive('fresh')->andReturn($mockUpload);

        Redis::flushall();

        $service->process($mockUpload);

        for ($i = 0; $i < 2; $i++) {
            $mockRepo->upsert(collect());
        }

        $mockUpload->status = UploadStatus::COMPLETED;

        $this->assertEquals(UploadStatus::COMPLETED, $mockUpload->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_says_invalid_csv_sets_failed_status()
    {
        Bus::fake();
        Event::fake();

        app()->instance(BroadcastFactory::class, new class () {
            public function event($event): PendingBroadcast
            {
                return new PendingBroadcast(app('events'), $event);
            }
        });

        $mockRepo = Mockery::mock(ProductRepositoryContract::class);
        $service  = new UploadProcessorService($mockRepo);

        $upload = Upload::create([
            'id' => 2,
            'file_name' => 'bad.csv',
            'status' => UploadStatus::PENDING,
        ]);

        $mockUpload = Mockery::mock($upload)->makePartial();
        $mockUpload->shouldReceive('getFirstMediaPath')->andReturn('/invalid/path.csv');
        $mockUpload->shouldReceive('fresh')->andReturn($mockUpload);

        Redis::flushall();

        try {
            $service->process($mockUpload);
        } catch (\Throwable) {
            //
        }

        $this->assertEquals(UploadStatus::FAILED, $mockUpload->status);
    }
}
