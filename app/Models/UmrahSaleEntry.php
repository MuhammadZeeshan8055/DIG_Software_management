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
        'payable',
        'received',
        'profit',
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
            'payable' => 'decimal:2',
            'received' => 'decimal:2',
            'profit' => 'decimal:2',
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

    /** Profit = Received − Cost Per Person */
    public static function totalsForMonth(string $month): array
    {
        $q = static::query()->forMonth($month);
        $payable = (float) (clone $q)->sum('payable');
        $received = (float) (clone $q)->sum('received');

        return [
            'entries' => (clone $q)->count(),
            'payable' => $payable,
            'received' => $received,
            'profit' => round($received - $payable, 2),
            'agent_profit' => (float) (clone $q)->sum('agent_profit_pp'),
        ];
    }
}
