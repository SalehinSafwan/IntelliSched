<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherSupervision extends Model
{
    protected $guarded = ['id'];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
}
