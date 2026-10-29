<?php

namespace App\Services;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class SaleSheetImporter
{
    /**
     * Parse the Dhothar Monthly Air Ticketing Register (Excel / CSV / Excel-HTML .xls).
     *
     * Columns: S#, Date, Passenger Name, PNR, Airline, Sector,
     * Fare / Person, Pax, Total Fare / Sales, Profit, Loss, Net Profit/Loss, Grand Total
     *
     * Mapping:
     * - payable  ← Total Fare / Sales (fallback: Fare / Person)
     * - received ← Grand Total
     * - via      ← PNR
     * - profit   ← Net Profit/Loss (fallback: Profit − Loss)
     *
     * @return array<int, array{
     *     sale_date: string,
     *     passenger_name: string,
     *     sector: ?string,
     *     airline: ?string,
     *     payable: float,
     *     received: float,
     *     via: ?string,
     *     gross_profit: float,
     *     loss: float,
     *     profit: float
     * }>
     */
    public function parse(string $path): array
    {
        $rows = $this->loadRows($path);

        if ($rows === []) {
            return [];
        }

        $headerIndex = $this->findHeaderRow($rows);

        if ($headerIndex === null) {
            throw new \RuntimeException('Could not find header row (Date, Passenger Name, …).');
        }

        $map = $this->mapColumns($rows[$headerIndex]);

        if (! isset($map['date'], $map['passenger_name'])) {
            throw new \RuntimeException('Sheet must include Date and Passenger Name columns.');
        }

        $entries = [];

        for ($i = $headerIndex + 1; $i < count($rows); $i++) {
            $row = $rows[$i];

            if ($this->isFooterOrEmptyRow($row)) {
                continue;
            }

            $passenger = $this->cell($row, $map['passenger_name']);

            if ($passenger === '' || $this->looksLikeTotalLabel($passenger)) {
                continue;
            }

            $saleDate = $this->parseDate($this->rawCell($row, $map['date']));

            if (! $saleDate) {
                continue;
            }

            $payable = $this->resolvePayable($row, $map);
            $received = $this->resolveReceived($row, $map, $payable);
            $amounts = $this->resolveProfitAmounts($row, $map);

            $entries[] = [
                'sale_date' => $saleDate,
                'passenger_name' => $passenger,
                'sector' => $this->nullableCell($row, $map['sector'] ?? null),
                'airline' => $this->nullableCell($row, $map['airline'] ?? null),
                'payable' => $payable,
                'received' => $received,
                'via' => $this->nullableCell($row, $map['via'] ?? null),
                'gross_profit' => $amounts['gross_profit'],
                'loss' => $amounts['loss'],
                'profit' => $amounts['net_profit'],
            ];
        }

        return $entries;
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function loadRows(string $path): array
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $probe = $this->fileProbe($path);

        // Dhothar register is often Excel-HTML saved as .xls (MIME text/html).
        if ($this->looksLikeHtmlSpreadsheet($probe) || in_array($ext, ['html', 'htm'], true)) {
            $spreadsheet = IOFactory::createReader('Html')->load($path);
        } elseif (in_array($ext, ['csv', 'txt'], true)) {
            // Force comma CSV — auto-detect breaks on title rows that contain spaces.
            $reader = IOFactory::createReader('Csv');
            $reader->setDelimiter(',');
            $reader->setEnclosure('"');
            $reader->setInputEncoding('UTF-8');
            $spreadsheet = $reader->load($path);
        } else {
            $spreadsheet = IOFactory::load($path);
        }

        return $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
    }

    private function fileProbe(string $path): string
    {
        $raw = @file_get_contents($path, false, null, 0, 512);

        if ($raw === false || $raw === '') {
            return '';
        }

        // Strip UTF-8 BOM used by Excel HTML exports.
        if (str_starts_with($raw, "\xEF\xBB\xBF")) {
            $raw = substr($raw, 3);
        }

        return $raw;
    }

    private function looksLikeHtmlSpreadsheet(string $probe): bool
    {
        if ($probe === '') {
            return false;
        }

        $lower = strtolower($probe);

        return str_contains($lower, '<html')
            || str_contains($lower, '<table')
            || str_contains($lower, 'xmlns:x="urn:schemas-microsoft-com:office:excel"');
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function findHeaderRow(array $rows): ?int
    {
        foreach ($rows as $index => $row) {
            $joined = strtoupper(implode(' ', array_map(fn ($v) => trim((string) $v), $row)));

            $hasDate = str_contains($joined, 'DATE');
            $hasPassenger = str_contains($joined, 'PASSENGER');
            $hasMoney = str_contains($joined, 'PAYABLE')
                || str_contains($joined, 'RECEIVED')
                || str_contains($joined, 'SALES')
                || str_contains($joined, 'FARE')
                || str_contains($joined, 'GRAND TOTAL')
                || str_contains($joined, 'PROFIT');

            if ($hasDate && $hasPassenger && $hasMoney) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param  array<int, mixed>  $header
     * @return array<string, int>
     */
    private function mapColumns(array $header): array
    {
        $map = [];

        foreach ($header as $index => $label) {
            $key = $this->normalizeHeader((string) $label);

            if ($key === '' || $key === 'S#' || $key === 'S' || $key === 'SR' || $key === 'NO') {
                continue;
            }

            if ($key === 'DATE' || $key === 'TRAVEL DATE') {
                $map['date'] = $index;
            } elseif (str_contains($key, 'PASSENGER')) {
                $map['passenger_name'] = $index;
            } elseif ($key === 'SECTOR' || str_contains($key, 'SECTOR')) {
                $map['sector'] ??= $index;
            } elseif ($key === 'AIRLINE' || str_contains($key, 'AIRLINE')) {
                $map['airline'] ??= $index;
            } elseif ($key === 'PAYABLE') {
                $map['payable'] = $index;
            } elseif ($key === 'RECEIVED') {
                $map['received'] = $index;
            } elseif (str_contains($key, 'GRAND TOTAL')) {
                $map['grand_total'] = $index;
            } elseif (str_contains($key, 'TOTAL FARE') || (str_contains($key, 'SALES') && str_contains($key, 'FARE'))) {
                $map['sales_total'] ??= $index;
            } elseif (str_contains($key, 'SALES') && ! str_contains($key, 'PROFIT')) {
                $map['sales_total'] ??= $index;
            } elseif (str_contains($key, 'FARE') && str_contains($key, 'PERSON')) {
                $map['fare_person'] = $index;
            } elseif ($key === 'PAX' || $key === 'PAXS' || $key === 'QTY') {
                $map['pax'] = $index;
            } elseif (str_contains($key, 'NET') && str_contains($key, 'PROFIT')) {
                // Net Profit/Loss (PKR)
                $map['net_profit'] = $index;
            } elseif ($key === 'LOSS' || str_starts_with($key, 'LOSS ')) {
                $map['loss'] = $index;
            } elseif ($key === 'PROFIT' || (str_contains($key, 'PROFIT') && ! str_contains($key, 'LOSS'))) {
                // Profit (PKR) — not Net Profit/Loss
                $map['profit'] ??= $index;
            } elseif ($key === 'VIA' || $key === 'PNR' || str_contains($key, 'PNR')) {
                $map['via'] ??= $index;
            }
        }

        return $map;
    }

    private function normalizeHeader(string $label): string
    {
        $key = strtoupper(trim(preg_replace('/\s+/', ' ', $label) ?? ''));
        $key = str_replace(['/', '\\', '_'], ' ', $key);
        $key = trim(preg_replace('/\s+/', ' ', $key) ?? '');

        return $key;
    }

    /**
     * @param  array<int, mixed>  $row
     * @param  array<string, int>  $map
     */
    private function resolvePayable(array $row, array $map): float
    {
        if (isset($map['payable'])) {
            return $this->parseMoney($this->cell($row, $map['payable']));
        }

        if (isset($map['sales_total'])) {
            return $this->parseMoney($this->cell($row, $map['sales_total']));
        }

        if (isset($map['fare_person'])) {
            return $this->parseMoney($this->cell($row, $map['fare_person']));
        }

        return 0.0;
    }

    /**
     * @param  array<int, mixed>  $row
     * @param  array<string, int>  $map
     */
    private function resolveReceived(array $row, array $map, float $payable): float
    {
        if (isset($map['received'])) {
            return $this->parseMoney($this->cell($row, $map['received']));
        }

        if (isset($map['grand_total'])) {
            return $this->parseMoney($this->cell($row, $map['grand_total']));
        }

        if (isset($map['sales_total'])) {
            return $this->parseMoney($this->cell($row, $map['sales_total']));
        }

        return $payable;
    }

    /**
     * Map register Profit / Loss / Net Profit/Loss columns.
     *
     * @param  array<int, mixed>  $row
     * @param  array<string, int>  $map
     * @return array{gross_profit: float, loss: float, net_profit: float}
     */
    private function resolveProfitAmounts(array $row, array $map): array
    {
        $gross = isset($map['profit']) ? $this->parseMoney($this->cell($row, $map['profit'])) : 0.0;
        $loss = isset($map['loss']) ? $this->parseMoney($this->cell($row, $map['loss'])) : 0.0;

        if (isset($map['net_profit'])) {
            $net = $this->parseMoney($this->cell($row, $map['net_profit']));
        } else {
            $net = round($gross - $loss, 2);
        }

        return [
            'gross_profit' => $gross,
            'loss' => $loss,
            'net_profit' => $net,
        ];
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function isFooterOrEmptyRow(array $row): bool
    {
        $joined = strtoupper(trim(implode(' ', array_map(
            fn ($v) => trim((string) ($v ?? '')),
            $row
        ))));

        if ($joined === '') {
            return true;
        }

        return $this->looksLikeTotalLabel($joined);
    }

    private function looksLikeTotalLabel(string $text): bool
    {
        $upper = strtoupper(trim($text));

        return str_contains($upper, 'TOTAL')
            || str_starts_with($upper, 'GRAND')
            || str_contains($upper, 'SUB TOTAL');
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function cell(array $row, ?int $index): string
    {
        if ($index === null || ! array_key_exists($index, $row) || $row[$index] === null) {
            return '';
        }

        return trim((string) $row[$index]);
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function rawCell(array $row, ?int $index): mixed
    {
        if ($index === null || ! array_key_exists($index, $row)) {
            return null;
        }

        return $row[$index];
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function nullableCell(array $row, ?int $index): ?string
    {
        $value = $this->cell($row, $index);

        return $value === '' ? null : $value;
    }

    private function parseMoney(string $value): float
    {
        $clean = preg_replace('/[^\d.\-]/', '', str_replace(',', '', $value)) ?? '0';

        return round((float) $clean, 2);
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
            } catch (\Throwable) {
                // fall through
            }
        }

        $text = trim((string) $value);

        foreach (['d/m/Y', 'd/m/y', 'j/n/Y', 'j/n/y', 'j-M-y', 'd-M-y', 'j-M-Y', 'd-M-Y', 'Y-m-d', 'd-m-Y'] as $format) {
            try {
                $parsed = Carbon::createFromFormat('!'.$format, $text);

                if ($parsed !== false) {
                    return $parsed->toDateString();
                }
            } catch (\Throwable) {
                // try next
            }
        }

        try {
            return Carbon::parse($text)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
