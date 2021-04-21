<?php

use Illuminate\Database\Seeder;

class VideoSeeder extends Seeder
{
    public function run()
    {
        factory(\App\Models\Video::class, 100)->create();
    }
}
