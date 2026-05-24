@php
    $hint = $hint ?? null;
    $color = $color ?? 'slate';
    $id = $id ?? null;
    $colors = [
        'slate' => 'border-slate-200 bg-white',
        'emerald' => 'border-emerald-200 bg-emerald-50',
        'sky' => 'border-sky-200 bg-sky-50',
        'amber' => 'border-amber-200 bg-amber-50',
    ];
    $valueColors = [
        'slate' => 'text-slate-900',
        'emerald' => 'text-emerald-700',
        'sky' => 'text-sky-700',
        'amber' => 'text-amber-700',
    ];
@endphp

<div class="rounded-xl border p-5 shadow-sm {{ $colors[$color] ?? $colors['slate'] }}">
    <p class="text-sm font-medium text-slate-600">{{ $label }}</p>
    <p @if($id) id="{{ $id }}" @endif class="mt-2 text-3xl font-bold {{ $valueColors[$color] ?? $valueColors['slate'] }}">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif
</div>
