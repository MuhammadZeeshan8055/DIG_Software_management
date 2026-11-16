<?php

namespace App\Services;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Read DIG Umrah Accounts Ledger Excel → list of rows.
 * Payable = Cost Per Person
 * Profit  = Received − Payable
 */
class UmrahSaleSheetImporter
{
    // Header keyword → field name (order matters for overlapping words)
    private const HEADER_MAP = [
        'DATE' => 'date',
        'ID' => 'booking_id',
        'HEAD' => 'passenger_name',
        'PASSENGER' => 'passenger_name',
        'PASSPORT' => 'passport_no',
        'TOTAL PAX' => 'total_pax',
        'AIRLINE' => 'airline',
        'AIR TICKET' => 'air_ticket_pp',
        'VISA' => 'visa_cost_pp',
        'ZIARAT' => 'ziarat_pp',
        'MAKKAH' => 'makkah_hotel_pp',
        'MADINAH' => 'madinah_hotel_pp',
        'TRANSPORT' => 'transport_pp',
        'OTHER' => 'other_expense_pp',
        'AGENT PROFIT' => 'agent_profit_pp',
        'COST PER PERSON' => 'payable',
        'RECEIVED' => 'received',
    ];

    public function parse(string $path): array
    {
        $rows = $this->loadRows($path);

        if ($rows === []) {
            return [];
        }

        $headerIndex = $this->findHeaderRow($rows);

        if ($headerIndex === null) {
            throw new \RuntimeException('Could not find header row (Date, Head / Person, …).');
        }

        $col = $this->mapColumns($rows[$headerIndex]);

        if (! isset($col['date'], $col['passenger_name'])) {
            throw new \RuntimeException('Sheet must include Date and Head / Person columns.');
        }

        $entries = [];
        $lastDate = null;

        for ($i = $headerIndex + 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $passenger = $this->text($row, $col['passenger_name'] ?? null);

            if ($passenger === '' || $this->isBlank($row) || $this->isTotalLabel($passenger)) {
                continue;
            }

            $saleDate = $this->parseDate($this->raw($row, $col['date'] ?? null)) ?? $lastDate;

            if ($saleDate === null) {
                continue;
            }

            $lastDate = $saleDate;
            $payable = $this->money($row, $col['payable'] ?? null);
            $received = $this->money($row, $col['received'] ?? null);

            $entries[] = [
                'sale_date' => $saleDate,
                'booking_id' => $this->optional($row, $col['booking_id'] ?? null),
                'passenger_name' => $passenger,
                'passport_no' => $this->optional($row, $col['passport_no'] ?? null),
                'airline' => $this->optional($row, $col['airline'] ?? null),
                'total_pax' => $this->pax($row, $col['total_pax'] ?? null),
                'air_ticket_pp' => $this->money($row, $col['air_ticket_pp'] ?? null),
                'visa_cost_pp' => $this->money($row, $col['visa_cost_pp'] ?? null),
                'makkah_hotel_pp' => $this->money($row, $col['makkah_hotel_pp'] ?? null),
                'madinah_hotel_pp' => $this->money($row, $col['madinah_hotel_pp'] ?? null),
                'ziarat_pp' => $this->money($row, $col['ziarat_pp'] ?? null),
                'transport_pp' => $this->money($row, $col['transport_pp'] ?? null),
                'other_expense_pp' => $this->money($row, $col['other_expense_pp'] ?? null),
                'agent_profit_pp' => $this->money($row, $col['agent_profit_pp'] ?? null),
                'payable' => $payable,
                'received' => $received,
                'profit' => round($received - $payable, 2),
            ];
        }

        return $entries;
    }

    private function loadRows(string $path): array
    {
        $book = IOFactory::load($path);
        $sheet = $book->getSheetByName('Ledger') ?? $book->getActiveSheet();

        return $sheet->toArray(null, true, false, false);
    }

    private function findHeaderRow(array $rows): ?int
    {
        foreach ($rows as $i => $row) {
            $line = strtoupper(implode(' ', array_map(fn ($v) => trim((string) $v), $row)));

            $hasDate = str_contains($line, 'DATE');
            $hasPerson = str_contains($line, 'HEAD') || str_contains($line, 'PERSON') || str_contains($line, 'PASSENGER');
            $hasMoney = str_contains($line, 'COST PER PERSON') || str_contains($line, 'RECEIVED');

            if ($hasDate && $hasPerson && $hasMoney) {
                return $i;
            }
        }

        return null;
    }

    private function mapColumns(array $header): array
    {
        $columns = [];

        foreach ($header as $index => $label) {
            $name = $this->cleanHeader((string) $label);

            if ($name === '' || in_array($name, ['S NO', 'S.NO', 'SNO'], true)) {
                continue;
            }

            // Skip TOTAL columns, keep "TOTAL PAX" / "TOTAL AMOUNT"
            if (str_contains($name, 'TOTAL') && ! str_contains($name, 'TOTAL PAX') && ! str_contains($name, 'TOTAL AMOUNT')) {
                continue;
            }

            foreach (self::HEADER_MAP as $keyword => $field) {
                $match = $keyword === 'ID' || $keyword === 'COST PER PERSON'
                    ? $name === $keyword
                    : str_contains($name, $keyword);

                if ($match) {
                    $columns[$field] = $index;
                    break;
                }
            }
        }

        return $columns;
    }

    private function cleanHeader(string $label): string
    {
        $name = strtoupper(trim($label));
        $name = str_replace(["\r", "\n", '/', '\\', '_', '(', ')'], ' ', $name);

        return trim(preg_replace('/\s+/', ' ', $name) ?? '');
    }

    private function isBlank(array $row): bool
    {
        foreach ($row as $value) {
            $text = trim((string) ($value ?? ''));

            if ($text !== '' && strtoupper($text) !== '#REF!') {
                return false;
            }
        }

        return true;
    }

    private function isTotalLabel(string $text): bool
    {
        $u = strtoupper(trim($text));

        if ($u === '') {
            return false;
        }

        return $u === 'TOTAL'
            || $u === 'BALANCE'
            || str_ends_with($u, ' TOTAL')
            || str_starts_with($u, 'TOTAL ')
            || str_starts_with($u, 'GRAND TOTAL')
            || str_starts_with($u, 'BALANCE ');
    }

    private function text(array $row, ?int $index): string
    {
        if ($index === null || ! array_key_exists($index, $row) || $row[$index] === null) {
            return '';
        }

        $text = trim((string) $row[$index]);
        $upper = strtoupper($text);

        return ($upper === '#REF!' || $upper === '#N/A') ? '' : $text;
    }

    private function raw(array $row, ?int $index): mixed
    {
        return ($index !== null && array_key_exists($index, $row)) ? $row[$index] : null;
    }

    private function optional(array $row, ?int $index): ?string
    {
        $value = $this->text($row, $index);

        return $value === '' ? null : $value;
    }

    private function money(array $row, ?int $index): float
    {
        $value = $this->text($row, $index);

        if ($value === '' || $value === '-') {
            return 0.0;
        }

        $clean = preg_replace('/[^\d.\-]/', '', str_replace(',', '', $value)) ?: '0';

        return round((float) $clean, 2);
    }

    private function pax(array $row, ?int $index): ?int
    {
        $value = $this->text($row, $index);

        if ($value === '') {
            return null;
        }

        $clean = preg_replace('/[^\d]/', '', $value);

        return ($clean === null || $clean === '') ? null : (int) $clean;
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::parse($value)->toDateString();
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateString();
            } catch (\Throwable $e) {
                // try text below
            }
        }

        $text = trim((string) $value);

        if ($this->isTotalLabel($text)) {
            return null;
        }

        foreach (['d-M-Y', 'j-M-Y', 'd-M-y', 'd/m/Y', 'Y-m-d', 'd-m-Y'] as $format) {
            try {
                $parsed = Carbon::createFromFormat('!'.$format, $text);

                if ($parsed !== false) {
                    return $parsed->toDateString();
                }
            } catch (\Throwable $e) {
                // next format
            }
        }

        try {
            return Carbon::parse($text)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
