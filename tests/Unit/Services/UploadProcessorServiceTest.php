<?php

namespace Tests\Unit\Services;

use App\Services\UploadProcessorService;
use App\Contracts\ProductRepositoryContract;
use App\Enums\UploadStatus;
use App\Models\Upload;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Event;
use Illuminate\Broadcasting\BroadcastFactory;
use Illuminate\Broadcasting\PendingBroadcast;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class UploadProcessorServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_success_processing_sets_completed_status()
    {
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

        $upload = Mockery::mock(Upload::class)->makePartial();
        $upload->id = 1;
        $upload->file_name = 'file.csv';
        $upload->status = UploadStatus::Pending->value;

        $csvPath = base_path('tests/stubs/data.csv');
        if (!is_dir(dirname($csvPath))) {
            mkdir(dirname($csvPath), 0777, true);
        }
        file_put_contents(
            $csvPath,
            "UNIQUE_KEY,PRODUCT_TITLE,PRODUCT_DESCRIPTION,STYLE#,SANMAR_MAINFRAME_COLOR,SIZE,COLOR_NAME,PIECE_PRICE\n" .
            "1,Shirt,Desc,ST1,Blue,L,Blue,10.5\n" .
            "2,Hat,Desc,ST2,Red,M,Red,5.0\n"
        );

        $upload->shouldReceive('getFirstMediaPath')->andReturn($csvPath);
        $upload->shouldReceive('update')->andReturnUsing(function (array $attrs) use ($upload) {
            unset($attrs['id']);
            foreach ($attrs as $k => $v) {
                $upload->{$k} = $v;
            }
            return true;
        });

        Redis::flushall();
        $service->process($upload);

        $this->assertEquals('completed', $upload->status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_invalid_csv_sets_failed_status()
    {
        Event::fake();
        app()->instance(BroadcastFactory::class, new class () {
            public function event($event): PendingBroadcast
            {
                return new PendingBroadcast(app('events'), $event);
            }
        });

        $mockRepo = Mockery::mock(ProductRepositoryContract::class);
        $service  = new UploadProcessorService($mockRepo);

        $upload = Mockery::mock(Upload::class)->makePartial();
        $upload->id = 2;
        $upload->file_name = 'bad.csv';
        $upload->status = UploadStatus::Pending->value;

        $upload->shouldReceive('getFirstMediaPath')->andReturn('/invalid/path.csv');
        $upload->shouldReceive('update')->andReturnUsing(function (array $attrs) use ($upload) {
            foreach ($attrs as $k => $v) {
                $upload->{$k} = $v;
            }
            return true;
        });

        try {
            $service->process($upload);
            $this->fail('Expected exception on invalid file path');
        } catch (\Throwable $e) {
            //
        }

        $this->assertEquals('failed', $upload->status);
    }
}
