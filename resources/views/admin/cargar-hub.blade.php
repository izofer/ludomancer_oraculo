<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centro de Mando | Cargar Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-200 flex items-center justify-center h-screen">

    <div class="bg-gray-800 p-8 rounded-lg shadow-2xl w-full max-w-md border border-gray-700">
        <h2 class="text-2xl font-bold mb-6 text-center text-blue-400">Despliegue de Ludomancer Hub</h2>

        @if(session('exito'))
            <div class="bg-green-900 border border-green-500 text-green-300 p-3 rounded mb-4 text-sm">
                {{ session('exito') }}
            </div>
        @endif

        @if(session('error') || $errors->any())
            <div class="bg-red-900 border border-red-500 text-red-300 p-3 rounded mb-4 text-sm">
                {{ session('error') ?? $errors->first() }}
            </div>
        @endif

        <form action="{{ route('admin.cargar-hub.procesar') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            
            <div class="border-2 border-dashed border-gray-600 rounded-md p-6 text-center hover:border-blue-500 transition-colors">
                <label for="instalador" class="cursor-pointer flex flex-col items-center">
                    <svg class="w-12 h-12 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    <span class="text-sm text-gray-400">Seleccionar LudomancerHub_setup.exe</span>
                    <input type="file" name="instalador" id="instalador" class="hidden" accept=".exe" required>
                </label>
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500">
                Subir a la Bóveda
            </button>
        </form>
    </div>

</body>
</html>