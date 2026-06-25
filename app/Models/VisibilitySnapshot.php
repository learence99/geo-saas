<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisibilitySnapshot extends Model
{
    public $timestamps = false; // 只有 checked_at，无 created_at/updated_at

    protected $table = 'visibility_snapshots';

    protected $fillable = [
        'tracked_prompt_id',
        'engine',
        'is_cited',
        'rank',
        'competitors',
        'sentiment',
        'raw_answer',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'tracked_prompt_id' => 'integer',
            'is_cited' => 'boolean',
            'rank' => 'integer',
            'competitors' => 'array',
            'checked_at' => 'datetime',
        ];
    }

    public function trackedPrompt(): BelongsTo
    {
        return $this->belongsTo(TrackedPrompt::class, 'tracked_prompt_id');
    }
}
