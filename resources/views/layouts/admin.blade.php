<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — MAMI.GA</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/admin.js'])
    @stack('styles')
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased"
      data-admin-page="@yield('admin_page', 'dashboard')">
    <div class="flex min-h-screen">
        @include('admin.partials.sidebar')

        <div class="flex min-w-0 flex-1 flex-col lg:ml-0">
            <header class="sticky top-0 z-20 border-b border-slate-200 bg-white px-4 py-4 lg:px-6">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <button type="button" id="sidebar-open"
                                class="rounded-lg border border-slate-200 p-2 text-slate-600 hover:bg-slate-50 lg:hidden"
                                aria-label="Ouvrir le menu">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>
                        <div>
                            <h1 class="text-lg font-semibold text-slate-900">@yield('page_title', 'Tableau de bord')</h1>
                            @hasSection('page_subtitle')
                                <p class="mt-0.5 text-sm text-slate-500">@yield('page_subtitle')</p>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-3 text-sm text-slate-600">
                        <span id="live-refresh-indicator" class="hidden text-xs text-slate-400">Actualisation…</span>
                        <span>{{ auth()->user()->name }}</span>
                    </div>
                </div>
            </header>

            <main class="flex-1 p-4 lg:p-6">
                @if (session('success'))
                    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {{ session('success') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <div id="sidebar-backdrop" class="fixed inset-0 z-30 hidden bg-slate-900/50 lg:hidden"></div>

    @stack('scripts')
</body>
</html>
