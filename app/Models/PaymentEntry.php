<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentEntry extends Model
{
    protected $fillable = [
        'ticket_import_id',
        'user_id',
        'passenger_name',
        'booking_reference',
        'amount_agreed',
        'amount_paid',
        'balance',
        'payment_status',
        'receiving_account_id',
        'received_in',
        'received_account',
    ];

    protected function casts(): array
    {
        return [
            'amount_agreed' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'balance' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ticketImport(): BelongsTo
    {
        return $this->belongsTo(TicketImport::class);
    }

    public function receivingAccount(): BelongsTo
    {
        return $this->belongsTo(ReceivingAccount::class);
    }

    /** @return array{agreed: float, paid: float, balance: float} */
    public static function totals(): array
    {
        return [
            'agreed' => (float) static::sum('amount_agreed'),
            'paid' => (float) static::sum('amount_paid'),
            'balance' => (float) static::sum('balance'),
        ];
    }

    public function receivedAccountLabel(): string
    {
        return $this->receivingAccount?->name ?? ($this->received_account ?? '—');
    }

    public function receivedInLabel(): string
    {
        return $this->receivingAccount?->methodLabel()
            ?? config('payment_accounts.options.'.$this->received_in, $this->received_in ?? '—');
    }
}
