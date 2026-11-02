<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class HotelVoucher extends Model
{
    protected $fillable = [
        'voucher_number',
        'verification_token',
        'issue_date',
        'status',
        'booking_reference',
        'family_head',
        'group_number',
        'agent_customer',
        'package_program',
        'country',
        'visa_type',
        'service_scope',
        'guests',
        'stays',
        'flights',
        'transport',
        'ksa_helpline',
        'pakistan_helpline',
        'prepared_by',
        'special_instructions',
        'user_id',
    ];

    protected static function booted(): void
    {
        // Every voucher gets a unique token for the public QR verify link
        static::creating(function (HotelVoucher $voucher): void {
            if (empty($voucher->verification_token)) {
                $voucher->verification_token = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'guests' => 'array',
            'stays' => 'array',
            'flights' => 'array',
            'transport' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── QR / public verify ──────────────────────────────────────────

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
        return route('hotel-vouchers.verify', $this->ensureVerificationToken(), absolute: true);
    }

    public function qrPayload(): string
    {
        return $this->verificationUrl();
    }

    // ── Display helpers (used in document / list) ───────────────────

    /** Empty values become an em dash for nice printing. */
    public function show(mixed $value): string
    {
        $text = trim((string) ($value ?? ''));

        return ($text === '' || $text === '-') ? '—' : $text;
    }

    /** Format a date like 10 Nov 2026. */
    public function showDate(mixed $value): string
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '' || $text === '-') {
            return '—';
        }

        try {
            return Carbon::parse($text)->format('d M Y');
        } catch (\Throwable) {
            return $this->show($text);
        }
    }

    /** Format date + time like 10 Nov 2026, 23:30. */
    public function showDateTime(mixed $value): string
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '' || $text === '-') {
            return '—';
        }

        try {
            return Carbon::parse($text)->format('d M Y, H:i');
        } catch (\Throwable) {
            return $this->show($text);
        }
    }

    /** Nights = check-out minus check-in (falls back to stored nights). */
    public function showNights(array $stay): string
    {
        $checkIn = trim((string) ($stay['check_in'] ?? ''));
        $checkOut = trim((string) ($stay['check_out'] ?? ''));

        if ($checkIn !== '' && $checkOut !== '') {
            try {
                $in = Carbon::parse($checkIn)->startOfDay();
                $out = Carbon::parse($checkOut)->startOfDay();
                $nights = (int) $in->diffInDays($out, false);

                return (string) max(0, $nights);
            } catch (\Throwable) {
                // use stored value below
            }
        }

        $stored = $stay['nights'] ?? null;

        return ($stored === '' || $stored === null) ? '—' : (string) $stored;
    }

    public function flightSector(array $flight): string
    {
        $sector = trim((string) ($flight['sector_code'] ?? ''));
        if ($sector !== '') {
            return $sector;
        }

        $origin = trim((string) ($flight['origin'] ?? ''));
        $destination = trim((string) ($flight['destination'] ?? ''));
        if ($origin === '' || $destination === '') {
            return '—';
        }

        return strtoupper($origin.'-'.$destination);
    }

    // ── Counts / labels ─────────────────────────────────────────────

    public static function nextNumber(): string
    {
        $latest = static::query()
            ->where('voucher_number', 'like', 'DIG-HV-%')
            ->orderByDesc('id')
            ->value('voucher_number');

        $next = 1;
        if ($latest && preg_match('/DIG-HV-(\d+)/', $latest, $matches)) {
            $next = ((int) $matches[1]) + 1;
        }

        return 'DIG-HV-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function paxCount(): int
    {
        return count($this->guests ?? []);
    }

    public function bedsYesCount(): int
    {
        $count = 0;
        foreach ($this->guests ?? [] as $guest) {
            if (strcasecmp((string) ($guest['bed'] ?? ''), 'Yes') === 0) {
                $count++;
            }
        }

        return $count;
    }

    public function paxBedsLabel(): string
    {
        return $this->paxCount().' / '.$this->bedsYesCount();
    }

    /** Short hotel lines for the list table. */
    public function staysSummaryLines(): array
    {
        $lines = [];

        foreach ($this->stays ?? [] as $stay) {
            $city = trim((string) ($stay['city'] ?? ''));
            $hotel = trim((string) ($stay['hotel'] ?? ''));
            if ($city === '' && $hotel === '') {
                continue;
            }

            $line = trim(($city !== '' ? $city.': ' : '').$hotel);
            $nights = (int) ($stay['nights'] ?? 0);
            if ($nights > 0) {
                $line .= ' ('.$nights.'N)';
            }

            $lines[] = $line;
        }

        return $lines;
    }

    public function totalNights(): int
    {
        $total = 0;
        foreach ($this->stays ?? [] as $stay) {
            $total += (int) ($stay['nights'] ?? 0);
        }

        return $total;
    }

    public function statusBadgeClass(): string
    {
        return $this->status === 'issued'
            ? 'payment-badge payment-badge--paid'
            : 'payment-badge payment-badge--draft';
    }

    public function statusLabel(): string
    {
        return $this->status === 'issued' ? 'Issued' : 'Draft';
    }
}
