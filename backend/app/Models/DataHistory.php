<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataHistory extends Model
{
    protected $fillable = [
        'data_id',
        'action',
        'old_values',
        'new_values',
        'changed_by',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function data(): BelongsTo
    {
        return $this->belongsTo(Data::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
