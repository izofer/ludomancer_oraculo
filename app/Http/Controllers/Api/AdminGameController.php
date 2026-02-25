<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminGameController extends Controller
{
    // 1. REVISAR EL ARSENAL (Listar todos los juegos)
    public function index()
    {
        $games = Game::orderBy('created_at', 'desc')->get();

        return response()->json([
            'mensaje' => 'Arsenal recuperado.',
            'data' => $games
        ]);
    }

    // 2. FORJAR UNA NUEVA ARMA (Subir un juego)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'slug' => 'required|string|unique:games,slug|max:255',
            'desc' => 'required|string',
            'version_actual' => 'required|string|max:50',
            'status' => 'required|in:activo,mantenimiento,bug_critico',
            'mensaje_mantenimiento' => 'nullable|string',
            // Validamos los archivos físicos
            'archivo_juego' => 'required|file|mimes:zip', // Exigimos que sea un ZIP
            'imagen_file' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048' // Max 2MB
        ]);

        // Aseguramos que el slug tenga formato correcto (ej: "mi-juego-1")
        $slug = Str::slug($validated['slug']);

        // A. Guardar el archivo ZIP en su propia carpeta (ej: storage/app/games/hell-riders/...)
        $gameFileName = $slug . '_v' . str_replace('.', '_', $validated['version_actual']) . '.zip';
        // Al concatenar 'games/' . $slug, Laravel crea la carpeta automáticamente si no existe
        $gamePath = $request->file('archivo_juego')->storeAs('games/' . $slug, $gameFileName);

        // B. Guardar la imagen (Aterriza en storage/app/public/banners para que la web pueda verla)
        $imagePath = $request->file('imagen_file')->store('banners', 'public');

        // C. Registrar en la base de datos
        $game = Game::create([
            'titulo' => $validated['titulo'],
            'slug' => $slug,
            'desc' => $validated['desc'],
            'version_actual' => $validated['version_actual'],
            'url_descarga' => $gamePath, // Guardamos la ruta interna (ej: games/juego_v1.zip)
            'imagen' => '/storage/' . $imagePath, // Guardamos la ruta pública
            'status' => $validated['status'],
            'mensaje_mantenimiento' => $validated['mensaje_mantenimiento']
        ]);

        return response()->json([
            'mensaje' => 'Juego forjado y empaquetado con éxito.',
            'data' => $game
        ], 201);
    }

    // 3. INSPECCIONAR UN JUEGO
    public function show($id)
    {
        $game = Game::findOrFail($id);
        return response()->json(['data' => $game]);
    }

    // 4. ACTUALIZAR UN JUEGO (Lanzar un parche)
    public function update(Request $request, $id)
    {
        $game = Game::findOrFail($id);

        $validated = $request->validate([
            'titulo' => 'sometimes|string|max:255',
            'desc' => 'sometimes|string',
            'version_actual' => 'sometimes|string|max:50',
            'status' => 'sometimes|in:activo,mantenimiento,bug_critico',
            'mensaje_mantenimiento' => 'nullable|string',
            'archivo_juego' => 'nullable|file|mimes:zip', 
            'imagen_file' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048'
        ]);

        // Si el Emperador sube un nuevo ZIP (Actualización del juego)
        if ($request->hasFile('archivo_juego')) {
            // Borramos el ZIP viejo para no llenar el servidor con versiones pasadas
            if (Storage::exists($game->url_descarga)) {
                Storage::delete($game->url_descarga);
            }

            $version = $validated['version_actual'] ?? $game->version_actual;
            $slug = $validated['slug'] ?? $game->slug; // Usamos el slug actual o el nuevo si lo cambió
            
            $gameFileName = $slug . '_v' . str_replace('.', '_', $version) . '.zip';
            
            // Guardamos el nuevo ZIP dentro de la subcarpeta del juego
            $validated['url_descarga'] = $request->file('archivo_juego')->storeAs('games/' . $slug, $gameFileName);
        }

        // Si sube un nuevo banner
        if ($request->hasFile('imagen_file')) {
            // Borrar imagen vieja (limpiamos 'storage/' del string original)
            $oldImagePath = str_replace('/storage/', '', $game->imagen);
            if (Storage::disk('public')->exists($oldImagePath)) {
                Storage::disk('public')->delete($oldImagePath);
            }

            $imagePath = $request->file('imagen_file')->store('banners', 'public');
            $validated['imagen'] = '/storage/' . $imagePath;
        }

        $game->update($validated);

        return response()->json([
            'mensaje' => 'Actualización desplegada correctamente.',
            'data' => $game
        ]);
    }

    // 5. DESTRUIR UN JUEGO
    public function destroy($id)
    {
        $game = Game::findOrFail($id);

        // TÁCTICA DE TIERRA ARRASADA: Borramos la carpeta entera del juego y todo su contenido
        $folderPath = 'games/' . $game->slug;
        if (Storage::exists($folderPath)) {
            Storage::deleteDirectory($folderPath);
        }

        // Borrar el banner público
        $oldImagePath = str_replace('/storage/', '', $game->imagen);
        if (Storage::disk('public')->exists($oldImagePath)) {
            Storage::disk('public')->delete($oldImagePath);
        }

        $game->delete();

        return response()->json([
            'mensaje' => 'Juego y su base de operaciones erradicados del sistema por completo.'
        ]);
    }
}