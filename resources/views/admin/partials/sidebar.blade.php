<aside class="flex w-64 flex-col border-r border-slate-200 bg-slate-900 text-slate-100">
    <div class="border-b border-slate-700 px-6 py-5">
        <a href="{{ route('admin.dashboard') }}" class="block">
            <span class="text-lg font-bold tracking-tight text-white">MAMI.GA</span>
            <span class="mt-1 block text-xs text-slate-400">Administration</span>
        </a>
    </div>

    <nav class="flex-1 space-y-1 px-3 py-4">
        <a href="{{ route('admin.dashboard') }}"
           class="{{ request()->routeIs('admin.dashboard') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} flex items-center rounded-lg px-3 py-2 text-sm font-medium transition">
            Tableau de bord
        </a>
        <a href="{{ route('admin.drivers.index') }}"
           class="{{ request()->routeIs('admin.drivers.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} flex items-center rounded-lg px-3 py-2 text-sm font-medium transition">
            Chauffeurs
        </a>
        <a href="{{ route('admin.rides.index') }}"
           class="{{ request()->routeIs('admin.rides.*') ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} flex items-center rounded-lg px-3 py-2 text-sm font-medium transition">
            Courses
        </a>
    </nav>

    <div class="border-t border-slate-700 p-4">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="w-full rounded-lg bg-slate-800 px-3 py-2 text-left text-sm font-medium text-slate-200 transition hover:bg-slate-700">
                Déconnexion
            </button>
        </form>
    </div>
</aside>
