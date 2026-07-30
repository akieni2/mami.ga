<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $documentShare->title }} - MAMI.GA</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <main class="mx-auto min-h-screen max-w-3xl px-4 py-6 sm:py-10">
        <header class="mb-6">
            <div class="text-sm font-semibold uppercase text-slate-500">MAMI.GA</div>
            <h1 class="mt-2 text-2xl font-bold text-slate-950">{{ $documentShare->title }}</h1>
            @if ($documentShare->description)
                <p class="mt-3 text-base leading-7 text-slate-600">{{ $documentShare->description }}</p>
            @endif
        </header>

        <section class="space-y-3">
            @forelse ($documentShare->files as $file)
                <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    @if ($file->isImage())
                        <img src="{{ Storage::disk('public')->url($file->stored_path) }}" alt="{{ $file->original_name }}"
                             class="mb-3 max-h-72 w-full rounded-lg object-contain">
                    @elseif ($file->isVideo())
                        <video controls preload="metadata" class="mb-3 max-h-72 w-full rounded-lg bg-black">
                            <source src="{{ Storage::disk('public')->url($file->stored_path) }}" type="{{ $file->mime_type }}">
                        </video>
                    @endif

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="font-semibold text-slate-900">{{ $file->original_name }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ $file->humanSize() }}</p>
                        </div>
                        <a href="{{ route('document-shares.public.download', [$documentShare, $file]) }}"
                           class="inline-flex justify-center rounded-lg bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                            Telecharger
                        </a>
                    </div>
                </article>
            @empty
                <div class="rounded-lg border border-slate-200 bg-white p-8 text-center text-slate-500">
                    Aucun document disponible.
                </div>
            @endforelse
        </section>

        <footer class="mt-8 text-center text-xs text-slate-400">
            Les fichiers se telechargent un par un pour rester simples a utiliser sur telephone.
        </footer>
    </main>
</body>
</html>
