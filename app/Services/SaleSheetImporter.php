<?php

namespace App\Services;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Reads a travel sales Excel/CSV file and turns each row into a simple array.
 *
 * Expected columns:
 * DATE, PASSENGER NAME, SECTOR, AIRLINE, PAYABLE, RECEIVED, VIA
 *
 * Profit formula: profit = received - payable
 */
class SaleSheetImporter
{
    /**
     * Read the file and return a list of sale rows.
     */
    public function parse(string $path): array
    {
        // 1) Load all rows from the Excel / CSV file
        $rows = $this->loadRows($path);

        if (count($rows) === 0) {
            return [];
        }

        // 2) Find the header row (the one with DATE, PASSENGER NAME, etc.)
        $headerIndex = $this->findHeaderRow($rows);

        if ($headerIndex === null) {
            throw new \RuntimeException('Could not find header row (Date, Passenger Name, …).');
        }

        // 3) Learn which column number means which field
        $columns = $this->mapColumns($rows[$headerIndex]);

        if (! isset($columns['date']) || ! isset($columns['passenger_name'])) {
            throw new \RuntimeException('Sheet must include Date and Passenger Name columns.');
        }

        $entries = [];
        $lastSaleDate = null;

        // 4) Read every data row under the header
        for ($i = $headerIndex + 1; $i < count($rows); $i++) {
            $row = $rows[$i];

            $dateValue = $this->getRawValue($row, $columns['date']);
            $passenger = $this->getText($row, $columns['passenger_name']);

            // Skip TOTAL / BALANCE footer rows
            if ($this->isTotalOrBalanceLabel((string) $dateValue) || $this->isTotalOrBalanceLabel($passenger)) {
                continue;
            }

            // Skip empty rows
            if ($this->isBlankRow($row)) {
                continue;
            }

            // Skip rows with no passenger name
            if ($passenger === '') {
                continue;
            }

            // Read the date (or reuse the previous row's date if this cell is blank)
            $saleDate = $this->parseDate($dateValue);

            if ($saleDate === null && $lastSaleDate !== null) {
                $saleDate = $lastSaleDate;
            }

            if ($saleDate === null) {
                continue;
            }

            $lastSaleDate = $saleDate;

            // Read money columns
            $payable = $this->getPayable($row, $columns);
            $received = $this->getReceived($row, $columns, $payable);

            // Profit = what we received minus what we pay
            $profit = round($received - $payable, 2);

            $entries[] = [
                'sale_date' => $saleDate,
                'passenger_name' => $passenger,
                'sector' => $this->getOptionalText($row, $columns['sector'] ?? null),
                'airline' => $this->getOptionalText($row, $columns['airline'] ?? null),
                'payable' => $payable,
                'received' => $received,
                'via' => $this->getOptionalText($row, $columns['via'] ?? null),
                'profit' => $profit,
            ];
        }

        return $entries;
    }

    /**
     * Open the file and return all rows as a normal PHP array.
     */
    private function loadRows(string $path): array
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $startOfFile = $this->readFileStart($path);

        if ($this->isHtmlFile($startOfFile) || $ext === 'html' || $ext === 'htm') {
            $spreadsheet = IOFactory::createReader('Html')->load($path);
        } elseif ($ext === 'csv' || $ext === 'txt') {
            $reader = IOFactory::createReader('Csv');
            $reader->setDelimiter(',');
            $reader->setEnclosure('"');
            $reader->setInputEncoding('UTF-8');
            $spreadsheet = $reader->load($path);
        } else {
            // Normal Excel files (.xlsx / .xls)
            $spreadsheet = IOFactory::load($path);
        }

        return $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
    }

    /**
     * Read the first few bytes so we can detect HTML Excel exports.
     */
    private function readFileStart(string $path): string
    {
        $raw = @file_get_contents($path, false, null, 0, 512);

        if ($raw === false || $raw === '') {
            return '';
        }

        // Remove BOM if Excel added one at the start
        if (str_starts_with($raw, "\xEF\xBB\xBF")) {
            $raw = substr($raw, 3);
        }

        return $raw;
    }

    private function isHtmlFile(string $startOfFile): bool
    {
        if ($startOfFile === '') {
            return false;
        }

        $lower = strtolower($startOfFile);

        if (str_contains($lower, '<html')) {
            return true;
        }

        if (str_contains($lower, '<table')) {
            return true;
        }

        if (str_contains($lower, 'xmlns:x="urn:schemas-microsoft-com:office:excel"')) {
            return true;
        }

        return false;
    }

    /**
     * Find the row that contains the column titles.
     */
    private function findHeaderRow(array $rows): ?int
    {
        foreach ($rows as $index => $row) {
            $parts = [];

            foreach ($row as $value) {
                $parts[] = trim((string) $value);
            }

            $joined = strtoupper(implode(' ', $parts));

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
     * Match header names to column numbers.
     * Example: ['date' => 0, 'passenger_name' => 1, ...]
     */
    private function mapColumns(array $header): array
    {
        $columns = [];

        foreach ($header as $index => $label) {
            $name = $this->cleanHeaderName((string) $label);

            // Skip empty or serial-number headers
            if ($name === '' || $name === 'S#' || $name === 'S' || $name === 'SR' || $name === 'NO') {
                continue;
            }

            if ($name === 'DATE' || $name === 'TRAVEL DATE') {
                $columns['date'] = $index;
            } elseif (str_contains($name, 'PASSENGER')) {
                $columns['passenger_name'] = $index;
            } elseif ($name === 'SECTOR' || str_contains($name, 'SECTOR')) {
                if (! isset($columns['sector'])) {
                    $columns['sector'] = $index;
                }
            } elseif ($name === 'AIRLINE' || str_contains($name, 'AIRLINE')) {
                if (! isset($columns['airline'])) {
                    $columns['airline'] = $index;
                }
            } elseif ($name === 'PAYABLE') {
                $columns['payable'] = $index;
            } elseif ($name === 'RECEIVED') {
                $columns['received'] = $index;
            } elseif (str_contains($name, 'GRAND TOTAL')) {
                $columns['grand_total'] = $index;
            } elseif (str_contains($name, 'TOTAL FARE') || (str_contains($name, 'SALES') && str_contains($name, 'FARE'))) {
                if (! isset($columns['sales_total'])) {
                    $columns['sales_total'] = $index;
                }
            } elseif (str_contains($name, 'SALES') && ! str_contains($name, 'PROFIT')) {
                if (! isset($columns['sales_total'])) {
                    $columns['sales_total'] = $index;
                }
            } elseif (str_contains($name, 'FARE') && str_contains($name, 'PERSON')) {
                $columns['fare_person'] = $index;
            } elseif ($name === 'VIA' || $name === 'PNR' || str_contains($name, 'PNR')) {
                if (! isset($columns['via'])) {
                    $columns['via'] = $index;
                }
            }
        }

        return $columns;
    }

    /**
     * Make a header label easy to compare: "Payable (PKR)" -> "PAYABLE PKR"
     */
    private function cleanHeaderName(string $label): string
    {
        $name = strtoupper(trim($label));
        $name = preg_replace('/\s+/', ' ', $name);
        $name = str_replace(['/', '\\', '_'], ' ', $name);
        $name = preg_replace('/\s+/', ' ', $name);

        return trim((string) $name);
    }

    /**
     * Get the payable amount from the row.
     */
    private function getPayable(array $row, array $columns): float
    {
        if (isset($columns['payable'])) {
            return $this->parseMoney($this->getText($row, $columns['payable']));
        }

        if (isset($columns['sales_total'])) {
            return $this->parseMoney($this->getText($row, $columns['sales_total']));
        }

        if (isset($columns['fare_person'])) {
            return $this->parseMoney($this->getText($row, $columns['fare_person']));
        }

        return 0.0;
    }

    /**
     * Get the received amount from the row.
     */
    private function getReceived(array $row, array $columns, float $payable): float
    {
        if (isset($columns['received'])) {
            return $this->parseMoney($this->getText($row, $columns['received']));
        }

        if (isset($columns['grand_total'])) {
            return $this->parseMoney($this->getText($row, $columns['grand_total']));
        }

        if (isset($columns['sales_total'])) {
            return $this->parseMoney($this->getText($row, $columns['sales_total']));
        }

        // If received is missing, use payable as a safe default
        return $payable;
    }

    /**
     * Return true when every cell in the row is empty.
     */
    private function isBlankRow(array $row): bool
    {
        foreach ($row as $value) {
            $text = trim((string) ($value ?? ''));

            if ($text !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if text is a TOTAL / BALANCE label (footer row).
     */
    private function isTotalOrBalanceLabel(string $text): bool
    {
        $upper = strtoupper(trim($text));

        if ($upper === '') {
            return false;
        }

        if ($upper === 'TOTAL' || $upper === 'GRAND TOTAL' || $upper === 'SUB TOTAL' || $upper === 'BALANCE') {
            return true;
        }

        if (str_ends_with($upper, ' TOTAL')) {
            return true;
        }

        if (str_starts_with($upper, 'TOTAL ')) {
            return true;
        }

        if (str_starts_with($upper, 'GRAND TOTAL')) {
            return true;
        }

        if (str_starts_with($upper, 'BALANCE ')) {
            return true;
        }

        return false;
    }

    /**
     * Get a cell as trimmed text. Missing cells become "".
     */
    private function getText(array $row, ?int $index): string
    {
        if ($index === null) {
            return '';
        }

        if (! array_key_exists($index, $row)) {
            return '';
        }

        if ($row[$index] === null) {
            return '';
        }

        return trim((string) $row[$index]);
    }

    /**
     * Get the raw cell value (needed for Excel date numbers).
     */
    private function getRawValue(array $row, ?int $index): mixed
    {
        if ($index === null) {
            return null;
        }

        if (! array_key_exists($index, $row)) {
            return null;
        }

        return $row[$index];
    }

    /**
     * Get text or null when the cell is empty.
     */
    private function getOptionalText(array $row, ?int $index): ?string
    {
        $value = $this->getText($row, $index);

        if ($value === '') {
            return null;
        }

        return $value;
    }

    /**
     * Turn money text like "165,053" into 165053.00
     */
    private function parseMoney(string $value): float
    {
        // Remove commas, then keep only digits, dot, and minus
        $noCommas = str_replace(',', '', $value);
        $clean = preg_replace('/[^\d.\-]/', '', $noCommas);

        if ($clean === null || $clean === '') {
            $clean = '0';
        }

        return round((float) $clean, 2);
    }

    /**
     * Turn many date styles into Y-m-d (example: 2026-10-03).
     */
    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Already a date object
        if ($value instanceof \DateTimeInterface) {
            return Carbon::parse($value)->toDateString();
        }

        // Excel sometimes stores dates as numbers
        if (is_numeric($value)) {
            try {
                $date = ExcelDate::excelToDateTimeObject((float) $value);

                return Carbon::instance($date)->toDateString();
            } catch (\Throwable $e) {
                // Try other formats below
            }
        }

        $text = trim((string) $value);

        if ($this->isTotalOrBalanceLabel($text)) {
            return null;
        }

        $formats = [
            'd/m/Y',
            'd/m/y',
            'j/n/Y',
            'j/n/y',
            'j-M-y',
            'd-M-y',
            'j-M-Y',
            'd-M-Y',
            'Y-m-d',
            'd-m-Y',
        ];

        foreach ($formats as $format) {
            try {
                $parsed = Carbon::createFromFormat('!'.$format, $text);

                if ($parsed !== false) {
                    return $parsed->toDateString();
                }
            } catch (\Throwable $e) {
                // Try the next format
            }
        }

        // Last try: let Carbon guess
        try {
            return Carbon::parse($text)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
