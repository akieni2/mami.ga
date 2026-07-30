<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentShareFile extends Model
{
    protected $fillable = [
        'document_share_id',
        'original_name',
        'stored_path',
        'mime_type',
        'size_bytes',
        'sort_order',
        'download_count',
        'last_downloaded_at',
    ];

    protected function casts(): array
    {
        return [
            'last_downloaded_at' => 'datetime',
        ];
    }

    public function share(): BelongsTo
    {
        return $this->belongsTo(DocumentShare::class, 'document_share_id');
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

    public function isVideo(): bool
    {
        return str_starts_with((string) $this->mime_type, 'video/');
    }

    public function humanSize(): string
    {
        $bytes = (float) $this->size_bytes;
        $units = ['o', 'Ko', 'Mo', 'Go'];

        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return number_format($bytes, $i === 0 ? 0 : 1, ',', ' ').' '.$units[$i];
    }
}
