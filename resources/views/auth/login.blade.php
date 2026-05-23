<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion — MAMI.GA Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-900 px-4">
    <div class="w-full max-w-md rounded-2xl border border-slate-700 bg-slate-800 p-8 shadow-xl">
        <div class="mb-8 text-center">
            <h1 class="text-2xl font-bold text-white">MAMI.GA</h1>
            <p class="mt-1 text-sm text-slate-400">Espace administration</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-slate-300">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                       class="w-full rounded-lg border border-slate-600 bg-slate-900 px-3 py-2 text-white placeholder-slate-500 focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500">
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-slate-300">Mot de passe</label>
                <input type="password" name="password" id="password" required
                       class="w-full rounded-lg border border-slate-600 bg-slate-900 px-3 py-2 text-white focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500">
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-400">
                <input type="checkbox" name="remember" value="1" class="rounded border-slate-600 bg-slate-900 text-sky-500">
                Se souvenir de moi
            </label>

            <button type="submit"
                    class="w-full rounded-lg bg-sky-600 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-500">
                Se connecter
            </button>
        </form>
    </div>
</body>
</html>
