<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalarySlip extends Model
{
    protected $fillable = [
        'user_id',
        'month',
        'monthly_salary',
        'working_days',
        'absent_days',
        'unpaid_leave_days',
        'deduct_days',
        'daily_rate',
        'deduction',
        'bonus',
        'net_pay',
        'generated_by',
    ];

    protected function casts(): array
    {
        return [
            'monthly_salary' => 'decimal:2',
            'working_days' => 'integer',
            'absent_days' => 'decimal:2',
            'unpaid_leave_days' => 'decimal:2',
            'deduct_days' => 'decimal:2',
            'daily_rate' => 'decimal:2',
            'deduction' => 'decimal:2',
            'bonus' => 'decimal:2',
            'net_pay' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function monthLabel(): string
    {
        return \Carbon\Carbon::createFromFormat('Y-m', $this->month)->format('F Y');
    }
}
