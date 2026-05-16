<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gudang Tempua — Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-xl shadow p-8 max-w-md w-full text-center">
        <div class="text-4xl mb-4">🏭</div>
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Gudang Tempua</h1>
        <p class="text-gray-500 mb-1">Selamat datang, {{ auth()->user()->name }}</p>
        <p class="text-sm text-gray-400 mb-6">Role: {{ auth()->user()->getRoleNames()->first() }}</p>
        <p class="text-indigo-500 font-medium">Dashboard — Coming Soon</p>
        <form method="POST" action="{{ route('logout') }}" class="mt-6">
            @csrf
            <button type="submit" class="text-sm text-red-500 hover:underline">Logout</button>
        </form>
    </div>
</body>
</html>
