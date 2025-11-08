<?php

namespace Tests\Unit\Enums;

use App\Enums\UploadStatus;
use Tests\TestCase;

class UploadStatusTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function test_enum_values_match_expected()
    {
        $this->assertEquals('pending', UploadStatus::PENDING->value);
        $this->assertEquals('processing', UploadStatus::PROCESSING->value);
        $this->assertEquals('completed', UploadStatus::COMPLETED->value);
        $this->assertEquals('failed', UploadStatus::FAILED->value);
    }
}
