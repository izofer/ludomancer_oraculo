<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Game;

class GameSeeder extends Seeder
{
    public function run(): void
    {
        Game::create([
            'titulo'                => 'Hell Riders',
            'slug'                  => 'HellRiders',
            'desc'                  => 'Carrera de caballos interactiva TikTok Live.',
            'version_actual'        => '1.0.0',
            'url_descarga'          => 'C:/laragon/www/HellRiders/dist/HellRiders.exe',
            'imagen'                => 'assets/banner_hell_riders.png',
            'status'                => 'activo',
            'mensaje_mantenimiento' => null,
        ]);
    }
}