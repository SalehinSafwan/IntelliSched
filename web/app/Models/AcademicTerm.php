<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicTerm extends Model
{
    protected $guarded = ['id'];

    public function courseOfferings(): HasMany
    {
        return $this->hasMany(CourseOffering::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    public function scheduleRuns(): HasMany
    {
        return $this->hasMany(ScheduleRun::class);
    }

    public function teachingHistory(): HasMany
    {
        return $this->hasMany(TeachingHistory::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }
}