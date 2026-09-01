<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TicketImport extends Model
{
    protected $fillable = [
        'user_id',
        'original_filename',
        'pdf_path',
        'raw_pdf_text',
        'agency_name',
        'agency_phone',
        'booking_reference',
        'agency_pnr',
        'passenger_name',
        'frequent_flyer',
        'ticket_number',
        'flight_segments',
        'status',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'flight_segments' => 'array',
            'confirmed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentEntry(): HasOne
    {
        return $this->hasOne(PaymentEntry::class);
    }

    public function routeLabel(): string
    {
        $segments = $this->flight_segments ?? [];

        if ($segments === []) {
            return '—';
        }

        $parts = [];

        foreach ($segments as $index => $segment) {
            if ($index === 0 && ! empty($segment['departure_location'])) {
                $parts[] = $segment['departure_location'];
            }

            if (! empty($segment['arrival_location'])) {
                $parts[] = $segment['arrival_location'];
            }
        }

        return $parts === [] ? '—' : implode(' → ', $parts);
    }

    public function flightNumbersLabel(): string
    {
        $numbers = array_filter(array_column($this->flight_segments ?? [], 'flight_number'));

        return $numbers === [] ? '—' : implode(', ', $numbers);
    }

    public function documentSerial(): string
    {
        return 'DOC-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function locationCode(?string $location): string
    {
        if (! $location) {
            return '—';
        }

        if (preg_match('/\(([A-Z]{3})\)/', $location, $matches)) {
            return $matches[1];
        }

        if (preg_match('/\b([A-Z]{3})\b/', $location, $matches)) {
            return $matches[1];
        }

        return $location;
    }

    /** @param  array<string, string>  $segment */
    public function segmentRouteCode(array $segment): string
    {
        $from = $this->locationCode($segment['departure_location'] ?? null);
        $to = $this->locationCode($segment['arrival_location'] ?? null);

        if ($from === '—' && $to === '—') {
            return '—';
        }

        return "{$from}-{$to}";
    }
}
