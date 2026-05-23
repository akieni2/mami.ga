@php
    $styles = [
        'pending' => 'bg-amber-100 text-amber-800',
        'accepted' => 'bg-sky-100 text-sky-800',
        'arrived' => 'bg-indigo-100 text-indigo-800',
        'started' => 'bg-blue-100 text-blue-800',
        'completed' => 'bg-emerald-100 text-emerald-800',
        'cancelled' => 'bg-slate-100 text-slate-700',
        'online' => 'bg-emerald-100 text-emerald-800',
        'offline' => 'bg-slate-100 text-slate-700',
        'on_ride' => 'bg-amber-100 text-amber-800',
        'busy' => 'bg-amber-100 text-amber-800',
    ];
    $class = $styles[$status] ?? 'bg-slate-100 text-slate-700';
@endphp

<span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $class }}">
    {{ str_replace('_', ' ', $status) }}
</span>
