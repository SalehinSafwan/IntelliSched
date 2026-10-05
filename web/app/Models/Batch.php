<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Batch extends Model
{
    protected $guarded = ['id'];

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function teachingHistory(): HasMany
    {
        return $this->hasMany(TeachingHistory::class);
    }

    public function scheduleRuns(): HasMany
    {
        return $this->hasMany(ScheduleRun::class);
    }
}