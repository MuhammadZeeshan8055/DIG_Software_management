<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'verification_token',
        'invoice_date',
        'due_date',
        'status',
        'service_category',
        'package_label',
        'customer_name',
        'customer_phone',
        'customer_email',
        'customer_address',
        'cnic_passport',
        'reference_number',
        'subtotal',
        'tax_percent',
        'tax_amount',
        'total_amount',
        'paid_amount',
        'balance',
        'user_id',
        'approved_at',
        'approved_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice): void {
            if (empty($invoice->verification_token)) {
                $invoice->verification_token = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'approved_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'tax_percent' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance' => 'decimal:2',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class)->orderByDesc('paid_at');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    public function approve(User $user): void
    {
        $this->update([
            'approved_at' => now(),
            'approved_by' => $user->id,
        ]);
    }

    public function scopeApproved($query)
    {
        return $query->whereNotNull('approved_at');
    }

    public static function nextNumber(): string
    {
        $prefix = config('invoice.number_prefix', 'INV-');
        $last = static::query()
            ->where('invoice_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('invoice_number');

        $next = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $next = ((int) $m[1]) + 1;
        }

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function recalculateTotals(): void
    {
        $subtotal = (float) $this->items()->sum('amount');
        $taxPercent = (float) $this->tax_percent;
        $taxAmount = round($subtotal * ($taxPercent / 100), 2);
        $total = round($subtotal + $taxAmount, 2);
        $paid = (float) $this->payments()->sum('amount');
        $balance = round($total - $paid, 2);

        $status = $this->status;
        if ($balance <= 0 && $total > 0) {
            $status = 'paid';
        } elseif ($this->status === 'paid' && $balance > 0) {
            $status = 'pending';
        }

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $total,
            'paid_amount' => $paid,
            'balance' => max($balance, 0),
            'status' => $status,
        ]);
    }

    public function statusLabel(): string
    {
        return $this->paymentStatusLabel();
    }

    /** Effective payment status for badges: paid | half | pending | draft */
    public function paymentStatusKey(): string
    {
        $paid = (float) $this->paid_amount;
        $total = (float) $this->total_amount;
        $balance = (float) $this->balance;

        if ($total > 0 && $balance <= 0) {
            return 'paid';
        }

        if ($paid > 0 && $balance > 0) {
            return 'half';
        }

        if ($this->status === 'draft') {
            return 'draft';
        }

        return 'pending';
    }

    public function paymentStatusLabel(): string
    {
        return match ($this->paymentStatusKey()) {
            'paid' => 'PAID',
            'half' => 'HALF RECEIVE',
            'draft' => 'DRAFT',
            default => 'PENDING',
        };
    }

    public function paymentBadgeClass(): string
    {
        return match ($this->paymentStatusKey()) {
            'paid' => 'payment-badge payment-badge--paid',
            'half' => 'payment-badge payment-badge--half',
            'draft' => 'payment-badge payment-badge--draft',
            default => 'payment-badge payment-badge--pending',
        };
    }

    public function categoryLabel(): string
    {
        return config('invoice.categories.'.$this->service_category, $this->service_category);
    }

    public function verificationUrl(): string
    {
        return route('invoices.verify', $this->verification_token, absolute: true);
    }
}