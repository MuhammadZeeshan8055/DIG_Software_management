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

    public function statusLabel(): string
    {
        $labels = [
            'waiting' => 'Waiting',
            'please_wait' => 'Please wait',
            'send_now' => 'Send now',
            'in_meeting' => 'In meeting',
            'completed' => 'Completed',
        ];

        return $labels[$this->status] ?? $this->status;
    }
}
