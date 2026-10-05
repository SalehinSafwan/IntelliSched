<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomCourseEligibility extends Model
{
    protected $table = 'room_course_eligibility';
    protected $guarded = ['id'];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}