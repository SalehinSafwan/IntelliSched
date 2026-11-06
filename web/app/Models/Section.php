<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    protected $guarded = ['id'];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function courseOfferings(): HasMany
    {
        return $this->hasMany(CourseOffering::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function supervisions(): HasMany
    {
        return $this->hasMany(TeacherSupervision::class);
    }

    /**
     * "Batch 1 · Section A", for menus and message headers.
     */
    public function getLabelAttribute(): string
    {
        return ($this->batch?->name ?? 'Batch') . ' · Section ' . $this->name;
    }
}