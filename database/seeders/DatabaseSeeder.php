<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder; 
use Database\Seeders\GameSeeder;
use App\Models\Game;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            GameSeeder::class,
        ]);
    }
}