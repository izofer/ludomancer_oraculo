<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    // 1. LISTAR LA INFANTERÍA (Con paginación y búsqueda)
    public function index(Request $request)
    {
        $query = User::with('currentPlan');

        // Búsqueda opcional por email o nombre
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        // Ejecutar paginación
        $users = $query->paginate(15);

        // Contar el ejército global (asumiendo ID 1 = Aprendiz, ID 2 = Maestro)
        $totalAprendiz = User::where('current_plan_id', 1)->count();
        $totalMaestro  = User::where('current_plan_id', 2)->count();

        return response()->json([
            'mensaje' => 'Registros recuperados.',
            'data'    => $users,
            'usuarios_con_planes' => [
                'aprendiz' => $totalAprendiz,
                'maestro'  => $totalMaestro
            ],
            'autorizacion' => 'admins'
        ]);
    }

    // 2. VER UN SOLDADO ESPECÍFICO
    public function show($id)
    {
        $user = User::with('currentPlan')->findOrFail($id);
        
        return response()->json([
            'mensaje' => 'Usuario localizado.',
            'data' => $user
        ]);
    }

    // 3. EDITAR / MODIFICAR PODERES (El núcleo de su solicitud)
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|min:6',
            'mac_address' => 'nullable|string', // Nullable es vital para poder resetear el HWID
            'status' => 'sometimes|in:activo,inactivo,mantenimiento',
            'licencia_adquirida_el' => 'nullable|date',
            'licencia_expira_el' => 'nullable|date',
            'current_plan_id' => 'nullable|exists:plans,id'
        ]);

        // Cifrado táctico de contraseña: Solo se actualiza si el administrador escribió una nueva
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']); // Evitamos que se guarde vacía o como texto plano
        }

        $user->update($validated);

        return response()->json([
            'mensaje' => 'Cuenta modificada con éxito.',
            'data' => $user
        ]);
    }

    // 4. ELIMINACIÓN O DESTIERRO
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        // ADVERTENCIA ARQUITECTÓNICA: 
        // No le recomiendo hacer $user->delete() si este usuario tiene registros en `transactions`.
        // Destruiría la integridad de su contabilidad. Es mejor cambiar su estado a 'inactivo' o crear un estado 'baneado'.
        
        // $user->delete(); // Descomente solo si está seguro de querer destrucción total.
        
        $user->update(['status' => 'inactivo', 'mac_address' => null]);

        return response()->json([
            'mensaje' => 'Usuario desterrado y bloqueado del Hub.'
        ]);
    }
}