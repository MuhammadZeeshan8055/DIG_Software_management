<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyVisitor extends Model
{
    protected $fillable = [
        'name',
        'contact_no',
        'purpose',
        'desk_id',
        'assigned_to',
        'created_by',
        'status',
        'remarks',
    ];

    public function desk(): BelongsTo
    {
        return $this->belongsTo(Desk::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Simple label for the status column. */
    public function statusLabel(): string
    {
        if ($this->status === 'waiting') {
            return 'Waiting';
        }

        if ($this->status === 'please_wait') {
            return 'Please wait';
        }

        if ($this->status === 'send_now') {
            return 'Send now';
        }

        if ($this->status === 'in_meeting') {
            return 'In meeting';
        }

        if ($this->status === 'completed') {
            return 'Completed';
        }

        return $this->status;
    }
}
