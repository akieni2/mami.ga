@php
    $links = [
        'dashboard' => ['JB Ludo', route('admin.jb-ludo.dashboard')],
        'players' => ['Joueurs', route('admin.jb-ludo.players')],
        'matches' => ['Parties', route('admin.jb-ludo.matches')],
        'leaderboard' => ['Classement', route('admin.jb-ludo.leaderboard')],
    ];
@endphp
<nav class="mb-6 flex flex-wrap gap-2">
    @foreach ($links as $key => [$label, $url])
        <a href="{{ $url }}"
           class="rounded-lg px-3 py-1.5 text-sm {{ ($active ?? '') === $key ? 'bg-slate-900 text-white' : 'bg-white border border-slate-200 text-slate-700' }}">
            {{ $label }}
        </a>
    @endforeach
</nav>
