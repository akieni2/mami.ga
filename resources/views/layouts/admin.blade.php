<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') — MAMI.GA</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <div class="flex min-h-screen">
        @include('admin.partials.sidebar')

        <div class="flex flex-1 flex-col">
            <header class="border-b border-slate-200 bg-white px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-lg font-semibold text-slate-900">@yield('page_title', 'Tableau de bord')</h1>
                        @hasSection('page_subtitle')
                            <p class="mt-0.5 text-sm text-slate-500">@yield('page_subtitle')</p>
                        @endif
                    </div>
                    <div class="text-sm text-slate-600">
                        {{ auth()->user()->name ?? 'Admin' }}
                    </div>
                </div>
            </header>

            <main class="flex-1 p-6">
                @if (session('success'))
                    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {{ session('success') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
