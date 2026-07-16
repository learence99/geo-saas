<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeoPerception extends Model
{
    protected $table = 'geo_perceptions';

    protected $fillable = [
        'url',
        'mode',
        'brand_name',
        'brand_entity_type',
        'main_topic',
        'detected_audience',
        'citability_grade',
        'trust_score',
        'ai_readable_summary',
        'detected_services',
        'evidence_snippets',
        'supported_claims',
        'unsupported_claims',
        'citation_worthy_facts',
        'ambiguities',
        'missing_authority_signals',
        'schema_types_present',
        'status',
        'error',
        'triggered_by',
    ];

    protected function casts(): array
    {
        return [
            'detected_services' => 'array',
            'evidence_snippets' => 'array',
            'supported_claims' => 'array',
            'unsupported_claims' => 'array',
            'citation_worthy_facts' => 'array',
            'ambiguities' => 'array',
            'missing_authority_signals' => 'array',
            'schema_types_present' => 'array',
            'trust_score' => 'integer',
        ];
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'triggered_by');
    }
}
