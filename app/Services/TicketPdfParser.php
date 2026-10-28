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
     * @return array{
     *     fields: array<string, string>,
     *     flight_segments: array<int, array<string, string>>,
     *     passenger_extras: array<int, array{passenger: string, segments: array<int, array<string, string>>}>
     * }
     */
    public function parse(string $text): array
    {
        return [
            'fields' => $this->parseFields($text),
            'flight_segments' => $this->parseFlightSegments($text),
            'passenger_extras' => $this->parseExtras($text),
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

            // Passenger and ticket number can have more than one — handled below
            if ($field === 'passenger_name' || $field === 'ticket_number') {
                continue;
            }

            if (preg_match($pattern, $text, $matches)) {
                $fields[$field] = trim(preg_replace('/\s+/', ' ', $matches[1]));
            }
        }

        // Pair each passenger with their own ticket number
        $passengerTickets = $this->parsePassengerTickets($text);
        $fields['passenger_name'] = $passengerTickets['passenger_name'];
        $fields['ticket_number'] = $passengerTickets['ticket_number'];

        if ($fields['frequent_flyer'] === '-') {
            $fields['frequent_flyer'] = '';
        }

        if ($fields['booking_reference']) {
            $fields['booking_reference'] = preg_replace('/\s*-\s*/', '-', $fields['booking_reference']);
        }

        return $fields;
    }

    /**
     * Get passengers and their ticket numbers.
     * PDF table often looks like:
     * Mr A (ADT) - 0842319320249
     * Mrs B (ADT) - 0842319320250
     *
     * @return array{passenger_name: string, ticket_number: string}
     */
    private function parsePassengerTickets(string $text): array
    {
        $names = [];
        $tickets = [];

        // 1) Best: name + ticket number together in one row
        //    Example: Mr ALEENA IJAZ (ADT) - 0842319320249
        //    Also allows " - " for frequent flyer between name and ticket
        if (preg_match_all(
            '/((?:Mr|Mrs|Ms|Miss|Mstr|Master|Infant)\.?\s+[A-Za-z][A-Za-z .\'\-]*?\((?:ADT|CHD|INF)\))\s*(?:-\s*){0,3}(\d{10,15})/i',
            $text,
            $matches,
            PREG_SET_ORDER
        )) {
            foreach ($matches as $match) {
                $name = trim(preg_replace('/\s+/', ' ', $match[1]));
                $ticket = trim($match[2]);

                if ($name === '' || in_array($name, $names, true)) {
                    continue;
                }

                $names[] = $name;
                $tickets[] = $ticket;
            }
        }

        // 2) If no pairs found, get names and ticket numbers separately
        if (count($names) === 0) {
            $names = $this->findPassengerNames($text);
            $tickets = $this->findTicketNumbers($text);
        }

        // 3) If we have names but only 1 ticket (or fewer), find more ticket numbers
        if (count($names) > 1 && count($tickets) < count($names)) {
            $moreTickets = $this->findTicketNumbers($text);

            if (count($moreTickets) >= count($names)) {
                $tickets = $moreTickets;
            }
        }

        // Keep same count order: name1 with ticket1, name2 with ticket2, ...
        return [
            'passenger_name' => implode(' / ', $names),
            'ticket_number' => implode(' / ', $tickets),
        ];
    }

    /** @return array<int, string> */
    private function findPassengerNames(string $text): array
    {
        $names = [];

        // TRAVELLER NAME Mr NAME (ADT)
        if (preg_match_all('/TRAVELLER NAME\s+(.+?\((?:ADT|CHD|INF)\))/i', $text, $matches)) {
            foreach ($matches[1] as $name) {
                $name = trim(preg_replace('/\s+/', ' ', $name));
                if ($name !== '' && ! in_array($name, $names, true)) {
                    $names[] = $name;
                }
            }
        }

        if (count($names) <= 1) {
            if (preg_match_all('/((?:Mr|Mrs|Ms|Miss|Mstr|Master|Infant)\.?\s+[A-Za-z][A-Za-z .\'\-]*?\((?:ADT|CHD|INF)\))/i', $text, $matches2)) {
                foreach ($matches2[1] as $name) {
                    $name = trim(preg_replace('/\s+/', ' ', $name));
                    if ($name !== '' && ! in_array($name, $names, true)) {
                        $names[] = $name;
                    }
                }
            }
        }

        if (count($names) <= 1) {
            if (preg_match_all('/([A-Za-z][A-Za-z .\'\-]{1,60}?\((?:ADT|CHD|INF)\))/', $text, $matches3)) {
                foreach ($matches3[1] as $name) {
                    $name = trim(preg_replace('/\s+/', ' ', $name));

                    if (stripos($name, 'TRAVELLER') !== false) {
                        continue;
                    }

                    if ($name !== '' && ! in_array($name, $names, true)) {
                        $names[] = $name;
                    }
                }
            }
        }

        return $names;
    }

    /** @return array<int, string> */
    private function findTicketNumbers(string $text): array
    {
        $numbers = [];

        // After "TICKET NO." label (may appear once as a header)
        if (preg_match_all('/TICKET NO\.\s*(\d{10,15})/i', $text, $matches)) {
            foreach ($matches[1] as $number) {
                $number = trim($number);
                if ($number !== '' && ! in_array($number, $numbers, true)) {
                    $numbers[] = $number;
                }
            }
        }

        // Table style: many 13-digit ticket numbers in the PDF
        if (count($numbers) <= 1) {
            if (preg_match_all('/\b(\d{13})\b/', $text, $matches2)) {
                foreach ($matches2[1] as $number) {
                    $number = trim($number);
                    if ($number !== '' && ! in_array($number, $numbers, true)) {
                        $numbers[] = $number;
                    }
                }
            }
        }

        // Broader: 10 to 15 digit numbers near passengers
        if (count($numbers) <= 1) {
            if (preg_match_all('/\b(\d{10,15})\b/', $text, $matches3)) {
                foreach ($matches3[1] as $number) {
                    $number = trim($number);

                    // Skip phone-like numbers starting with country code tips
                    if (str_starts_with($number, '92') && strlen($number) <= 12) {
                        continue;
                    }

                    if ($number !== '' && ! in_array($number, $numbers, true)) {
                        $numbers[] = $number;
                    }
                }
            }
        }

        return $numbers;
    }

    /**
     * Parse the EXTRAS table (seat / meal / baggage per passenger per segment).
     *
     * @return array<int, array{passenger: string, segments: array<int, array<string, string>>}>
     */
    public function parseExtras(string $text): array
    {
        $extras = [];

        // Important: PDF TOC has a lone word "EXTRAS" early on — ignore that.
        // Use the real table that starts with column headers.
        $body = '';
        if (preg_match('/PASSENGER\s+SEGMENTS\s+SEAT\s+MEAL\s+BAGGAGE\s+(.+)$/is', $text, $section)) {
            $body = trim($section[1]);
        }

        if ($body === '') {
            return [];
        }

        // Each passenger + their segment rows
        // Example: Mr ALEENA IJAZ ISB - JED 11D None None JED - ISB 11A None None
        if (! preg_match_all(
            '/((?:Mr|Mrs|Ms|Miss|Mstr|Master|Infant)\.?\s+[A-Z][A-Za-z .\'\-]*)((?:\s+[A-Z]{3}\s*-\s*[A-Z]{3}\s+\S+\s+\S+\s+\S+)+)/',
            $body,
            $matches,
            PREG_SET_ORDER
        )) {
            return [];
        }

        foreach ($matches as $match) {
            $passenger = trim(preg_replace('/\s+/', ' ', $match[1]));
            $segBlock = $match[2];
            $segments = [];

            if (preg_match_all('/([A-Z]{3})\s*-\s*([A-Z]{3})\s+(\S+)\s+(\S+)\s+(\S+)/', $segBlock, $segs, PREG_SET_ORDER)) {
                foreach ($segs as $seg) {
                    $segments[] = [
                        'segment' => $seg[1].' - '.$seg[2],
                        'seat' => $seg[3],
                        'meal' => $seg[4],
                        'baggage' => $seg[5],
                    ];
                }
            }

            if ($passenger !== '' && $segments !== []) {
                $extras[] = [
                    'passenger' => $passenger,
                    'segments' => $segments,
                ];
            }
        }

        return $extras;
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function parseFlightSegments(string $text): array
    {
        // Tip: do not use [^P]/[^B]/[^SC] fences — baggage "2.0 P", class
        // "Business", and "Status" all break those. Stop on the next label instead.
        //
        // Baggage is optional: some itineraries (e.g. Air Sial / no-fare PDFs)
        // go straight from Class → PNR with no Baggage line.
        // Terminal is matched case-insensitively (PDF OCR often yields "TermInal").
        $pattern = '/(?<flight_number>[A-Z]{2}-\d+)\s+(?<airline>[A-Za-z][A-Za-z\s]+?)\s+'
            .'(?<departure_time>\d{2}:\d{2})\s*\(\s*(?<departure_date>[^)]+?)\s*\)\s+'
            .'(?<departure_location>[^(]+?\([A-Z]{3}\))\s*Terminal\s*-\s*(?<departure_terminal>\S+)\s+'
            .'(?<arrival_time>\d{2}:\d{2})\s*\(\s*(?<arrival_date>[^)]+?)\s*\)\s+'
            .'(?<arrival_location>[^(]+?\([A-Z]{3}\))\s*Terminal\s*-\s*(?<arrival_terminal>\S+)\s+'
            .'(?:Status\s*:\s*(?<status>\w+)\s*)?'
            .'Class\s*:\s*(?<class>.+?)'
            .'(?:\s+Baggage\s*:\s*(?<baggage>.+?))?'
            .'\s+PNR\s*:\s*(?<pnr>[A-Z0-9]+)/i';

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

        // PDF OCR often drops letters in "Confirm" (e.g. ConFim / Confim).
        if ($segment['status'] !== '' && preg_match('/^con[fv]?i?m$/i', $segment['status'])) {
            $segment['status'] = 'Confirm';
        }

        // Clean empty fare-class brackets: "Economy ()" → "Economy"
        if ($segment['class'] !== '') {
            $segment['class'] = trim(preg_replace('/\(\s*\)/', '', $segment['class']));
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
