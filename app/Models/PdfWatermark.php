<?php

namespace App\Models;

use App\Enums\PdfWatermarkStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PdfWatermark extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'status' => PdfWatermarkStatus::class
    ];

    public function getRouteKey(): string
    {
        return 'uuid';
    }

    protected static function boot(): void
    {
        parent::boot();
        static::creating(static function ($model) {
            $model->uuid = $model->uuid ?: Str::orderedUuid();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
