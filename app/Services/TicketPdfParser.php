<?php

namespace App\Services;

class TicketPdfParser
{
    public function extractText(string $pdfPath): string
    {
        $parser = new \Smalot\PdfParser\Parser;
        $pdf = $parser->parseFile($pdfPath);

        return trim(preg_replace('/\s+/u', ' ', $pdf->getText()) ?? '');
    }

    /**
     * @return array{fields: array<string, string>, flight_segments: array<int, array<string, string>>}
     */
    public function parse(string $text): array
    {
        return [
            'fields' => $this->parseFields($text),
            'flight_segments' => $this->parseFlightSegments($text),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function parseFields(string $text): array
    {
        $fields = [];

        foreach (array_keys(config('ticket_import.form_fields', [])) as $key) {
            $fields[$key] = '';
        }

        foreach (config('ticket_import.patterns', []) as $field => $pattern) {
            if (! array_key_exists($field, $fields) || ! $pattern) {
                continue;
            }

            if (preg_match($pattern, $text, $matches)) {
                $fields[$field] = trim(preg_replace('/\s+/', ' ', $matches[1]));
            }
        }

        if ($fields['frequent_flyer'] === '-') {
            $fields['frequent_flyer'] = '';
        }

        if ($fields['booking_reference']) {
            $fields['booking_reference'] = preg_replace('/\s*-\s*/', '-', $fields['booking_reference']);
        }

        return $fields;
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function parseFlightSegments(string $text): array
    {
        $pattern = '/(?<flight_number>[A-Z]{2}-\d+)\s+(?<airline>[A-Za-z][A-Za-z\s]+?)\s+'
            .'(?<departure_time>\d{2}:\d{2})\s*\(\s*(?<departure_date>[^)]+?)\s*\)\s+'
            .'(?<departure_location>[^(]+?\([A-Z]{3}\))\s*Terminal[^0-9]*'
            .'(?<arrival_time>\d{2}:\d{2})\s*\(\s*(?<arrival_date>[^)]+?)\s*\)\s+'
            .'(?<arrival_location>[^(]+?\([A-Z]{3}\))\s*Terminal[^S]*'
            .'Status\s*:\s*(?<status>\w+)\s*Class\s*:\s*(?<class>[^B]+?)\s*'
            .'Baggage\s*:\s*(?<baggage>[^P]+?)\s*PNR\s*:\s*(?<pnr>[A-Z0-9]+)/i';

        if (! preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
            return [];
        }

        $segments = [];

        foreach ($matches as $match) {
            $segments[] = $this->normalizeSegment($match);
        }

        return $segments;
    }

    /**
     * @param  array<string, string>  $match
     * @return array<string, string>
     */
    private function normalizeSegment(array $match): array
    {
        $segment = [];

        foreach (array_keys(config('ticket_import.flight_segment_fields', [])) as $key) {
            $segment[$key] = trim(preg_replace('/\s+/', ' ', $match[$key] ?? ''));
        }

        return $segment;
    }

    /** @return array<string, string> */
    public function emptySegment(): array
    {
        $segment = [];

        foreach (array_keys(config('ticket_import.flight_segment_fields', [])) as $key) {
            $segment[$key] = '';
        }

        return $segment;
    }
}
