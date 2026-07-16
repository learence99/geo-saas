<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeoFix extends Model
{
    protected $table = 'geo_fixes';

    protected $fillable = [
        'url',
        'only_categories',
        'score_before',
        'score_estimated_after',
        'fixes',
        'skipped',
        'status',
        'error',
        'triggered_by',
    ];

    protected function casts(): array
    {
        return [
            'fixes' => 'array',
            'skipped' => 'array',
            'score_before' => 'integer',
            'score_estimated_after' => 'integer',
        ];
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'triggered_by');
    }
}
