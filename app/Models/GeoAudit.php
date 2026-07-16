<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeoAudit extends Model
{
    protected $fillable = [
        'url',
        'score',
        'band',
        'checks',
        'error',
        'status',
        'triggered_by',
    ];

    protected $casts = [
        'checks' => 'array',
    ];

    public function triggeredBy()
    {
        return $this->belongsTo(Admin::class, 'triggered_by');
    }
}
