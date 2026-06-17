@php use Illuminate\Support\Facades\Storage; @endphp
@extends('layouts.admin')

@section('title', $report->reference)
@section('page_title', $report->reference)
@section('page_subtitle', $report->title)
@section('admin_page', 'municipality-reports')

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-4">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <dl class="grid gap-3 text-sm md:grid-cols-2">
                    <div><dt class="text-slate-500">Catégorie</dt><dd class="font-medium">{{ $report->category->label() }}</dd></div>
                    <div><dt class="text-slate-500">Statut</dt><dd class="font-medium">{{ $report->status->label() }}</dd></div>
                    <div><dt class="text-slate-500">Quartier</dt><dd>{{ $report->sector?->name ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Zone opérationnelle</dt><dd>{{ $report->operationalZone?->name ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">GPS</dt><dd>{{ $report->latitude }}, {{ $report->longitude }}</dd></div>
                    <div><dt class="text-slate-500">Adresse</dt><dd>{{ $report->address ?? '—' }}</dd></div>
                    <div class="md:col-span-2"><dt class="text-slate-500">Description</dt><dd class="mt-1 whitespace-pre-wrap">{{ $report->description }}</dd></div>
                </dl>
            </div>

            @if ($report->attachments->isNotEmpty())
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="mb-3 font-semibold">Photo</h3>
                    @php $photo = $report->attachments->first(); @endphp
                    <img src="{{ Storage::disk($photo->disk)->url($photo->path) }}" alt="Photo signalement" class="max-h-80 rounded-lg border border-slate-200">
                </div>
            @endif

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="mb-3 font-semibold">Historique</h3>
                <ul class="space-y-2 text-sm">
                    @foreach ($report->updates as $update)
                        <li class="rounded-lg bg-slate-50 px-3 py-2">
                            <span class="font-medium">{{ $update->created_at?->format('d/m/Y H:i') }}</span>
                            — {{ $update->from_status?->label() ?? '—' }} → {{ $update->to_status->label() }}
                            @if ($update->notes)<br><span class="text-slate-600">{{ $update->notes }}</span>@endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="space-y-4">
            <form method="POST" action="{{ route('admin.municipality.reports.assign', $report) }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                @csrf
                <h3 class="mb-3 font-semibold">Assigner</h3>
                <select name="assigned_to" class="mb-3 w-full rounded-lg border-slate-200 text-sm" required>
                    <option value="">Agent…</option>
                    @foreach ($agents as $agent)
                        <option value="{{ $agent->id }}" @selected($report->assigned_to === $agent->id)>{{ $agent->name }}</option>
                    @endforeach
                </select>
                <textarea name="notes" rows="2" class="mb-3 w-full rounded-lg border-slate-200 text-sm" placeholder="Notes"></textarea>
                <button type="submit" class="w-full rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">Assigner</button>
            </form>

            <form method="POST" action="{{ route('admin.municipality.reports.status', $report) }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                @csrf
                <h3 class="mb-3 font-semibold">Changer le statut</h3>
                <select name="status" class="mb-3 w-full rounded-lg border-slate-200 text-sm" required>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($report->status === $status)>{{ $status->label() }}</option>
                    @endforeach
                </select>
                <textarea name="notes" rows="2" class="mb-3 w-full rounded-lg border-slate-200 text-sm" placeholder="Notes"></textarea>
                <button type="submit" class="w-full rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white">Mettre à jour</button>
            </form>
        </div>
    </div>
@endsection
