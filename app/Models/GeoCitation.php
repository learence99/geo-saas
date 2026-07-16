<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeoCitation extends Model
{
    protected $table = 'geo_citations';

    protected $fillable = [
        'brand',
        'domain',
        'topic',
        'provider',
        'verdict',
        'queries_run',
        'brand_mention_rate',
        'domain_citation_rate',
        'entries',
        'top_cited_domains',
        'status',
        'error',
        'triggered_by',
    ];

    protected function casts(): array
    {
        return [
            'entries' => 'array',
            'top_cited_domains' => 'array',
            'queries_run' => 'integer',
            'brand_mention_rate' => 'integer',
            'domain_citation_rate' => 'integer',
        ];
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'triggered_by');
    }
}
