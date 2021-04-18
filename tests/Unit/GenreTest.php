<?php

namespace Tests\Unit;

use App\Models\Genre;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\Uuid;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class GenreTest extends TestCase
{
    use DatabaseMigrations;

    public function testIfUseTraits()
    {
        Genre::create(['name' => 'test']);
        $traits = [SoftDeletes::class, Uuid::class];
        $genreTraits = array_keys(class_uses(Genre::class));
        $this->assertEquals($traits, $genreTraits);
    }

    public function testIncrementing()
    {
        $genre = new Genre();
        $this->assertFalse($genre->getIncrementing());
    }

    public function testKeyType()
    {
        $genre = new Genre();
        $this->assertEquals('string', $genre->getKeyType());
    }

    public function testDates()
    {
        $genre = new Genre();
        $dates = ['deleted_at', 'created_at', 'updated_at'];
        foreach ($dates as $date) {
            $this->assertContains($date, $genre->getDates());
        }
        $this->assertCount(count($dates), $genre->getDates());
    }

    public function testFillable()
    {
        $genre = new Genre();
        $this->assertEquals(['name', 'is_active'], $genre->getFillable());
    }
}
