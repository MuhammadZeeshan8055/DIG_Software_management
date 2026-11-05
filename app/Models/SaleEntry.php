<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleEntry extends Model
{
    protected $fillable = [
        'sale_date',
        'passenger_name',
        'sector',
        'airline',
        'payable',
        'received',
        'via',
        'profit',
        'report_month',
        'original_filename',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'payable' => 'decimal:2',
            'received' => 'decimal:2',
            'profit' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Only rows for one report month, example: 2026-10
     */
    public function scopeForMonth(Builder $query, string $month): Builder
    {
        return $query->where('report_month', $month);
    }

    /**
     * Add up Payable, Received, and Profit for one month.
     * Profit = Received - Payable
     */
    public static function totalsForMonth(string $month): array
    {
        $query = static::query()->forMonth($month);

        $payable = (float) (clone $query)->sum('payable');
        $received = (float) (clone $query)->sum('received');
        $profit = round($received - $payable, 2);

        return [
            'entries' => (clone $query)->count(),
            'payable' => $payable,
            'received' => $received,
            'profit' => $profit,
        ];
    }
}
