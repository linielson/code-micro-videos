<?php

namespace Tests\Feature\Models;

use App\Models\Category;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use DatabaseMigrations;

    public function testList()
    {
        factory(Category::class)->create();
        $categories = Category::all();
        $this->assertCount(1, $categories);
        $this->assertEqualsCanonicalizing(
            ['id', 'name', 'description', 'is_active', 'created_at', 'updated_at', 'deleted_at'],
            array_keys($categories->first()->getAttributes())
        );
    }

    public function testCreate()
    {
        $category = Category::create(['name' => 'John Doe']);
        $category->refresh();

        $this->assertEquals(36, strlen($category->id));
        $this->assertEquals('John Doe', $category->name);
        $this->assertNull($category->description);
        $this->assertTrue($category->is_active);
    }

    public function testCreateDescription()
    {
        //with null description
        $category = Category::create(['name' => 'John Doe', 'description' => null]);
        $category->refresh();
        $this->assertNull($category->description);

        //with filled description
        $category = Category::create(['name' => 'John Doe', 'description' => 'some description']);
        $category->refresh();
        $this->assertEquals('some description', $category->description);
    }

    public function testCreateIsActive()
    {
        $category = Category::create(['name' => 'John Doe', 'is_active' => false]);
        $category->refresh();
        $this->assertFalse($category->is_active);

        $category = Category::create(['name' => 'John Doe', 'is_active' => true]);
        $category->refresh();
        $this->assertTrue($category->is_active);
    }

    public function testUpdate()
    {
        $category = factory(Category::class)->create([
            'description' => 'some description',
            'is_active' => false
        ]);

        $data = [
            'name' => 'John Doe',
            'description' => 'updated description',
            'is_active' => true
        ];
        $category->update($data);

        foreach($data as $key => $value) {
            $this->assertEquals($value, $category->{$key});
        }
    }

    public function testDelete()
    {
        $category = factory(Category::class)->create();
        $category->delete();
        $this->assertNull(Category::find($category->id));

        $category->restore();
        $this->assertNotNull(Category::find($category->id));
    }
}
