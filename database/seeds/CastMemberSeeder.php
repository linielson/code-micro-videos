<?php

use Illuminate\Database\Seeder;

class CastMemberSeeder extends Seeder
{
    public function run()
    {
        factory(\App\Models\CastMember::class, 100)->create();
    }
}
