<?php

namespace App\Http\Controllers;

use App\Models\DocumentShare;
use App\Models\DocumentShareFile;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicDocumentShareController extends Controller
{
    public function show(DocumentShare $documentShare): View
    {
        if (! $documentShare->isPubliclyAvailable()) {
            abort(404);
        }

        $documentShare->increment('scan_count');
        $documentShare->update(['last_scanned_at' => now()]);
        $documentShare->load('files');

        return view('public.document-shares.show', compact('documentShare'));
    }

    public function download(DocumentShare $documentShare, DocumentShareFile $file): StreamedResponse|Response
    {
        if (! $documentShare->isPubliclyAvailable() || (int) $file->document_share_id !== (int) $documentShare->id) {
            abort(404);
        }

        if (! Storage::disk('public')->exists($file->stored_path)) {
            abort(404);
        }

        $file->increment('download_count');
        $file->update(['last_downloaded_at' => now()]);

        return Storage::disk('public')->download($file->stored_path, $file->original_name);
    }
}
