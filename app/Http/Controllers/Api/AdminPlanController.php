<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;

class AdminPlanController extends Controller
{
    // 1. EL INVENTARIO (Listar todos los planes, activos e inactivos)
    public function index()
    {
        // Traemos todos los planes, ordenados por los más caros primero
        $plans = Plan::orderBy('price', 'desc')->get();

        return response()->json([
            'mensaje' => 'Catálogo de planes recuperado.',
            'data' => $plans
        ]);
    }

    // 2. FORJAR UN NUEVO PLAN
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0', // Jamás permitimos precios negativos
            'currency' => 'sometimes|string|size:3', // Ejemplo: USD, COP
            'days_of_power' => 'required|integer|min:1',
            'is_active' => 'sometimes|boolean'
        ]);

        // Si no envía moneda, forzamos USD como el estándar de su Imperio
        $validated['currency'] = $validated['currency'] ?? 'USD';
        $validated['is_active'] = $validated['is_active'] ?? true;

        $plan = Plan::create($validated);

        return response()->json([
            'mensaje' => 'Nuevo plan forjado con éxito.',
            'data' => $plan
        ], 201);
    }

    // 3. INSPECCIONAR UN PLAN
    public function show($id)
    {
        $plan = Plan::findOrFail($id);

        return response()->json([
            'mensaje' => 'Plan localizado.',
            'data' => $plan
        ]);
    }

    // 4. MODIFICAR EL PRECIO O PODER
    public function update(Request $request, $id)
    {
        $plan = Plan::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'currency' => 'sometimes|string|size:3',
            'days_of_power' => 'sometimes|integer|min:1',
            'is_active' => 'sometimes|boolean'
        ]);

        $plan->update($validated);

        return response()->json([
            'mensaje' => 'Plan actualizado correctamente.',
            'data' => $plan
        ]);
    }

    // 5. EL RETIRO TÁCTICO (Falso Delete)
    public function destroy($id)
    {
        $plan = Plan::findOrFail($id);
        
        // En lugar de destruirlo, lo ocultamos del mercado
        $plan->update(['is_active' => false]);

        return response()->json([
            'mensaje' => 'El plan ha sido retirado del mercado exitosamente (Archivado).'
        ]);
    }
}