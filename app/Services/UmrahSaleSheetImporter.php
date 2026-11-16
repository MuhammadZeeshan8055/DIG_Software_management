<?php

namespace App\Services;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Read Umrah Ledger Sales Report Excel → list of rows.
 * All money values (including Profit / Loss) come from the file.
 */
class UmrahSaleSheetImporter
{
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

            if ($passenger === '' || $this->isBlank($row) || $this->isFooterLabel($passenger)) {
                continue;
            }

            $saleDate = $this->parseDate($this->raw($row, $col['date'] ?? null)) ?? $lastDate;

            if ($saleDate === null) {
                continue;
            }

            $lastDate = $saleDate;

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

                'payable' => $this->money($row, $col['payable'] ?? null),
                'total_amount' => $this->money($row, $col['total_amount'] ?? null),
                'received' => $this->money($row, $col['received'] ?? null),
                'remaining_amount' => $this->money($row, $col['remaining_amount'] ?? null),
                'profit' => $this->money($row, $col['profit'] ?? null),
                'loss' => $this->money($row, $col['loss'] ?? null),

                'air_ticket_total' => $this->money($row, $col['air_ticket_total'] ?? null),
                'visa_total' => $this->money($row, $col['visa_total'] ?? null),
                'makkah_hotel_total' => $this->money($row, $col['makkah_hotel_total'] ?? null),
                'madinah_hotel_total' => $this->money($row, $col['madinah_hotel_total'] ?? null),
                'ziarat_total' => $this->money($row, $col['ziarat_total'] ?? null),
                'transport_total' => $this->money($row, $col['transport_total'] ?? null),
                'other_expense_total' => $this->money($row, $col['other_expense_total'] ?? null),
                'agent_profit_total' => $this->money($row, $col['agent_profit_total'] ?? null),
            ];
        }

        return $entries;
    }

    private function loadRows(string $path): array
    {
        $book = IOFactory::load($path);
        $sheet = $book->getSheetByName('Ledger')
            ?? $book->getSheetByName('Sheet1')
            ?? $book->getActiveSheet();

        return $sheet->toArray(null, true, false, false);
    }

    private function findHeaderRow(array $rows): ?int
    {
        foreach ($rows as $i => $row) {
            $line = strtoupper(implode(' ', array_map(fn ($v) => trim((string) $v), $row)));

            $hasDate = str_contains($line, 'DATE');
            $hasPerson = str_contains($line, 'HEAD') || str_contains($line, 'PERSON') || str_contains($line, 'PASSENGER');
            $hasMoney = str_contains($line, 'COST PER PERSON') || str_contains($line, 'RECEIVED') || str_contains($line, 'TOTAL AMOUNT');

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

            $isLineTotal = str_contains($name, 'TOTAL')
                && ! str_contains($name, 'TOTAL PAX')
                && ! str_contains($name, 'TOTAL AMOUNT');

            if ($name === 'DATE') {
                $columns['date'] = $index;
            } elseif ($name === 'ID') {
                $columns['booking_id'] = $index;
            } elseif (str_contains($name, 'HEAD') || str_contains($name, 'PASSENGER') || $name === 'PERSON') {
                $columns['passenger_name'] = $index;
            } elseif (str_contains($name, 'PASSPORT')) {
                $columns['passport_no'] = $index;
            } elseif (str_contains($name, 'TOTAL PAX') || $name === 'PAX') {
                $columns['total_pax'] = $index;
            } elseif ($name === 'AIRLINE' || str_contains($name, 'AIRLINE')) {
                $columns['airline'] = $index;
            } elseif ($name === 'COST PER PERSON') {
                $columns['payable'] = $index;
            } elseif (str_contains($name, 'TOTAL AMOUNT')) {
                $columns['total_amount'] = $index;
            } elseif (str_contains($name, 'RECEIVED')) {
                $columns['received'] = $index;
            } elseif (str_contains($name, 'REMAINING')) {
                $columns['remaining_amount'] = $index;
            } elseif ($name === 'PROFIT') {
                $columns['profit'] = $index;
            } elseif ($name === 'LOSS') {
                $columns['loss'] = $index;
            } elseif ($isLineTotal && str_contains($name, 'AIR TICKET')) {
                $columns['air_ticket_total'] = $index;
            } elseif ($isLineTotal && str_contains($name, 'VISA')) {
                $columns['visa_total'] = $index;
            } elseif ($isLineTotal && str_contains($name, 'MAKKAH')) {
                $columns['makkah_hotel_total'] = $index;
            } elseif ($isLineTotal && str_contains($name, 'MADINAH')) {
                $columns['madinah_hotel_total'] = $index;
            } elseif ($isLineTotal && str_contains($name, 'ZIARAT')) {
                $columns['ziarat_total'] = $index;
            } elseif ($isLineTotal && str_contains($name, 'TRANSPORT')) {
                $columns['transport_total'] = $index;
            } elseif ($isLineTotal && str_contains($name, 'OTHER')) {
                $columns['other_expense_total'] = $index;
            } elseif ($isLineTotal && str_contains($name, 'AGENT PROFIT')) {
                $columns['agent_profit_total'] = $index;
            } elseif (! $isLineTotal && str_contains($name, 'AIR TICKET')) {
                $columns['air_ticket_pp'] = $index;
            } elseif (! $isLineTotal && str_contains($name, 'VISA')) {
                $columns['visa_cost_pp'] = $index;
            } elseif (! $isLineTotal && str_contains($name, 'ZIARAT')) {
                $columns['ziarat_pp'] = $index;
            } elseif (! $isLineTotal && str_contains($name, 'MAKKAH')) {
                $columns['makkah_hotel_pp'] = $index;
            } elseif (! $isLineTotal && str_contains($name, 'MADINAH')) {
                $columns['madinah_hotel_pp'] = $index;
            } elseif (! $isLineTotal && str_contains($name, 'TRANSPORT')) {
                $columns['transport_pp'] = $index;
            } elseif (! $isLineTotal && (str_contains($name, 'OTHER EXPENSE') || str_contains($name, 'OTHER'))) {
                $columns['other_expense_pp'] = $index;
            } elseif (! $isLineTotal && str_contains($name, 'AGENT PROFIT')) {
                $columns['agent_profit_pp'] = $index;
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

    private function isFooterLabel(string $text): bool
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
        // Prefer raw numeric cell (handles Excel negatives correctly)
        $raw = $this->raw($row, $index);

        if (is_numeric($raw)) {
            return round((float) $raw, 2);
        }

        $value = $this->text($row, $index);

        if ($value === '' || $value === '-') {
            return 0.0;
        }

        $negative = str_contains($value, '(') && str_contains($value, ')');
        $clean = preg_replace('/[^\d.\-]/', '', str_replace(',', '', $value)) ?: '0';
        $amount = round((float) $clean, 2);

        return $negative ? -abs($amount) : $amount;
    }

    private function pax(array $row, ?int $index): ?int
    {
        $raw = $this->raw($row, $index);

        if (is_numeric($raw)) {
            return (int) $raw;
        }

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
                // try text
            }
        }

        $text = trim((string) $value);

        if ($this->isFooterLabel($text)) {
            return null;
        }

        foreach (['d-M-Y', 'j-M-Y', 'd-M-y', 'd/m/Y', 'Y-m-d', 'd-m-Y'] as $format) {
            try {
                $parsed = Carbon::createFromFormat('!'.$format, $text);

                if ($parsed !== false) {
                    return $parsed->toDateString();
                }
            } catch (\Throwable $e) {
                // next
            }
        }

        try {
            return Carbon::parse($text)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
