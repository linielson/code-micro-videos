<?php

namespace Tests\Feature\Models;

use App\Models\Video;
use App\Models\Category;
use App\Models\Genre;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use Ramsey\Uuid\Uuid as UuidValidator;
use Tests\Exceptions\TestException;

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
                'video_file',
                'thumb_file',
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

    public function testCreateWithRelations()
    {
        $category = factory(Category::class)->create();
        $genre = factory(Genre::class)->create();
        $video = Video::create([
            'title' => 'Some title',
            'description' => 'Some description',
            'year_launched' => 2020,
            'rating' => Video::RATING_LIST[0],
            'duration' => 60,
            'opened' => true,
            'categories_id' => [$category->id],
            'genres_id' => [$genre->id],
        ]);

        $this->assertHasCategory($video->id, $category->id);
        $this->assertHasGenre($video->id, $genre->id);
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

    public function testUpdateWithRelations()
    {
        $category = factory(Category::class)->create();
        $genre = factory(Genre::class)->create();
        $video = factory(Video::class)->create();
        $data = [
            'title' => 'Some title changed',
            'description' => 'Some description changed',
            'year_launched' => 2021,
            'rating' => Video::RATING_LIST[1],
            'duration' => 80,
            'opened' => true,
            'categories_id' => [$category->id],
            'genres_id' => [$genre->id],
        ];
        $video->update($data);

        $this->assertHasCategory($video->id, $category->id);
        $this->assertHasGenre($video->id, $genre->id);
    }

    public function testDelete()
    {
        $video = factory(Video::class)->create();
        $video->delete();
        $this->assertNull(Video::find($video->id));

        $video->restore();
        $this->assertNotNull(Video::find($video->id));
    }

    public function testRollbackCreate()
    {
        $hasError = false;
        try{
            Video::Create([
                'title' => 'John Doe',
                'description' => 'Old movie',
                'year_launched' => 1960,
                'rating' => Video::RATING_LIST[0],
                'duration' => 180,
                'categories_id' => [0,1,2]
            ]);
            }catch (QueryException $exception) {
            $this->assertCount(0, Video::all());
            $hasError = true;
        }

        $this->assertTrue($hasError);
    }

    public function testRollbackUpdate()
    {
        $hasError = false;
        $video = factory(Video::class)->create();
        $oldTitle = $video->title;
        try{
            $video->update([
                'title' => 'John Doe',
                'description' => 'Old movie',
                'year_launched' => 1960,
                'rating' => Video::RATING_LIST[0],
                'duration' => 180,
                'categories_id' => [0,1,2]
            ]);
        }catch (QueryException $exception) {
            $this->assertDatabaseHas('videos', ['title' => $oldTitle]);
            $hasError = true;
        }

        $this->assertTrue($hasError);
    }

    public function testeHandleRelations()
    {
        $video = factory(Video::class)->create();
        Video::handleRelations($video, []);
        $this->assertCount(0, $video->categories);
        $this->assertCount(0, $video->genres);

        $category = factory(Category::class)->create();
        Video::handleRelations($video, ['categories_id' => [$category->id]]);
        $video->refresh();
        $this->assertCount(1, $video->categories);

        $genre = factory(Genre::class)->create();
        Video::handleRelations($video, ['genres_id' => [$genre->id]]);
        $video->refresh();
        $this->assertCount(1, $video->genres);

        $video->categories()->delete();
        $video->genres()->delete();

        Video::handleRelations($video, [
            'categories_id' => [$category->id],
            'genres_id' => [$genre->id]
        ]);
        $video->refresh();
        $this->assertCount(1, $video->categories);
        $this->assertCount(1, $video->genres);
    }

    public function testSyncCategories()
    {
        $categoriesId = factory(Category::class, 3)->create()->pluck('id')->toArray();
        $video = factory(Video::class)->create();
        Video::handleRelations($video, ['categories_id' => [$categoriesId[0]]]);
        $this->assertDatabaseHas('category_video', [
            'category_id' => $categoriesId[0],
            'video_id' => $video->id
        ]);

        Video::handleRelations($video, [
            'categories_id' => [$categoriesId[1], $categoriesId[2]]
        ]);

        $this->assertDatabaseMissing('category_video', [
            'category_id' => $categoriesId[0],
            'video_id' => $video->id
        ]);

        $this->assertDatabaseHas('category_video', [
            'category_id' => $categoriesId[1],
            'video_id' => $video->id
        ]);

        $this->assertDatabaseHas('category_video', [
            'category_id' => $categoriesId[2],
            'video_id' => $video->id
        ]);
    }

    public function testSyncGenres()
    {
        $genresId = factory(Genre::class, 3)->create()->pluck('id')->toArray();
        $video = factory(Video::class)->create();
        Video::handleRelations($video, ['genres_id' => [$genresId[0]]]);

        $this->assertDatabaseHas('genre_video', [
            'genre_id' => $genresId[0],
            'video_id' => $video->id
        ]);

        Video::handleRelations($video, [
            'genres_id' => [$genresId[1], $genresId[2]]
        ]);

        $this->assertDatabaseMissing('genre_video', [
            'genre_id' => $genresId[0],
            'video_id' => $video->id
        ]);

        $this->assertDatabaseHas('genre_video', [
            'genre_id' => $genresId[1],
            'video_id' => $video->id
        ]);

        $this->assertDatabaseHas('genre_video', [
            'genre_id' => $genresId[2],
            'video_id' => $video->id
        ]);
    }

    public function testCreateWithFiles()
    {
        \Storage::fake();
        $thumb_file = UploadedFile::fake()->image('thumb.jpg');
        $video_file = UploadedFile::fake()->image('video.mp4');
        $video = Video::create([
            'title' => 'Some title',
            'description' => 'Some description',
            'year_launched' => 2020,
            'rating' => Video::RATING_LIST[0],
            'duration' => 60,
            'thumb_file' => $thumb_file,
            'video_file' => $video_file,
        ]);
        $video->refresh();

        $this->assertDatabaseHas('videos', ['thumb_file' => $thumb_file->hashName(), 'video_file' => $video_file->hashName()]);

        \Storage::assertExists("{$video->id}/{$video->thumb_file}");
        \Storage::assertExists("{$video->id}/{$video->video_file}");
    }

    public function testCreateIfRollbackFiles()
    {
        \Storage::fake();
        \Event::listen(TransactionCommitted::class, function () {
            throw new TestException();
        });
        $hashError = false;

        try {
            $video = Video::create([
                'title' => 'Some title',
                'description' => 'Some description',
                'year_launched' => 2020,
                'rating' => Video::RATING_LIST[0],
                'duration' => 60,
                'thumb_file' => UploadedFile::fake()->image('thumb.jpg'),
                'video_file' => UploadedFile::fake()->image('thumb.mp4'),
            ]);
        } catch (TestException $e) {
            $this->assertCount(0, \Storage::allFiles());
            $hashError = true;
        }

        $this->assertTrue($hashError);
    }

    public function testUpdateWithFiles()
    {
        \Storage::fake();
        $video = factory(Video::class)->create();
        $videoFile = UploadedFile::fake()->create('video.mp4');
        $thumbFile = UploadedFile::fake()->image('thumb.jpg');

        $video->update([
            'duration' => 90,
            'video_file' => $videoFile,
            'thumb_file' => $thumbFile
        ]);
        \Storage::assertExists("{$video->id}/{$video->video_file}");
        \Storage::assertExists("{$video->id}/{$video->thumb_file}");

        $newVideoFile = UploadedFile::fake()->image('video.mp4');
        $video->update(['duration' => 90, 'video_file' => $newVideoFile]);
        \Storage::assertExists("{$video->id}/{$newVideoFile->hashName()}");
        \Storage::assertExists("{$video->id}/{$thumbFile->hashName()}");

        \Storage::assertMissing("{$video->id}/{$videoFile->hashName()}");
    }

    public function testUpdateIfRollbackFiles()
    {
        \Storage::fake();
        $video = factory(Video::class)->create();
        \Event::listen(TransactionCommitted::class, function () {
            throw new TestException();
        });
        $hasError = false;

        try {
            $video->update([
                'title' => 'Some title x',
                'video_file' => UploadedFile::fake()->create('video.mp4'),
                'thumb_file' => UploadedFile::fake()->image('thumb.jpg')
            ]);
        } catch (TestException $e) {
            $this->assertCount(0, \Storage::allFiles());
            $hasError = true;
        }

        $this->assertTrue($hasError);
    }

    protected function assertHasCategory($videoId, $categoryId)
    {
        $this->assertDatabaseHas('category_video', [
            'video_id' => $videoId,
            'category_id' => $categoryId
        ]);
    }

    protected function assertHasGenre($videoId, $genreId)
    {
        $this->assertDatabaseHas('genre_video', [
            'video_id' => $videoId,
            'genre_id' => $genreId
        ]);
    }
}
