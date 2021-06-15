<?php

namespace Tests\Unit;

use App\Models\Video;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\Uuid;
use App\Models\Traits\UploadFiles;

class VideoUnitTest extends TestCase
{
    public function testIfUseTraits()
    {
        $traits = [SoftDeletes::class, Uuid::class, UploadFiles::class];
        $videoTraits = array_keys(class_uses(Video::class));
        $this->assertEquals($traits, $videoTraits);
    }

    public function testIncrementing()
    {
        $video = new Video();
        $this->assertFalse($video->getIncrementing());
    }

    public function testKeyType()
    {
        $video = new Video();
        $this->assertEquals('string', $video->getKeyType());
    }

    public function testDates()
    {
        $video = new Video();
        $dates = ['deleted_at', 'created_at', 'updated_at'];
        $this->assertEqualsCanonicalizing($dates, $video->getDates());
        $this->assertCount(count($dates), $video->getDates());
    }

    public function testFillable()
    {
        $video = new Video();
        $this->assertEquals(
            ['title', 'description', 'year_launched', 'opened', 'rating', 'duration' , 'video_file', 'thumb_file'],
            $video->getFillable()
        );
    }

    public function testCasts()
    {
        $video = new Video();
        $this->assertEquals(
            ['opened' => 'boolean', 'year_launched' => 'integer', 'duration' => 'integer'],
            $video->getCasts()
        );
    }
}
