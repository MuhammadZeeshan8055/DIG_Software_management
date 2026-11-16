<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UmrahSaleEntry extends Model
{
    protected $fillable = [
        'sale_date',
        'booking_id',
        'passenger_name',
        'passport_no',
        'airline',
        'total_pax',
        'air_ticket_pp',
        'visa_cost_pp',
        'makkah_hotel_pp',
        'madinah_hotel_pp',
        'ziarat_pp',
        'transport_pp',
        'other_expense_pp',
        'agent_profit_pp',
        'air_ticket_total',
        'visa_total',
        'makkah_hotel_total',
        'madinah_hotel_total',
        'ziarat_total',
        'transport_total',
        'other_expense_total',
        'agent_profit_total',
        'payable',
        'total_amount',
        'received',
        'remaining_amount',
        'profit',
        'loss',
        'report_month',
        'original_filename',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'total_pax' => 'integer',
            'air_ticket_pp' => 'decimal:2',
            'visa_cost_pp' => 'decimal:2',
            'makkah_hotel_pp' => 'decimal:2',
            'madinah_hotel_pp' => 'decimal:2',
            'ziarat_pp' => 'decimal:2',
            'transport_pp' => 'decimal:2',
            'other_expense_pp' => 'decimal:2',
            'agent_profit_pp' => 'decimal:2',
            'air_ticket_total' => 'decimal:2',
            'visa_total' => 'decimal:2',
            'makkah_hotel_total' => 'decimal:2',
            'madinah_hotel_total' => 'decimal:2',
            'ziarat_total' => 'decimal:2',
            'transport_total' => 'decimal:2',
            'other_expense_total' => 'decimal:2',
            'agent_profit_total' => 'decimal:2',
            'payable' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'received' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'profit' => 'decimal:2',
            'loss' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForMonth(Builder $query, string $month): Builder
    {
        return $query->where('report_month', $month);
    }

    /** Totals from imported Excel values (no recalculation). */
    public static function totalsForMonth(string $month): array
    {
        $q = static::query()->forMonth($month);

        return [
            'entries' => (clone $q)->count(),
            'payable' => (float) (clone $q)->sum('payable'),
            'total_amount' => (float) (clone $q)->sum('total_amount'),
            'received' => (float) (clone $q)->sum('received'),
            'remaining' => (float) (clone $q)->sum('remaining_amount'),
            'profit' => (float) (clone $q)->sum('profit'),
            'loss' => (float) (clone $q)->sum('loss'),
            'agent_profit' => (float) (clone $q)->sum('agent_profit_total'),
        ];
    }
}
