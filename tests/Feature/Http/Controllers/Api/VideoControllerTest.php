<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Category;
use App\Models\Genre;
use App\Models\Video;
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
        $category = factory(Category::class)->create();
        $genre = factory(Genre::class)->create();

        $response = $this->json('POST', route('videos.store'), [
            'title' => 'John Doe',
            'description' => 'Old movie',
            'year_launched' => 1960,
            'rating' => Video::RATING_LIST[0],
            'duration' => 180,
            'categories_id' => [$category->id],
            'genres_id' => [$genre->id]
        ]);

        $video = Video::find($response->json('id'));
        $response
            ->assertStatus(201)
            ->assertJson($video->toArray());

        $this->assertFalse($response->json('opened'));

        $response = $this->json('POST', route('videos.store'), [
            'title' => 'John Doe',
            'description' => 'New movie',
            'year_launched' => 2021,
            'rating' => Video::RATING_LIST[0],
            'duration' => 180,
            'opened' => true,
            'categories_id' => [$category->id],
            'genres_id' => [$genre->id]
        ]);
        $this->assertTrue($response->json('opened'));
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
            'categories_id' => [$category->id],
            'genres_id' => [$genre->id]
        ]);

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

        $video = Video::find($response->json('id'));
        $response
            ->assertStatus(200)
            ->assertJson($video->toArray())
            ->assertJsonFragment($updatedData);
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

    private function assertInvalidationRequired(TestResponse $response)
    {
        $this->assertInvalidationFields($response,
            ['title', 'description', 'year_launched', 'rating', 'duration'], 'required'
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
    }
}
