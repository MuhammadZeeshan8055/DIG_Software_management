<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    protected $fillable = [
        'user_id',
        'from_date',
        'to_date',
        'leave_type',   // 'full' or 'half'
        'full_days',    // e.g. 1 or 3 for multi-day full leave
        'half_days',    // 1 for a half-day leave
        'is_paid',
        'reason',
        'status',       // pending | approved | rejected
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'from_date' => 'date',
            'to_date' => 'date',
            'full_days' => 'integer',
            'half_days' => 'integer',
            'is_paid' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
