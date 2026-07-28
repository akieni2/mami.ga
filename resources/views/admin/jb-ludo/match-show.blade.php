@extends('layouts.admin')

@section('title', 'JB Ludo — Partie')
@section('page_title', 'JB Ludo')
@section('page_subtitle', $match->reference)

@section('content')
    @include('admin.jb-ludo.partials.nav', ['active' => 'matches'])

    <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4 text-sm">
        <p><strong>Mode :</strong> {{ $match->mode->value }} · <strong>Statut :</strong> {{ $match->status->value }}</p>
        <p><strong>Blancs :</strong> {{ $match->whitePlayer?->pseudo }} · <strong>Noirs :</strong> {{ $match->blackPlayer?->pseudo }}</p>
        <p><strong>Résultat :</strong> {{ $match->result?->value ?? '—' }} ({{ $match->result_reason ?? '—' }})</p>
        <p><strong>Coups :</strong> {{ $match->move_count }}</p>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4">
        <h2 class="mb-3 text-sm font-semibold">Journal des coups</h2>
        <ol class="space-y-2 text-sm">
            @forelse ($match->moves as $move)
                <li class="font-mono text-xs">
                    #{{ $move->server_seq }} {{ $move->color->value }}
                    {{ collect($move->path)->map(fn ($s) => $s['r'].','.$s['c'])->implode(' → ') }}
                    @if (! empty($move->captures))
                        · prises {{ count($move->captures) }}
                    @endif
                </li>
            @empty
                <li class="text-slate-500">Aucun coup.</li>
            @endforelse
        </ol>
    </div>
@endsection
