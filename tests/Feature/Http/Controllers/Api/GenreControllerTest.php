<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Http\Controllers\Api\GenreController;
use App\Models\Category;
use App\Models\Genre;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\TestResponse;
use Illuminate\Http\Request;
use Tests\Exceptions\TestException;
use Tests\TestCase;
use Tests\Traits\TestValidations;

class GenreControllerTest extends TestCase
{
    use DatabaseMigrations, TestValidations;

    public function testIndex()
    {
        $genre = factory(Genre::class)->create();
        $response = $this->get(route('genres.index'));
        $response->assertStatus(200);
    }

    public function testShow()
    {
        $genre = factory(Genre::class)->create();
        $response = $this->get(route('genres.show', ['genre' => $genre->id]));
        $response
            ->assertStatus(200)
            ->assertJson($genre->toArray());
    }

    public function testStore()
    {
        $response = $this->json('POST', route('genres.store'), $this->sendData());

        $genre = Genre::find($response->json('id'));
        $response
            ->assertStatus(201)
            ->assertJson($genre->toArray());

        $this->assertTrue($response->json('is_active'));

        $response = $this->json('POST', route('genres.store'), array_merge($this->sendData(), ['is_active' => false]));
        $this->assertFalse($response->json('is_active'));
    }

    public function testRollbackStore()
    {
        $controller = \Mockery::mock(GenreController::class)
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
            $this->assertCount(0, Genre::all());
            $hasError = true;
        }

        $this->assertTrue($hasError);
    }

    public function testUpdate()
    {
        $category = factory(Category::class)->create();
        $genre = factory(Genre::class)->create(['is_active' => false]);
        $genre->categories()->sync($category->id);

        $another_category = factory(Category::class)->create();
        $response = $this->json(
            'PUT',
            route('genres.update', ['genre' => $genre->id]),
            ['name' => 'Changed name', 'is_active' => true, 'categories_id' => [$another_category->id]]
        );

        $genre = Genre::with('categories')->find($response->json('id'));
        $this->assertEquals('Changed name', $response->json('name'));
        $this->assertEquals(true, $response->json('is_active'));
        $this->assertHasCategory($response->json('id'), $another_category->id);
    }

    public function testRollbackUpdate()
    {
        $controller = \Mockery::mock(GenreController::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $category = factory(Category::class)->create();
        $genre = factory(Genre::class)->create();
        $genre->categories()->sync($category->id);
        $genre->save();

        $controller
            ->shouldReceive('findOrFail')
            ->withAnyArgs()
            ->andReturn($genre);

        $controller
            ->shouldReceive('validate')
            ->withAnyArgs()
            ->andReturn([
                'name' => $genre->name
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
            $this->assertCount(1, Genre::all());
            $hasError = true;
        }

        $this->assertTrue($hasError);
    }

    protected function assertHasCategory($genreId, $categoryId)
    {
        $this->assertDatabaseHas('category_genre', [
            'genre_id' => $genreId,
            'category_id' => $categoryId
        ]);
    }

    public function testDestroy()
    {
        $genre = factory(Genre::class)->create();
        $response = $this->json('DELETE', route('genres.destroy', ['genre' => $genre->id]));

        $response->assertStatus(204);
        $this->assertNull(Genre::find($genre->id));
        $this->assertNotNull(Genre::withTrashed()->find($genre->id));
    }

    public function testInvalidationDataPOST()
    {
        $response = $this->json('POST', route('genres.store'), []);
        $this->assertInvalidationRequired($response);

        $response = $this->json('POST', route('genres.store'), [
            'name' => str_repeat('x', 256),
            'is_active' => 'a',
            'categories_id' => 'a'
        ]);
        $this->assertInvalidationFields($response, ['name'], 'max.string', ['max' => 255]);
        $this->assertInvalidationFields($response, ['is_active'], 'boolean');
        $this->assertInvalidationFields($response, ['categories_id'], 'array');

        $response = $this->json('POST', route('genres.store'), ['categories_id' => ['999']]);
        $this->assertInvalidationFields($response, ['categories_id'], 'exists');

        $category = factory(Category::class)->create();
        $category->delete();
        $response = $this->json('POST', route('genres.store'), ['categories_id' => [$category->id]]);
        $this->assertInvalidationFields($response, ['categories_id'], 'exists');
    }

    public function testInvalidationDataPUT()
    {
        $genre = factory(Genre::class)->create();
        $response = $this->json('PUT', route('genres.update', ['genre' => $genre->id]), []);
        $this->assertInvalidationRequired($response);

        $response = $this->json('PUT', route('genres.update', ['genre' => $genre->id]), [
            'name' => str_repeat('x', 256),
            'is_active' => 'a',
            'categories_id' => 'a'
        ]);
        $this->assertInvalidationFields($response, ['name'], 'max.string', ['max' => 255]);
        $this->assertInvalidationFields($response, ['is_active'], 'boolean');
        $this->assertInvalidationFields($response, ['categories_id'], 'array');

        $response = $this->json('PUT', route('genres.update', ['genre' => $genre->id]), ['categories_id' => ['999']]);
        $this->assertInvalidationFields($response, ['categories_id'], 'exists');

        $category = factory(Category::class)->create();
        $category->delete();
        $response = $this->json('PUT', route('genres.update', ['genre' => $genre->id]), ['categories_id' => [$category->id]]);
        $this->assertInvalidationFields($response, ['categories_id'], 'exists');
    }

    private function assertInvalidationRequired(TestResponse $response)
    {
        $this->assertInvalidationFields($response, ['name', 'categories_id'], 'required');
        $response->assertJsonMissingValidationErrors(['is_active']);
    }

    private function sendData() {
        $category = factory(Category::class)->create();
        return [
            'name' => 'John Doe',
            'is_active' => true,
            'categories_id' => [$category->id]
        ];
    }
}
