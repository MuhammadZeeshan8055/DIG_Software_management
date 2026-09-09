<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    protected $fillable = [
        'user_id',
        'from_date',
        'to_date',
        'leave_type',   // 'full' or 'half'
        'full_days',
        'half_days',
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

    /**
     * Simple check: does this user have APPROVED leave on this day?
     *
     * Example: leave from 9 Sep to 10 Sep, today is 9 Sep → true
     */
    public static function isOnApprovedLeave(int $userId, ?string $date = null): bool
    {
        // If no date given, use today
        if ($date === null) {
            $date = Carbon::now(app_timezone())->toDateString();
        }

        // Find one approved leave where:
        // from_date <= today  AND  to_date >= today
        $leave = self::query()
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->whereDate('from_date', '<=', $date)
            ->whereDate('to_date', '>=', $date)
            ->first();

        // true if we found a row, false if not
        if ($leave) {
            return true;
        }

        return false;
    }

    /**
     * Simple check: does this user already have leave on these dates?
     * (pending OR approved — so they cannot apply again)
     *
     * Overlap rule (easy):
     * existing.from <= new.to  AND  existing.to >= new.from
     */
    public static function hasOverlap(int $userId, string $fromDate, string $toDate): bool
    {
        $leave = self::query()
            ->where('user_id', $userId)
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('from_date', '<=', $toDate)
            ->whereDate('to_date', '>=', $fromDate)
            ->first();

        if ($leave) {
            return true;
        }

        return false;
    }
}
