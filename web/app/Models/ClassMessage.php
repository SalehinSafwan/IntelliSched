<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassMessage extends Model
{
    // Session stages, in order.
    public const AWAITING_NOTICE = 'AWAITING_NOTICE';
    public const PENDING_REVIEW = 'PENDING_REVIEW';
    public const CHANGES_REQUESTED = 'CHANGES_REQUESTED';
    public const COMPLETED = 'COMPLETED';

    protected $guarded = ['id'];

    protected $attributes = [
        'status' => self::AWAITING_NOTICE,
    ];

    protected function casts(): array
    {
        return [
            'class_date' => 'date',
            'new_date' => 'date',
            'last_activity_at' => 'datetime',
            'teacher_seen_at' => 'datetime',
            'completed_at' => 'datetime',
            'posted_at' => 'datetime',
        ];
    }

    public function isCompleted(): bool
    {
        return $this->status === self::COMPLETED;
    }

    /**
     * The CR/ACR still has to write (or rewrite) the notice.
     */
    public function awaitsNotice(): bool
    {
        return in_array($this->status, [self::AWAITING_NOTICE, self::CHANGES_REQUESTED], true);
    }

    public function awaitsReview(): bool
    {
        return $this->status === self::PENDING_REVIEW;
    }

    public function statusInfo(): array
    {
        $statuses = config('intellisched.message_statuses');

        return $statuses[$this->status] ?? $statuses[self::AWAITING_NOTICE];
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

    public function notices(): HasMany
    {
        return $this->hasMany(ClassMessageReply::class)
            ->where('kind', ClassMessageReply::NOTICE)
            ->orderBy('id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(ClassMessageRead::class);
    }
}
