<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TrackedPrompt extends Model
{
    protected $table = 'tracked_prompts';

    protected $fillable = [
        'subject',
        'prompt',
        'engine',
    ];

    public function snapshots(): HasMany
    {
        return $this->hasMany(VisibilitySnapshot::class, 'tracked_prompt_id');
    }

    public function latestSnapshot(): HasOne
    {
        return $this->hasOne(VisibilitySnapshot::class, 'tracked_prompt_id')->latestOfMany();
    }
}
