<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $guarded = ['id'];

    public function courseEligibility(): HasMany
    {
        return $this->hasMany(RoomCourseEligibility::class);
    }

    public function scheduleEntries(): HasMany
    {
        return $this->hasMany(ScheduleEntry::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }
}