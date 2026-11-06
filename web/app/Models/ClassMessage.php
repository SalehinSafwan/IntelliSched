<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassMessage extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'class_date' => 'date',
            'new_date' => 'date',
            'last_activity_at' => 'datetime',
            'teacher_seen_at' => 'datetime',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class);
    }

    public function newTimeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class, 'new_time_slot_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ClassMessageReply::class)->orderBy('id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(ClassMessageRead::class);
    }
}
