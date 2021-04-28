<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Http\Controllers\Api\VideoController;
use App\Models\Category;
use App\Models\Genre;
use App\Models\Video;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\TestValidations;
use Illuminate\Http\Request;
use Tests\Exceptions\TestException;

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
    }

    public function testRollbackStore()
    {
        $controller = \Mockery::mock(VideoController::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $controller
            ->shouldReceive('validate')
            ->withAnyArgs()
            ->andReturn($this->sendData());

        $controller
            ->shouldReceive('rules')
            ->withAnyArgs()
            ->andReturn([]);

        $controller
            ->shouldReceive('handleRelations')
            ->once()
            ->andThrow(new TestException());

        $hasError = false;
        $request = \Mockery::mock(Request::class);
        try{
            $controller->store($request);
        }catch (TestException $exception) {
            $this->assertCount(0, Video::all());
            $hasError = true;
        }

        $this->assertTrue($hasError);
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

    public function testRollbackUpdate()
    {
        $controller = \Mockery::mock(VideoController::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $category = factory(Category::class)->create();
        $genre = factory(Genre::class)->create();
        $video = factory(Video::class)->create();
        $video->categories()->sync($category->id);
        $video->genres()->sync($genre->id);
        $video->save();

        $controller
            ->shouldReceive('findOrFail')
            ->withAnyArgs()
            ->andReturn($video);

        $controller
            ->shouldReceive('validate')
            ->withAnyArgs()
            ->andReturn([
                'title' => $video->title,
                'description' => $video->description,
                'year_launched' => $video->year_launched,
                'rating' => $video->rating,
                'duration' => $video->duration
            ]);

        $controller
            ->shouldReceive('rules')
            ->withAnyArgs()
            ->andReturn([]);

        $controller
            ->shouldReceive('handleRelations')
            ->once()
            ->andThrow(new TestException());

        $hasError = false;
        $request = \Mockery::mock(Request::class);
        try{
            $controller->store($request, 1);
        }catch (TestException $exception) {
            $this->assertCount(1, Video::all());
            $hasError = true;
        }

        $this->assertTrue($hasError);
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
    }

    public function testInvalidationDataPUT()
    {
        $video = factory(Video::class)->create();
        $this->assertValidations('PUT', route('videos.update', ['video' => $video->id]));
    }

    public function testSyncCategories()
    {
        $categoriesId = factory(Category::class, 3)->create()->pluck('id')->toArray();
        $genre = factory(Genre::class)->create();
        $genre->categories()->sync($categoriesId);
        $genreId = $genre->id;

        $sendData = [
            'title' => 'John Doe',
            'description' => 'Old movie',
            'year_launched' => 1960,
            'rating' => Video::RATING_LIST[0],
            'duration' => 180,
            'genres_id' => [$genreId],
            'categories_id' => [$categoriesId[0]]
         ];

        $response = $this->json('POST', route('videos.store'), $sendData);
        $this->assertDatabaseHas('category_video', [
            'category_id' => $categoriesId[0],
            'video_id' => $response->json('id')
        ]);

        $sendData = [
            'title' => 'John Doe',
            'description' => 'Old movie',
            'year_launched' => 1960,
            'rating' => Video::RATING_LIST[0],
            'duration' => 180,
            'genres_id' => [$genreId],
            'categories_id' => [$categoriesId[1], $categoriesId[2]]
         ];

        $response = $this->json('PUT', route('videos.update', ['video' => $response->json('id')]), $sendData);
        $this->assertDatabaseMissing('category_video', [
            'category_id' => $categoriesId[0],
            'video_id' => $response->json('id')
        ]);

        $this->assertDatabaseHas('category_video', [
            'category_id' => $categoriesId[1],
            'video_id' => $response->json('id')
        ]);

        $this->assertDatabaseHas('category_video', [
            'category_id' => $categoriesId[2],
            'video_id' => $response->json('id')
        ]);
    }

    public function testSyncGenres()
    {
        $genresId = factory(Genre::class, 3)->create()->pluck('id')->toArray();
        $category = factory(Category::class)->create();

        $sendData = [
            'title' => 'John Doe',
            'description' => 'Old movie',
            'year_launched' => 1960,
            'rating' => Video::RATING_LIST[0],
            'duration' => 180,
            'categories_id' => [$category->id],
            'genres_id' => [$genresId[0]]
         ];

        $response = $this->json('POST', route('videos.store'), $sendData);
        $this->assertDatabaseHas('genre_video', [
            'genre_id' => $genresId[0],
            'video_id' => $response->json('id')
        ]);

        $sendData = [
            'title' => 'John Doe',
            'description' => 'Old movie',
            'year_launched' => 1960,
            'rating' => Video::RATING_LIST[0],
            'duration' => 180,
            'categories_id' => [$category->id],
            'genres_id' => [$genresId[1], $genresId[2]]
         ];

        $response = $this->json('PUT', route('videos.update', ['video' => $response->json('id')]), $sendData);
        $this->assertDatabaseMissing('genre_video', [
            'genre_id' => $genresId[0],
            'video_id' => $response->json('id')
        ]);

        $this->assertDatabaseHas('genre_video', [
            'genre_id' => $genresId[1],
            'video_id' => $response->json('id')
        ]);

        $this->assertDatabaseHas('genre_video', [
            'genre_id' => $genresId[2],
            'video_id' => $response->json('id')
        ]);
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
}
