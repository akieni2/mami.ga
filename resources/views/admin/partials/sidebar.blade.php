@php
    $navItems = [
        ['route' => 'admin.dashboard', 'label' => 'Tableau de bord', 'match' => 'admin.dashboard', 'admin_only' => true],
        ['route' => 'admin.rides.index', 'label' => 'Courses', 'match' => 'admin.rides.*', 'admin_only' => true],
        ['route' => 'admin.drivers.index', 'label' => 'Chauffeurs', 'match' => 'admin.drivers.*', 'admin_only' => true],
        ['route' => 'admin.driver-applications.index', 'label' => 'Candidatures', 'match' => 'admin.driver-applications.*', 'admin_only' => true],
        ['route' => 'admin.users.index', 'label' => 'Utilisateurs', 'match' => 'admin.users.*', 'admin_only' => true],
        ['route' => 'admin.clients.index', 'label' => 'Clients', 'match' => 'admin.clients.*', 'admin_only' => true],
        ['route' => 'admin.map.index', 'label' => 'Carte opérationnelle', 'match' => 'admin.map.*', 'admin_only' => true],
        ['route' => 'admin.reports.index', 'label' => 'Rapports', 'match' => 'admin.reports.*', 'admin_only' => true],
        ['route' => 'admin.municipality.reports.index', 'label' => 'Signalements Owendo', 'match' => 'admin.municipality.reports.*', 'admin_only' => true],
        ['route' => 'admin.municipality.map.index', 'label' => 'Carte municipale', 'match' => 'admin.municipality.map.*', 'admin_only' => true],
        ['route' => 'admin.municipality.fiscal.tax-types', 'label' => 'Fiscalité Owendo', 'match' => 'admin.municipality.fiscal.*', 'admin_only' => true],
        ['route' => 'admin.municipality.collection.dashboard', 'label' => 'Recouvrement terrain', 'match' => 'admin.municipality.collection.*', 'admin_only' => true],
        ['route' => 'admin.municipality.mayor.dashboard', 'label' => 'Quittances maire', 'match' => 'admin.municipality.mayor.*', 'admin_only' => true],
        ['route' => 'admin.municipality.operators.index', 'label' => 'Opérateurs économiques', 'match' => 'admin.municipality.operators.*', 'operators_admin' => true],
    ];
@endphp

<aside id="admin-sidebar"
       class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-slate-700 bg-slate-900 text-slate-100 transition-transform duration-200 lg:static lg:translate-x-0">
    <div class="flex items-center justify-between border-b border-slate-700 px-6 py-5">
        <a href="{{ auth()->user()?->canAccessEconomicOperatorAdmin() && ! auth()->user()?->isAdmin() ? route('admin.municipality.operators.index') : route('admin.dashboard') }}" class="block">
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
        @foreach ($navItems as $item)
            @if (($item['admin_only'] ?? false) && ! auth()->user()?->isAdmin())
                @continue
            @endif
            @if (($item['operators_admin'] ?? false) && ! auth()->user()?->canAccessEconomicOperatorAdmin())
                @continue
            @endif
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
