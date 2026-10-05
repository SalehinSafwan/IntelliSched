<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduleRun extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'objective_score' => 'decimal:2',
            'generated_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(ScheduleEntry::class);
    }

    public function changes(): HasMany
    {
        return $this->hasMany(ScheduleChange::class);
    }
}