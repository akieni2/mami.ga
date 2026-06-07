@extends('layouts.admin')

@section('title', 'Candidature #' . $application->id)
@section('page_title', 'Candidature #' . $application->id)
@section('page_subtitle', $application->fullName() . ' — ' . $application->status->value)
@section('admin_page', 'driver-applications')

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.driver-applications.index') }}" class="text-sm text-sky-600 hover:underline">&larr; Retour aux candidatures</a>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-800">Informations personnelles</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div><dt class="text-slate-500">Nom</dt><dd class="font-medium">{{ $application->fullName() }}</dd></div>
                <div><dt class="text-slate-500">Téléphone</dt><dd class="font-medium">{{ $application->phone }}</dd></div>
                <div><dt class="text-slate-500">Email</dt><dd class="font-medium">{{ $application->email }}</dd></div>
                <div><dt class="text-slate-500">Numéro d'identité</dt><dd class="font-medium">{{ $application->national_id_number }}</dd></div>
            </dl>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-800">Permis</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div><dt class="text-slate-500">Numéro permis</dt><dd class="font-medium">{{ $application->driving_license_number }}</dd></div>
            </dl>
            @if ($url = $application->photoUrl('license_photo_path'))
                <img src="{{ $url }}" alt="Photo permis" class="mt-4 max-h-48 rounded-lg border border-slate-200 object-contain">
            @endif
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-800">Véhicule</h2>
            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                <div><dt class="text-slate-500">Marque</dt><dd class="font-medium">{{ $application->vehicle_brand }}</dd></div>
                <div><dt class="text-slate-500">Modèle</dt><dd class="font-medium">{{ $application->vehicle_model }}</dd></div>
                <div><dt class="text-slate-500">Couleur</dt><dd class="font-medium">{{ $application->vehicle_color }}</dd></div>
                <div><dt class="text-slate-500">Année</dt><dd class="font-medium">{{ $application->vehicle_year }}</dd></div>
                <div><dt class="text-slate-500">Plaque</dt><dd class="font-medium">{{ $application->plate_number }}</dd></div>
                <div><dt class="text-slate-500">Type</dt><dd class="font-medium">{{ $application->vehicle_type }}</dd></div>
            </dl>
            @if ($url = $application->photoUrl('vehicle_photo_path'))
                <img src="{{ $url }}" alt="Photo véhicule" class="mt-4 max-h-48 rounded-lg border border-slate-200 object-contain">
            @endif
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-800">Chauffeur</h2>
            @if ($url = $application->photoUrl('driver_photo_path'))
                <img src="{{ $url }}" alt="Photo chauffeur" class="mt-4 max-h-64 rounded-lg border border-slate-200 object-contain">
            @endif
            <p class="mt-4 text-sm text-slate-500">
                Statut : @include('admin.partials.status-badge', ['status' => $application->status->value])
            </p>
            @if ($application->rejection_reason)
                <p class="mt-2 text-sm text-red-700"><strong>Motif de rejet :</strong> {{ $application->rejection_reason }}</p>
            @endif
            @if ($application->reviewed_at)
                <p class="mt-2 text-xs text-slate-500">
                    Traité le {{ $application->reviewed_at->format('d/m/Y H:i') }}
                    @if ($application->reviewer) par {{ $application->reviewer->name }} @endif
                </p>
            @endif
        </div>
    </div>

    @if ($application->status->value === 'pending')
        <div class="mt-6 flex flex-wrap gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <form method="POST" action="{{ route('admin.driver-applications.approve', $application) }}" class="inline">
                @csrf
                <button type="submit"
                        onclick="return confirm('Approuver cette candidature et créer le chauffeur ?')"
                        class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                    Approuver
                </button>
            </form>

            <form method="POST" action="{{ route('admin.driver-applications.reject', $application) }}" class="flex flex-1 flex-wrap items-end gap-3">
                @csrf
                <div class="min-w-[280px] flex-1">
                    <label for="rejection_reason" class="block text-sm font-medium text-slate-700">Motif de rejet (obligatoire)</label>
                    <textarea id="rejection_reason" name="rejection_reason" rows="2" required minlength="10"
                              class="mt-1 w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500"
                              placeholder="Expliquez la raison du rejet…">{{ old('rejection_reason') }}</textarea>
                </div>
                <button type="submit"
                        onclick="return confirm('Rejeter cette candidature ?')"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                    Rejeter
                </button>
            </form>
        </div>
    @endif
@endsection
