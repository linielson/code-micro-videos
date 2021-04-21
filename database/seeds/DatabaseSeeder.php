<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call(CategorySeeder::class);
        $this->call(GenreSeeder::class);
        $this->call(CastMemberSeeder::class);
        $this->call(VideoSeeder::class);
    }
}
