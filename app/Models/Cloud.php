<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Cloud extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'image_path',
        'mood',
        'mood_label',
        'location_city',
        'location_lat',
        'location_lng',
        'note',
        'cloud_type',
        'collect_date',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'mood' => 'integer',
            'is_public' => 'boolean',
            'location_lat' => 'decimal:7',
            'location_lng' => 'decimal:7',
            'collect_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 获取图片完整访问 URL
     */
    public function getImageUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->image_path);
    }
}
