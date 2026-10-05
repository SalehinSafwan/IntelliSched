<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimeSlot extends Model
{
    protected $table = 'time_slots';
    protected $guarded = ['id'];

    public function teacherAvailability(): HasMany
    {
        return $this->hasMany(TeacherAvailability::class);
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