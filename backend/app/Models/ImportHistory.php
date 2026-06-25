<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportHistory extends Model
{
    protected $fillable = [
        'filename',
        'status',
        'success_count',
        'error_count',
        'warning_count',
        'total_rows',
        'errors',
        'warnings',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'errors' => 'array',
            'warnings' => 'array',
            'success_count' => 'integer',
            'error_count' => 'integer',
            'warning_count' => 'integer',
            'total_rows' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopePartial($query)
    {
        return $query->where('status', 'partial');
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
