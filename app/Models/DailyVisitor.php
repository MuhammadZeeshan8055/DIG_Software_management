<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyVisitor extends Model
{
    protected $fillable = [
        'name',
        'contact_no',
        'purpose',
        'desk_id',
        'assigned_to',
        'cro_user_id',
        'created_by',
        'status',
        'remarks',
        'case_status',
        'case_admin_note',
        'case_closed_by',
        'case_closed_at',
    ];

    protected function casts(): array
    {
        return [
            'case_closed_at' => 'datetime',
        ];
    }

    public function desk(): BelongsTo
    {
        return $this->belongsTo(Desk::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** CRO who forwarded this visitor to a manager (stays on the case). */
    public function croUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cro_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function caseClosedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'case_closed_by');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(VisitorReminder::class);
    }

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

    /** Is this visitor assigned to this user right now? */
    public function isAssignedTo(int $userId): bool
    {
        return (int) $this->assigned_to === $userId;
    }

    /**
     * CRO view after forwarding: CRO still sees the row,
     * but the manager is the current assignee.
     */
    public function isCroWatching(int $userId): bool
    {
        if (! $this->cro_user_id) {
            return false;
        }

        if ((int) $this->cro_user_id !== $userId) {
            return false;
        }

        // If still assigned to CRO, they are not "watching" yet
        if ($this->isAssignedTo($userId)) {
            return false;
        }

        return true;
    }

    /** Manager already chose Please wait or Send now — CRO can Mark sent. */
    public function managerReadyForCroSend(): bool
    {
        if ($this->status === 'please_wait') {
            return true;
        }

        if ($this->status === 'send_now') {
            return true;
        }

        return false;
    }

    /** Manager name for CRO chip (simple). */
    public function managerName(): string
    {
        if ($this->assignedUser) {
            return $this->assignedUser->name;
        }

        return '—';
    }

    /** When all reminders are done, mark case for admin review. */
    public function setAwaitingReview(): void
    {
        // Do not change a closed case
        if ($this->case_status === 'closed') {
            return;
        }

        // Still have an open reminder? Wait.
        $openCount = $this->reminders()->where('is_done', false)->count();
        if ($openCount > 0) {
            return;
        }

        // Need at least one finished reminder
        $doneCount = $this->reminders()->where('is_done', true)->count();
        if ($doneCount == 0) {
            return;
        }

        $this->case_status = 'awaiting_review';
        $this->save();
    }
}
