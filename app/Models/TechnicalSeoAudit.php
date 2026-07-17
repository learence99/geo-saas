<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicalSeoAudit extends Model
{
    protected $table = 'technical_seo_audits';

    protected $fillable = [
        'url',
        'performance_score',
        'seo_score',
        'accessibility_score',
        'best_practices_score',
        'core_web_vitals',
        'issues',
        'lighthouse_version',
        'status',
        'error',
        'triggered_by',
    ];

    protected function casts(): array
    {
        return [
            'performance_score' => 'integer',
            'seo_score' => 'integer',
            'accessibility_score' => 'integer',
            'best_practices_score' => 'integer',
            'core_web_vitals' => 'array',
            'issues' => 'array',
        ];
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'triggered_by');
    }
}
