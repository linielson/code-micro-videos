<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Category;
use App\Models\Genre;
use App\Models\Video;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\TestValidations;

class VideoControllerTest extends TestCase
{
    use DatabaseMigrations, TestValidations;

    public function testIndex()
    {
        $video = factory(Video::class)->create();
        $response = $this->get(route('videos.index'));
        $response->assertStatus(200);
    }

    public function testShow()
    {
        $video = factory(Video::class)->create();
        $response = $this->get(route('videos.show', ['video' => $video->id]));
        $response
            ->assertStatus(200)
            ->assertJson($video->toArray());
    }

    public function testStore()
    {
        $response = $this->json('POST', route('videos.store'), $this->sendData());

        $video = Video::find($response->json('id'));
        $response
            ->assertStatus(201)
            ->assertJson($video->toArray());

        $this->assertFalse($response->json('opened'));

        $response = $this->json('POST', route('videos.store'), array_merge($this->sendData(), ['opened' => true]));
        $this->assertTrue($response->json('opened'));

        \Storage::fake();
        $file = UploadedFile::fake()->create('video.mp4', 102400);

        $response = $this->json('POST', route('videos.store'), array_merge($this->sendData(), ['video_file' => $file]));
        $video = Video::find($response->json('id'));
        $response
            ->assertStatus(201)
            ->assertJson($video->toArray());

        \Storage::assertExists("{$response->json('id')}/{$file->hashName()}");
    }

    public function testUpdate()
    {
        $category = factory(Category::class)->create();
        $genre = factory(Genre::class)->create();

        $video = factory(Video::class)->create([
            'year_launched' => 1920,
            'opened' => true,
            'rating' => Video::RATING_LIST[0],
            'duration' => 180,
        ]);
        $video->categories()->sync($category->id);
        $video->genres()->sync($genre->id);
        $video->save();

        $another_category = factory(Category::class)->create();
        $another_genre = factory(Genre::class)->create();
        $updatedData = [
            'title' => 'John Doe',
            'description' => 'Old movie',
            'year_launched' => 1960,
            'rating' => Video::RATING_LIST[2],
            'duration' => 120,
            'opened' => false,
            'categories_id' => [$another_category->id],
            'genres_id' => [$another_genre->id]
        ];
        $response = $this->json('PUT', route('videos.update', ['video' => $video->id]), $updatedData);

        $video = Video::with('categories', 'genres')->find($response->json('id'));

        $this->assertEquals('John Doe', $response->json('title'));
        $this->assertEquals('Old movie', $response->json('description'));
        $this->assertEquals(1960, $response->json('year_launched'));
        $this->assertEquals(Video::RATING_LIST[2], $response->json('rating'));
        $this->assertEquals(120, $response->json('duration'));
        $this->assertEquals(false, $response->json('opened'));

        $response->assertJsonStructure(['created_at', 'updated_at']);
        $this->assertHasCategory($response->json('id'), $another_category->id);
        $this->assertHasGenre($response->json('id'), $another_genre->id);
    }

    public function testDestroy()
    {
        $video = factory(Video::class)->create();
        $response = $this->json('DELETE', route('videos.destroy', ['video' => $video->id]));

        $response->assertStatus(204);
        $this->assertNull(Video::find($video->id));
        $this->assertNotNull(Video::withTrashed()->find($video->id));
    }

    public function testInvalidationDataPOST()
    {
        $this->assertValidations('POST', route('videos.store'));
        $this->assertValidationsVideoFile('POST', route('videos.store'));
    }

    public function testInvalidationDataPUT()
    {
        $video = factory(Video::class)->create();
        $this->assertValidations('PUT', route('videos.update', ['video' => $video->id]));
    }

    private function sendData() {
        $category = factory(Category::class)->create();
        $genre = factory(Genre::class)->create();
        return [
            'title' => 'John Doe',
            'description' => 'Old movie',
            'year_launched' => 1960,
            'rating' => Video::RATING_LIST[0],
            'duration' => 180,
            'categories_id' => [$category->id],
            'genres_id' => [$genre->id]
        ];
    }

    private function assertHasCategory($videoId, $categoryId)
    {
        $this->assertDatabaseHas('category_video', [
            'video_id' => $videoId,
            'category_id' => $categoryId
        ]);
    }

    private function assertHasGenre($videoId, $genreId)
    {
        $this->assertDatabaseHas('genre_video', [
            'video_id' => $videoId,
            'genre_id' => $genreId
        ]);
    }

    private function assertInvalidationRequired(TestResponse $response)
    {
        $this->assertInvalidationFields($response,
            ['title', 'description', 'year_launched', 'rating', 'duration', 'categories_id', 'genres_id'], 'required'
        );
    }

    private function assertValidations(string $method, $uri)
    {
        $response = $this->json($method, $uri, []);
        $this->assertInvalidationRequired($response);

        $response = $this->json($method, $uri, [
            'title' => str_repeat('x', 256),
            'opened' => 't',
            'duration' => '10m',
            'year_launched' => '10/10/2010',
            'rating' => 0,
            'categories_id' => 'a',
            'genres_id' => 'a'
        ]);

        $this->assertInvalidationFields($response, ['title'], 'max.string', ['max' => 255]);
        $this->assertInvalidationFields($response, ['opened'], 'boolean');
        $this->assertInvalidationFields($response, ['duration'], 'integer');
        $this->assertInvalidationFields($response, ['year_launched'], 'date_format', ['format' => 'Y']);
        $this->assertInvalidationFields($response, ['rating'], 'in');
        $this->assertInvalidationFields($response, ['categories_id'], 'array');
        $this->assertInvalidationFields($response, ['genres_id'], 'array');

        $response = $this->json($method, $uri, [
            'categories_id' => ['999'],
            'genres_id' => ['999']
        ]);

        $this->assertInvalidationFields($response, ['categories_id'], 'exists');
        $this->assertInvalidationFields($response, ['genres_id'], 'exists');

        $category = factory(Category::class)->create();
        $genre = factory(Genre::class)->create();
        $category->delete();
        $genre->delete();
        $response = $this->json($method, $uri, [
            'categories_id' => [$category->id],
            'genres_id' => [$genre->id]
        ]);

        $this->assertInvalidationFields($response, ['categories_id'], 'exists');
        $this->assertInvalidationFields($response, ['genres_id'], 'exists');
    }

    private function assertValidationsVideoFile(string $method, $uri)
    {
        $file = UploadedFile::fake()->create('video.mp3', 102401);

        $response = $this->json($method, $uri, ['video_file' => $file]);

        $this->assertInvalidationFields($response, ['video_file'], 'max.file', ['max' => 102400]);
        $this->assertInvalidationFields($response, ['video_file'], 'mimetypes', ['values' => 'video/mp4']);
    }
}
