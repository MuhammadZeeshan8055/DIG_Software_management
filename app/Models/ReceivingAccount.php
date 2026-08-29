<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReceivingAccount extends Model
{
    protected $fillable = [
        'method',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function paymentEntries(): HasMany
    {
        return $this->hasMany(PaymentEntry::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function methodLabel(): string
    {
        return config('payment_accounts.options.'.$this->method, $this->method);
    }

    /** @return array{id: int, method: string, name: string, type: string} */
    public function toPickerArray(): array
    {
        return [
            'id' => $this->id,
            'method' => $this->method,
            'name' => $this->name,
            'type' => $this->methodLabel(),
        ];
    }

    /** @return Collection<int, array{id: int, method: string, name: string, type: string}> */
    public static function pickerOptions(): Collection
    {
        return static::query()
            ->active()
            ->orderBy('name')
            ->get()
            ->map(fn (self $account) => $account->toPickerArray())
            ->values();
    }
}
