<?php

namespace App\Modules\Municipality\Models;

use App\Models\User;
use App\Modules\Municipality\Enums\ReceiptDocumentFormat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MunicipalReceiptDocument extends Model
{
    protected $fillable = [
        'municipal_receipt_id',
        'format',
        'version',
        'storage_path',
        'disk',
        'generated_by',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'format' => ReceiptDocumentFormat::class,
            'generated_at' => 'datetime',
        ];
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(MunicipalReceipt::class, 'municipal_receipt_id');
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function contents(): string
    {
        return Storage::disk($this->disk)->get($this->storage_path);
    }
}
