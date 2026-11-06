<?php

namespace App\Models;

use App\Services\TicketJourneyClassifier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

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
        'passenger_extras',
        'status',
        'confirmed_at',
        'verification_token',
    ];

    protected static function booted(): void
    {
        static::creating(function (TicketImport $import): void {
            if (empty($import->verification_token)) {
                $import->verification_token = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'flight_segments' => 'array',
            'passenger_extras' => 'array',
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

    /**
     * Split saved flights into outbound and return groups.
     */
    public function journeyGroups(): array
    {
        $classifier = app(TicketJourneyClassifier::class);

        return $classifier->split($this->flight_segments ?? []);
    }

    /**
     * All outbound flight legs (may include connections).
     */
    public function outboundSegments(): array
    {
        $groups = $this->journeyGroups();

        return $groups['outbound'];
    }

    /**
     * All return flight legs (may include connections).
     */
    public function returnSegments(): array
    {
        $groups = $this->journeyGroups();

        return $groups['return'];
    }

    /**
     * Build a simple summary for one journey (outbound or return).
     *
     * Uses the first leg for departure, and the last leg for arrival.
     * That way a connection like DMM -> DXB -> SKT shows as DMM -> SKT.
     */
    public function summariseJourney(array $legs): array
    {
        $legs = array_values($legs);

        // Empty journey
        if (count($legs) === 0) {
            return [
                'airline' => '—',
                'flight_numbers' => '—',
                'route' => '—',
                'departure_time' => '—',
                'departure_date' => '—',
                'arrival_time' => '—',
                'arrival_date' => '—',
                'departure_location' => '—',
                'arrival_location' => '—',
                'first' => null,
                'last' => null,
            ];
        }

        $first = $legs[0];
        $last = $legs[count($legs) - 1];

        // Collect airline names (unique, non-empty)
        $airlines = [];
        foreach ($legs as $leg) {
            $name = isset($leg['airline']) ? trim($leg['airline']) : '';
            if ($name !== '' && ! in_array($name, $airlines, true)) {
                $airlines[] = $name;
            }
        }

        // Collect flight numbers (non-empty)
        $flightNumbers = [];
        foreach ($legs as $leg) {
            $number = isset($leg['flight_number']) ? trim($leg['flight_number']) : '';
            if ($number !== '') {
                $flightNumbers[] = $number;
            }
        }

        $fromCode = $this->locationCode($first['departure_location'] ?? null);
        $toCode = $this->locationCode($last['arrival_location'] ?? null);

        if ($fromCode === '—' && $toCode === '—') {
            $route = '—';
        } else {
            $route = $fromCode.'-'.$toCode;
        }

        return [
            'airline' => count($airlines) === 0 ? '—' : implode(' / ', $airlines),
            'flight_numbers' => count($flightNumbers) === 0 ? '—' : implode(', ', $flightNumbers),
            'route' => $route,
            'departure_time' => $first['departure_time'] ?? '—',
            'departure_date' => $first['departure_date'] ?? '—',
            'arrival_time' => $last['arrival_time'] ?? '—',
            'arrival_date' => $last['arrival_date'] ?? '—',
            'departure_location' => $first['departure_location'] ?? '—',
            'arrival_location' => $last['arrival_location'] ?? '—',
            'first' => $first,
            'last' => $last,
        ];
    }

    public function documentSerial(): string
    {
        return 'DOC-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function ensureVerificationToken(): string
    {
        if (empty($this->verification_token)) {
            $this->forceFill([
                'verification_token' => (string) Str::uuid(),
            ])->save();
        }

        return (string) $this->verification_token;
    }

    public function verificationUrl(): string
    {
        return route('tickets.verify', $this->ensureVerificationToken(), absolute: true);
    }

    /**
     * QR encodes the public verification page URL.
     */
    public function qrPayload(): string
    {
        return $this->verificationUrl();
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

    /**
     * EXTRAS rows (seat / meal / baggage). Uses saved data, or reads from PDF text.
     *
     * @return array<int, array{passenger: string, segments: array<int, array<string, string>>}>
     */
    public function extrasRows(): array
    {
        $saved = $this->passenger_extras;

        if (is_array($saved) && $saved !== []) {
            return $saved;
        }

        $raw = trim((string) $this->raw_pdf_text);
        if ($raw === '') {
            return [];
        }

        return app(\App\Services\TicketPdfParser::class)->parseExtras($raw);
    }

    /**
     * Split saved passengers and ticket numbers into rows.
     * Example: name "A / B" + ticket "111 / 222" => two rows.
     *
     * @return array<int, array{name: string, ticket: string}>
     */
    public function passengerTicketRows(): array
    {
        $nameText = trim((string) $this->passenger_name);
        $ticketText = trim((string) $this->ticket_number);

        if ($nameText === '' && $ticketText === '') {
            return [];
        }

        $names = preg_split('/\s*\/\s*/', $nameText) ?: [];
        $tickets = preg_split('/\s*\/\s*/', $ticketText) ?: [];

        // Old imports may use comma between ticket numbers
        if (count($tickets) === 1 && str_contains($ticketText, ',')) {
            $tickets = preg_split('/\s*,\s*/', $ticketText) ?: [];
        }

        $names = array_values(array_filter(array_map('trim', $names), fn ($v) => $v !== ''));
        $tickets = array_values(array_filter(array_map('trim', $tickets), fn ($v) => $v !== ''));

        $count = max(count($names), count($tickets));
        $rows = [];

        for ($i = 0; $i < $count; $i++) {
            $rows[] = [
                'name' => $names[$i] ?? '',
                'ticket' => $tickets[$i] ?? '',
            ];
        }

        return $rows;
    }
}
