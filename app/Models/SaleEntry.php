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
        'gross_profit',
        'loss',
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
            'gross_profit' => 'decimal:2',
            'loss' => 'decimal:2',
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

    /**
     * Totals aligned with the Monthly Air Ticketing Register footer.
     *
     * @return array{
     *     entries: int,
     *     payable: float,
     *     received: float,
     *     gross_profit: float,
     *     loss: float,
     *     profit: float
     * }
     */
    public static function totalsForMonth(string $month): array
    {
        $base = static::query()->forMonth($month);

        return [
            'entries' => (clone $base)->count(),
            'payable' => (float) (clone $base)->sum('payable'),
            'received' => (float) (clone $base)->sum('received'),
            'gross_profit' => (float) (clone $base)->sum('gross_profit'),
            'loss' => (float) (clone $base)->sum('loss'),
            'profit' => (float) (clone $base)->sum('profit'),
        ];
    }
}
