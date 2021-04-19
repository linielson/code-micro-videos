<?php

namespace Tests\Feature\Models;

use App\Models\Genre;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class GenreTest extends TestCase
{
    use DatabaseMigrations;

    public function testList()
    {
        factory(Genre::class, 1)->create();
        $genres = Genre::all();
        $this->assertCount(1, $genres);
        $this->assertEqualsCanonicalizing(
            ['id', 'name', 'is_active', 'created_at', 'updated_at', 'deleted_at'],
            array_keys($genres->first()->getAttributes())
        );
    }

    public function testCreate()
    {
        $genre = Genre::create(['name' => 'John Doe']);
        $genre->refresh();

        $this->assertEquals('John Doe', $genre->name);
        $this->assertTrue($genre->is_active);
    }

    public function testCreateIsActive()
    {
        $genre = Genre::create(['name' => 'John Doe', 'is_active' => false]);
        $genre->refresh();
        $this->assertFalse($genre->is_active);

        $genre = Genre::create(['name' => 'John Doe', 'is_active' => true]);
        $genre->refresh();
        $this->assertTrue($genre->is_active);
    }

    public function testUpdate()
    {
        $genre = factory(Genre::class)->create([
            'is_active' => false
        ])->first();

        $data = [
            'name' => 'John Doe',
            'is_active' => true
        ];
        $genre->update($data);

        foreach($data as $key => $value) {
            $this->assertEquals($value, $genre->{$key});
        }
    }
}
