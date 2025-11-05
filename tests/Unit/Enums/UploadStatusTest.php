<?php

namespace Tests\Unit\Enums;

use App\Enums\UploadStatus;
use Tests\TestCase;

class UploadStatusTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function test_enum_values_match_expected()
    {
        $this->assertEquals('pending', UploadStatus::Pending->value);
        $this->assertEquals('processing', UploadStatus::Processing->value);
        $this->assertEquals('completed', UploadStatus::Completed->value);
        $this->assertEquals('failed', UploadStatus::Failed->value);
    }
}
