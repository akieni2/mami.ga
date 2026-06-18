<aside id="admin-sidebar"
       class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-slate-700 bg-slate-900 text-slate-100 transition-transform duration-200 lg:static lg:translate-x-0">
    <div class="flex items-center justify-between border-b border-slate-700 px-6 py-5">
        <a href="{{ route('admin.dashboard') }}" class="block">
            <span class="text-lg font-bold tracking-tight text-white">MAMI.GA</span>
            <span class="mt-1 block text-xs text-slate-400">Exploitation</span>
        </a>
        <button type="button" id="sidebar-close" class="rounded p-1 text-slate-400 hover:text-white lg:hidden" aria-label="Fermer">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <nav class="flex-1 space-y-1 px-3 py-4">
        @foreach ([
            ['route' => 'admin.dashboard', 'label' => 'Tableau de bord', 'match' => 'admin.dashboard'],
            ['route' => 'admin.rides.index', 'label' => 'Courses', 'match' => 'admin.rides.*'],
            ['route' => 'admin.drivers.index', 'label' => 'Chauffeurs', 'match' => 'admin.drivers.*'],
            ['route' => 'admin.driver-applications.index', 'label' => 'Candidatures', 'match' => 'admin.driver-applications.*'],
            ['route' => 'admin.clients.index', 'label' => 'Clients', 'match' => 'admin.clients.*'],
            ['route' => 'admin.map.index', 'label' => 'Carte opérationnelle', 'match' => 'admin.map.*'],
            ['route' => 'admin.reports.index', 'label' => 'Rapports', 'match' => 'admin.reports.*'],
            ['route' => 'admin.municipality.reports.index', 'label' => 'Signalements Owendo', 'match' => 'admin.municipality.reports.*'],
            ['route' => 'admin.municipality.map.index', 'label' => 'Carte municipale', 'match' => 'admin.municipality.map.*'],
            ['route' => 'admin.municipality.fiscal.tax-types', 'label' => 'Fiscalité Owendo', 'match' => 'admin.municipality.fiscal.*'],
            ['route' => 'admin.municipality.collection.dashboard', 'label' => 'Recouvrement terrain', 'match' => 'admin.municipality.collection.*'],
        ] as $item)
            <a href="{{ route($item['route']) }}"
               class="{{ request()->routeIs($item['match']) ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }} block rounded-lg px-3 py-2 text-sm font-medium transition">
                {{ $item['label'] }}
            </a>
        @endforeach
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
