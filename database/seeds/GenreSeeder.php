<?php

use App\Models\Genre;
use App\Models\Category;
use Illuminate\Database\Seeder;

class GenreSeeder extends Seeder
{
    public function run()
    {
        factory(Genre::class, 10)->create();
        $categories = Category::all();
        Genre::all()
            ->each(function(Genre $genre) use($categories){
                $categoriesId = $categories->random(5)->pluck('id')->toArray();
                $genre->categories()->attach($categoriesId);
            });
    }
}
