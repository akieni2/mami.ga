<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentShare;
use App\Models\DocumentShareFile;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DocumentShareController extends Controller
{
    public function index(): View
    {
        $shares = DocumentShare::query()
            ->withCount('files')
            ->with('creator')
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.document-shares.index', compact('shares'));
    }

    public function create(): View
    {
        return view('admin.document-shares.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'files' => ['required', 'array', 'min:1'],
            'files.*' => [
                'required',
                'file',
                'max:512000',
                'mimetypes:application/pdf,image/jpeg,image/png,image/webp,image/gif,video/mp4,video/quicktime,video/x-msvideo,video/webm',
            ],
        ]);

        $share = DB::transaction(function () use ($request, $data): DocumentShare {
            $share = DocumentShare::query()->create([
                'created_by' => $request->user()?->id,
                'token' => Str::random(32),
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
                'is_active' => true,
            ]);

            foreach ($request->file('files', []) as $index => $file) {
                $path = $file->storeAs(
                    'document-shares/'.$share->token,
                    Str::uuid().'.'.$file->getClientOriginalExtension(),
                    'public',
                );

                DocumentShareFile::query()->create([
                    'document_share_id' => $share->id,
                    'original_name' => $file->getClientOriginalName(),
                    'stored_path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size_bytes' => $file->getSize() ?: 0,
                    'sort_order' => $index,
                ]);
            }

            return $share;
        });

        return redirect()
            ->route('admin.document-shares.show', $share)
            ->with('status', 'Dossier public cree. Le QR code est pret.');
    }

    public function show(DocumentShare $documentShare): View
    {
        $documentShare->load(['files', 'creator']);
        $publicUrl = route('document-shares.public.show', $documentShare);

        return view('admin.document-shares.show', compact('documentShare', 'publicUrl'));
    }

    public function toggle(DocumentShare $documentShare): RedirectResponse
    {
        $documentShare->update(['is_active' => ! $documentShare->is_active]);

        return back()->with('status', $documentShare->is_active ? 'Partage active.' : 'Partage desactive.');
    }

    public function destroy(DocumentShare $documentShare): RedirectResponse
    {
        Storage::disk('public')->deleteDirectory('document-shares/'.$documentShare->token);
        $documentShare->delete();

        return redirect()
            ->route('admin.document-shares.index')
            ->with('status', 'Dossier public supprime.');
    }

    public function qr(DocumentShare $documentShare): Response
    {
        $qrCode = QrCode::create(route('document-shares.public.show', $documentShare))
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(ErrorCorrectionLevel::High)
            ->setSize(600)
            ->setMargin(18)
            ->setRoundBlockSizeMode(RoundBlockSizeMode::Margin);

        return response((new PngWriter)->write($qrCode)->getString(), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="qr-'.$documentShare->token.'.png"',
        ]);
    }
}
