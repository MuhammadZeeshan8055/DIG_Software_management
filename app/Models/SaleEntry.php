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

    public function scopeForMonth(Builder $query, string $month): Builder
    {
        return $query->where('report_month', $month);
    }

    /**
     * @return array{entries: int, payable: float, received: float, profit: float, profit_filled: int}
     */
    public static function totalsForMonth(string $month): array
    {
        $base = static::query()->forMonth($month);

        return [
            'entries' => (clone $base)->count(),
            'payable' => (float) (clone $base)->sum('payable'),
            'received' => (float) (clone $base)->sum('received'),
            'profit' => (float) (clone $base)->whereNotNull('profit')->sum('profit'),
            'profit_filled' => (clone $base)->whereNotNull('profit')->count(),
        ];
    }
}
