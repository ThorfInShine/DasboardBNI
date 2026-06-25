<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardPreference extends Model
{
    protected $fillable = [
        'user_id',
        'widget_visibility',
        'widget_layout',
        'date_range_preferences',
    ];

    protected $casts = [
        'widget_visibility' => 'array',
        'widget_layout' => 'array',
        'date_range_preferences' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
