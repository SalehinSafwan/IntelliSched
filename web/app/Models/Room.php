<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $guarded = ['id'];

    public function courseEligibility(): HasMany
    {
        return $this->hasMany(RoomCourseEligibility::class);
    }

    // Lab courses this room is equipped for.
    public function eligibleCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'room_course_eligibility')
            ->withTimestamps();
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