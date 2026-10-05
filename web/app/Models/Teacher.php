<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    protected $guarded = ['id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function expertise(): HasMany
    {
        return $this->hasMany(TeacherExpertise::class);
    }

    public function preferences(): HasMany
    {
        return $this->hasMany(TeacherPreference::class);
    }

    public function availability(): HasMany
    {
        return $this->hasMany(TeacherAvailability::class);
    }

    public function teachingHistory(): HasMany
    {
        return $this->hasMany(TeachingHistory::class);
    }

    public function scheduleEntries(): HasMany
    {
        return $this->hasMany(ScheduleEntry::class);
    }
}