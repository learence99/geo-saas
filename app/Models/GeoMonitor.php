<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeoMonitor extends Model
{
    protected $table = 'geo_monitors';

    protected $fillable = [
        'domain',
        'url',
        'mode',
        'visibility_score',
        'band',
        'total_snapshots',
        'score_delta',
        'latest_geo_score',
        'latest_geo_band',
        'signals',
        'recommendations',
        'status',
        'error',
        'triggered_by',
    ];

    protected function casts(): array
    {
        return [
            'signals' => 'array',
            'recommendations' => 'array',
            'visibility_score' => 'integer',
            'total_snapshots' => 'integer',
            'score_delta' => 'integer',
            'latest_geo_score' => 'integer',
        ];
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'triggered_by');
    }
}
