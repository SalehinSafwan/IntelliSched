<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSchedule extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class, 'exam_schedule_rooms')
            ->withTimestamps();
    }

    public function courses(): HasMany
    {
        return $this->hasMany(ExamScheduleCourse::class);
    }

    public function isPublished(): bool
    {
        return $this->status === 'PUBLISHED';
    }
}
