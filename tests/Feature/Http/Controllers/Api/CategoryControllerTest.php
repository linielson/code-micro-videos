<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Category;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\TestValidations;

class CategoryControllerTest extends TestCase
{

    use DatabaseMigrations, TestValidations;

    public function testIndex()
    {
        $category = factory(Category::class)->create();
        $response = $this->get(route('categories.index'));
        $response
            ->assertStatus(200)
            ->assertJson([$category->toArray()]);
    }

    public function testShow()
    {
        $category = factory(Category::class)->create();
        $response = $this->get(route('categories.show', ['category' => $category->id]));
        $response
            ->assertStatus(200)
            ->assertJson($category->toArray());
    }

    public function testStore()
    {
        $response = $this->json('POST', route('categories.store'), ['name' => 'John Doe']);

        $category = Category::find($response->json('id'));
        $response
            ->assertStatus(201)
            ->assertJson($category->toArray());

        $this->assertTrue($response->json('is_active'));
        $this->assertNull($response->json('description'));

        $response = $this->json('POST', route('categories.store'), [
            'name' => 'John Doe',
            'description' => 'Some description',
            'is_active' => false
        ]);
        $response->assertJsonFragment([
            'description' => 'Some description',
            'is_active' => false
        ]);
    }

    public function testUpdate()
    {
        $category = factory(Category::class)->create([
            'description' => 'Some description',
            'is_active' => false
        ]);
        $response = $this->json(
            'PUT',
            route('categories.update', ['category' => $category->id]),
            ['name' => 'Changed name', 'description' => 'Changed description', 'is_active' => true]
        );

        $category = Category::find($response->json('id'));
        $response
            ->assertStatus(200)
            ->assertJson($category->toArray())
            ->assertJsonFragment([
                'name' => 'Changed name',
                'description' => 'Changed description',
                'is_active' => true
            ]);

        $response = $this->json(
            'PUT',
            route('categories.update', ['category' => $category->id]),
            ['name' => 'Changed name', 'description' => '']
        );
        $response->assertJsonFragment(['description' => null]);

        $category->description = 'Test';
        $category->save();

        $response = $this->json(
            'PUT',
            route('categories.update', ['category' => $category->id]),
            ['name' => 'Changed name', 'description' => null]
        );
        $response->assertJsonFragment(['description' => null]);
    }

    public function testDestroy()
    {
        $category = factory(Category::class)->create();
        $response = $this->json('DELETE', route('categories.destroy', ['category' => $category->id]));

        $response->assertStatus(204);
        $this->assertNull(Category::find($category->id));
        $this->assertNotNull(Category::withTrashed()->find($category->id));
    }

    public function testInvalidationDataPOST()
    {
        $response = $this->json('POST', route('categories.store'), []);
        $this->assertInvalidationRequired($response);

        $response = $this->json('POST', route('categories.store'), [
            'name' => str_repeat('x', 256),
            'is_active' => 'a'
        ]);
        $this->assertInvalidationFields($response, ['name'], 'max.string', ['max' => 255]);
        $this->assertInvalidationFields($response, ['is_active'], 'boolean');
    }

    public function testInvalidationDataPUT()
    {
        $category = factory(Category::class)->create();
        $response = $this->json('PUT', route('categories.update', ['category' => $category->id]), []);
        $this->assertInvalidationRequired($response);

        $response = $this->json('PUT', route('categories.update', ['category' => $category->id]), [
            'name' => str_repeat('x', 256),
            'is_active' => 'a'
        ]);
        $this->assertInvalidationFields($response, ['name'], 'max.string', ['max' => 255]);
        $this->assertInvalidationFields($response, ['is_active'], 'boolean');
    }

    private function assertInvalidationRequired(TestResponse $response)
    {
        $this->assertInvalidationFields($response, ['name'], 'required');
        $response->assertJsonMissingValidationErrors(['is_active']);
    }
}
