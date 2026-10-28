<?php

namespace App\Services;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class SaleSheetImporter
{
    /**
     * Parse an Excel/CSV sales sheet into row arrays.
     *
     * Expected headers (any case): DATE, PASSENGER NAME, SECTOR, AIRLINE, PAYABLE, RECEIVED, VIA
     *
     * @return array<int, array{
     *     sale_date: string,
     *     passenger_name: string,
     *     sector: ?string,
     *     airline: ?string,
     *     payable: float,
     *     received: float,
     *     via: ?string
     * }>
     */
    public function parse(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);

        if ($rows === []) {
            return [];
        }

        $headerIndex = $this->findHeaderRow($rows);

        if ($headerIndex === null) {
            throw new \RuntimeException('Could not find header row (DATE, PASSENGER NAME, …).');
        }

        $map = $this->mapColumns($rows[$headerIndex]);

        if (! isset($map['date'], $map['passenger_name'])) {
            throw new \RuntimeException('Sheet must include DATE and PASSENGER NAME columns.');
        }

        $entries = [];

        for ($i = $headerIndex + 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $passenger = $this->cell($row, $map['passenger_name']);

            if ($passenger === '') {
                continue;
            }

            $saleDate = $this->parseDate($this->rawCell($row, $map['date']));

            if (! $saleDate) {
                continue;
            }

            $entries[] = [
                'sale_date' => $saleDate,
                'passenger_name' => $passenger,
                'sector' => $this->nullableCell($row, $map['sector'] ?? null),
                'airline' => $this->nullableCell($row, $map['airline'] ?? null),
                'payable' => $this->parseMoney($this->cell($row, $map['payable'] ?? null)),
                'received' => $this->parseMoney($this->cell($row, $map['received'] ?? null)),
                'via' => $this->nullableCell($row, $map['via'] ?? null),
            ];
        }

        return $entries;
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function findHeaderRow(array $rows): ?int
    {
        foreach ($rows as $index => $row) {
            $joined = strtoupper(implode(' ', array_map(fn ($v) => trim((string) $v), $row)));

            if (str_contains($joined, 'DATE') && str_contains($joined, 'PASSENGER') && str_contains($joined, 'PAYABLE')) {
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
            $key = strtoupper(trim(preg_replace('/\s+/', ' ', (string) $label) ?? ''));

            if ($key === 'DATE') {
                $map['date'] = $index;
            } elseif (str_contains($key, 'PASSENGER')) {
                $map['passenger_name'] = $index;
            } elseif ($key === 'SECTOR') {
                $map['sector'] = $index;
            } elseif ($key === 'AIRLINE') {
                $map['airline'] = $index;
            } elseif ($key === 'PAYABLE') {
                $map['payable'] = $index;
            } elseif ($key === 'RECEIVED') {
                $map['received'] = $index;
            } elseif ($key === 'VIA') {
                $map['via'] = $index;
            }
        }

        return $map;
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

        foreach (['j-M-y', 'd-M-y', 'j-M-Y', 'd-M-Y', 'Y-m-d', 'd/m/Y', 'd-m-Y'] as $format) {
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
