<nav class="mb-6 flex flex-wrap gap-2 border-b border-slate-200 pb-3 text-sm">
    @foreach ([
        ['tax-types', 'Types de taxes', 'admin.municipality.fiscal.tax-types'],
        ['rates', 'Taux', 'admin.municipality.fiscal.rates'],
        ['targets', 'Objectifs annuels', 'admin.municipality.fiscal.targets'],
        ['assignments', 'Affectations', 'admin.municipality.fiscal.assignments'],
        ['obligations', 'Obligations', 'admin.municipality.fiscal.obligations'],
    ] as [$key, $label, $route])
        <a href="{{ route($route) }}"
           class="rounded-lg px-3 py-1.5 {{ ($active ?? '') === $key ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
            {{ $label }}
        </a>
    @endforeach
</nav>
