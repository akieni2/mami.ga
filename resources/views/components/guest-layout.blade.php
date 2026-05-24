<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Connexion — MAMI.GA</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div class="flex min-h-screen flex-col items-center bg-slate-900 pt-6 sm:justify-center sm:pt-0">
        <div class="mb-6 text-center">
            <a href="/" class="text-2xl font-bold tracking-tight text-white">MAMI.GA</a>
            <p class="mt-1 text-sm text-slate-400">Administration</p>
        </div>

        <div class="w-full overflow-hidden bg-white px-6 py-6 shadow-xl sm:max-w-md sm:rounded-xl">
            {{ $slot }}
        </div>
    </div>
</body>
</html>
