<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassMessageReply extends Model
{
    // A plain chat line from either side.
    public const COMMENT = 'COMMENT';
    // The CR/ACR's formal notice for the class.
    public const NOTICE = 'NOTICE';
    // The teacher's decision on a notice.
    public const APPROVED = 'APPROVED';
    public const REJECTED = 'REJECTED';

    protected $guarded = ['id'];

    protected $attributes = [
        'kind' => self::COMMENT,
    ];

    public function isNotice(): bool
    {
        return $this->kind === self::NOTICE;
    }

    public function isReview(): bool
    {
        return in_array($this->kind, [self::APPROVED, self::REJECTED], true);
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(ClassMessage::class, 'class_message_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
