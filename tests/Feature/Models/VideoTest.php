<?php

namespace Tests\Feature\Models;

use App\Models\Video;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Ramsey\Uuid\Uuid as UuidValidator;

class VideoTest extends TestCase
{
    use DatabaseMigrations;

    public function testList()
    {
        factory(Video::class)->create();
        $videos = Video::all();
        $this->assertCount(1, $videos);
        $this->assertEqualsCanonicalizing(
            [
                'id',
                'title',
                'description',
                'year_launched',
                'opened',
                'rating',
                'duration',
                'created_at',
                'updated_at',
                'deleted_at'
            ],
            array_keys($videos->first()->getAttributes())
        );
    }

    public function testCreate()
    {
        $video = Video::create([
            'title' => 'Some title',
            'description' => 'Some description',
            'year_launched' => 2020,
            'rating' => Video::RATING_LIST[0],
            'duration' => 60
        ]);
        $video->refresh();

        $this->assertTrue(UuidValidator::isValid($video->id));
        $this->assertFalse($video->opened);
        $this->assertEquals('Some title', $video->title);
        $this->assertEquals('Some description', $video->description);
        $this->assertEquals(2020, $video->year_launched);
        $this->assertEquals(60, $video->duration);

        $video = Video::create([
            'title' => 'Some title',
            'description' => 'Some description',
            'year_launched' => 2020,
            'rating' => Video::RATING_LIST[0],
            'duration' => 60,
            'opened' => true
        ]);
        $video->refresh();
        $this->assertTrue($video->opened);
    }

    public function testUpdate()
    {
        $video = factory(Video::class)->create([
            'title' => 'Some title',
            'description' => 'Some description',
            'year_launched' => 2020,
            'rating' => Video::RATING_LIST[0],
            'duration' => 60,
            'opened' => true
        ]);

        $data = [
            'title' => 'Some title changed',
            'description' => 'Some description changed',
            'year_launched' => 2021,
            'rating' => Video::RATING_LIST[1],
            'duration' => 80,
            'opened' => false
        ];
        $video->update($data);

        foreach($data as $key => $value) {
            $this->assertEquals($value, $video->{$key});
        }
    }

    public function testDelete()
    {
        $video = factory(Video::class)->create();
        $video->delete();
        $this->assertNull(Video::find($video->id));

        $video->restore();
        $this->assertNotNull(Video::find($video->id));
    }
}
