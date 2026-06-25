<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Data extends Model
{
    protected $fillable = [
        'category',
        'value',
        'date',
        'title',
        'description',
        'status',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'value' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Data $data) {
            DataHistory::create([
                'data_id' => $data->id,
                'action' => 'created',
                'old_values' => null,
                'new_values' => $data->toArray(),
                'changed_by' => Auth::id() ?? $data->created_by,
            ]);
        });

        static::updated(function (Data $data) {
            DataHistory::create([
                'data_id' => $data->id,
                'action' => 'updated',
                'old_values' => $data->getOriginal(),
                'new_values' => $data->getChanges(),
                'changed_by' => Auth::id() ?? $data->updated_by,
            ]);
        });

        static::deleted(function (Data $data) {
            DataHistory::create([
                'data_id' => $data->id,
                'action' => 'deleted',
                'old_values' => $data->toArray(),
                'new_values' => null,
                'changed_by' => Auth::id(),
            ]);
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(DataHistory::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeRecentDays($query, int $days)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeDateSince($query, int $days)
    {
        return $query->where('date', '>=', now()->subDays($days));
    }

    public function scopeSearch($query, ?string $keyword)
    {
        if (!$keyword) {
            return $query;
        }

        return $query->where(function ($q) use ($keyword) {
            $q->where('title', 'like', "%{$keyword}%")
              ->orWhere('description', 'like', "%{$keyword}%")
              ->orWhere('category', 'like', "%{$keyword}%")
              ->orWhere('metadata->device_id', 'like', "%{$keyword}%")
              ->orWhere('metadata->manufacturer', 'like', "%{$keyword}%")
              ->orWhere('metadata->model', 'like', "%{$keyword}%")
              ->orWhere('metadata->serial_number', 'like', "%{$keyword}%")
              ->orWhere('metadata->os', 'like', "%{$keyword}%");
        });
    }

    /**
     * Get aggregated stats grouped by category using Eloquent collection methods.
     */
    public static function statsByCategory()
    {
        return static::active()
            ->get()
            ->groupBy('category')
            ->map(function ($items, $category) {
                return [
                    'category' => $category,
                    'count' => $items->count(),
                    'total_value' => $items->sum('value'),
                ];
            })
            ->values();
    }

    /**
     * Get daily aggregated data for charts using Eloquent collection methods.
     */
    public static function dailyAggregates(int $days)
    {
        return static::active()
            ->dateSince($days)
            ->orderBy('date', 'asc')
            ->get()
            ->groupBy(fn ($item) => $item->date->format('Y-m-d'))
            ->map(function ($items, $date) {
                return [
                    'date' => $date,
                    'total_value' => $items->sum('value'),
                    'count' => $items->count(),
                ];
            })
            ->values();
    }
}
