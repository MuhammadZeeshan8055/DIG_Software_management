<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoicePayment extends Model
{
    protected $fillable = [
        'invoice_id',
        'amount',
        'paid_at',
        'note',
        'user_id',
        'receiving_account_id',
        'received_in',
        'received_account',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (InvoicePayment $payment): void {
            if ($payment->receiving_account_id) {
                $account = ReceivingAccount::find($payment->receiving_account_id);

                if ($account) {
                    $payment->received_in = $account->method;
                    $payment->received_account = $account->name;
                }
            }
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function receivingAccount(): BelongsTo
    {
        return $this->belongsTo(ReceivingAccount::class);
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
