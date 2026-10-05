<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $guarded = ['id'];

    public function offerings(): HasMany
    {
        return $this->hasMany(CourseOffering::class);
    }

    public function expertise(): HasMany
    {
        return $this->hasMany(TeacherExpertise::class);
    }

    public function preferences(): HasMany
    {
        return $this->hasMany(TeacherPreference::class);
    }

    public function teachingHistory(): HasMany
    {
        return $this->hasMany(TeachingHistory::class);
    }

    public function roomEligibility(): HasMany
    {
        return $this->hasMany(RoomCourseEligibility::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }
}