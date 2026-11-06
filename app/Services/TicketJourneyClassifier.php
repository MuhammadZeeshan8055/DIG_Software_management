<?php

namespace App\Services;

/**
 * Decide which flights are outbound and which are return.
 *
 * A ticket PDF can have many flight legs in a list, for example:
 *   1) DMM -> DXB
 *   2) DXB -> SKT   (still outbound, just a connection)
 *   3) SKT -> DXB   (return starts here)
 *   4) DXB -> DMM
 *
 * Old code treated flight #2 as the return. That was wrong.
 * This class finds the real start of the return journey.
 */
class TicketJourneyClassifier
{
    /**
     * Split all flight legs into two groups: outbound and return.
     *
     * Example result:
     * [
     *   'outbound' => [leg1, leg2],
     *   'return' => [leg3, leg4],
     *   'return_start_index' => 2,
     * ]
     */
    public function split(array $segments): array
    {
        // Reset keys to 0, 1, 2, 3... so index math is simple
        $segments = array_values($segments);

        // No flights at all
        if (count($segments) === 0) {
            return [
                'outbound' => [],
                'return' => [],
                'return_start_index' => null,
            ];
        }

        // Find where the return journey starts (or null if one-way)
        $returnStart = $this->findReturnStartIndex($segments);

        // One-way ticket: everything is outbound
        if ($returnStart === null) {
            return [
                'outbound' => $segments,
                'return' => [],
                'return_start_index' => null,
            ];
        }

        // Round trip: cut the list into two parts
        $outbound = [];
        $return = [];

        for ($i = 0; $i < count($segments); $i++) {
            if ($i < $returnStart) {
                $outbound[] = $segments[$i];
            } else {
                $return[] = $segments[$i];
            }
        }

        return [
            'outbound' => $outbound,
            'return' => $return,
            'return_start_index' => $returnStart,
        ];
    }

    /**
     * Return the index where the return journey begins.
     * Returns null when there is no return flight.
     */
    public function findReturnStartIndex(array $segments): ?int
    {
        $segments = array_values($segments);
        $count = count($segments);

        // Need at least 2 flights to have a return
        if ($count < 2) {
            return null;
        }

        // Method 1: look for a long gap between dates (best for most tickets)
        // Example: 03 Jan then 25 Mar => return starts on 25 Mar
        $dateSplit = $this->findSplitByDateGap($segments);
        if ($dateSplit !== null) {
            return $dateSplit;
        }

        // Method 2: round trip like ISB -> KHI -> ISB on the same day
        $routeSplit = $this->findSplitByRoundTrip($segments);
        if ($routeSplit !== null) {
            return $routeSplit;
        }

        // Method 3: exactly 2 flights that are NOT a connection
        // Example: KHI -> LHE, then DXB -> ISB (two separate trips)
        if ($count === 2) {
            $firstArrival = $this->airportCode($segments[0]['arrival_location'] ?? '');
            $secondDeparture = $this->airportCode($segments[1]['departure_location'] ?? '');

            if ($firstArrival !== '' && $secondDeparture !== '' && $firstArrival !== $secondDeparture) {
                return 1;
            }
        }

        // Could not find a return — treat all as outbound
        return null;
    }

    /**
     * Label used on the import screen / verify page.
     * Examples: "Outbound", "Outbound · Connection", "Return"
     */
    public function labelForIndex(array $segments, int $index): string
    {
        $split = $this->split($segments);
        $returnStart = $split['return_start_index'];

        // No return journey
        if ($returnStart === null) {
            if ($index === 0) {
                return 'Outbound';
            }

            return 'Outbound · Connection';
        }

        // Still in the outbound part
        if ($index < $returnStart) {
            if ($index === 0) {
                return 'Outbound';
            }

            return 'Outbound · Connection';
        }

        // Return part
        if ($index === $returnStart) {
            return 'Return';
        }

        return 'Return · Connection';
    }

    /**
     * Find the biggest time gap between two flights.
     * If that gap is 18 hours or more, treat it as the start of return.
     */
    private function findSplitByDateGap(array $segments): ?int
    {
        $timestamps = $this->getDepartureTimestamps($segments);

        $biggestGap = -1;
        $splitIndex = null;

        for ($i = 1; $i < count($segments); $i++) {
            $previousTime = $timestamps[$i - 1];
            $currentTime = $timestamps[$i];

            // Skip if we could not read a date
            if ($previousTime === null || $currentTime === null) {
                continue;
            }

            $gapInSeconds = $currentTime - $previousTime;

            if ($gapInSeconds > $biggestGap) {
                $biggestGap = $gapInSeconds;
                $splitIndex = $i;
            }
        }

        // 18 hours = 18 * 60 * 60 seconds
        $eighteenHours = 18 * 60 * 60;

        if ($splitIndex !== null && $biggestGap >= $eighteenHours) {
            return $splitIndex;
        }

        return null;
    }

    /**
     * For a round trip that starts and ends at the same airport,
     * find the city where the passenger turns around.
     *
     * Example: ISB -> DXB -> JED -> DXB -> ISB
     * Turnaround city is JED, so return starts when leaving JED.
     */
    private function findSplitByRoundTrip(array $segments): ?int
    {
        $count = count($segments);
        $startAirport = $this->airportCode($segments[0]['departure_location'] ?? '');
        $endAirport = $this->airportCode($segments[$count - 1]['arrival_location'] ?? '');

        // Not a classic round trip (must start and end at same airport)
        if ($startAirport === '' || $startAirport !== $endAirport) {
            return null;
        }

        $bestOutboundEnd = -1;
        $bestSplitIndex = null;

        // Look for an airport we arrive at, then later depart from (the stay city)
        for ($i = 0; $i < $count - 1; $i++) {
            $arrivalAirport = $this->airportCode($segments[$i]['arrival_location'] ?? '');

            if ($arrivalAirport === '' || $arrivalAirport === $startAirport) {
                continue;
            }

            for ($j = $i + 1; $j < $count; $j++) {
                $departureAirport = $this->airportCode($segments[$j]['departure_location'] ?? '');

                if ($departureAirport !== $arrivalAirport) {
                    continue;
                }

                // Prefer the furthest outbound city (largest i)
                if ($i >= $bestOutboundEnd) {
                    $bestOutboundEnd = $i;
                    $bestSplitIndex = $j;
                }
            }
        }

        return $bestSplitIndex;
    }

    /**
     * Convert each flight's departure date/time into a timestamp (seconds).
     * PDF dates often look like "03 Jan" with no year.
     */
    private function getDepartureTimestamps(array $segments): array
    {
        $timestamps = [];
        $year = (int) date('Y');
        $previousTimestamp = null;

        foreach ($segments as $index => $segment) {
            $timestamp = $this->parseDepartureTimestamp($segment, $year);

            // If dates go backwards (Dec then Jan), move to next year
            if ($timestamp !== null && $previousTimestamp !== null) {
                $oneDay = 24 * 60 * 60;
                if ($timestamp < ($previousTimestamp - $oneDay)) {
                    $year = $year + 1;
                    $timestamp = $this->parseDepartureTimestamp($segment, $year);
                }
            }

            $timestamps[$index] = $timestamp;

            if ($timestamp !== null) {
                $previousTimestamp = $timestamp;
            }
        }

        return $timestamps;
    }

    /**
     * Turn "03 Jan" + "09:45" into a unix timestamp.
     */
    private function parseDepartureTimestamp(array $segment, int $year): ?int
    {
        $date = isset($segment['departure_date']) ? trim($segment['departure_date']) : '';
        $time = isset($segment['departure_time']) ? trim($segment['departure_time']) : '';

        if ($date === '') {
            return null;
        }

        // Clean extra spaces: "03  Jan" => "03 Jan"
        $date = preg_replace('/\s+/', ' ', $date);

        // Expected formats: "03 Jan" or "03 Jan 2026"
        if (! preg_match('/^(\d{1,2})\s+([A-Za-z]{3})(?:\s+(\d{2,4}))?$/', $date, $matches)) {
            return null;
        }

        $day = (int) $matches[1];
        $month = $matches[2];

        if (isset($matches[3]) && $matches[3] !== '') {
            $parsedYear = (int) $matches[3];
        } else {
            $parsedYear = $year;
        }

        // "26" => 2026
        if ($parsedYear < 100) {
            $parsedYear = 2000 + $parsedYear;
        }

        // If time is missing or invalid, use midnight
        if ($time === '' || ! preg_match('/^\d{1,2}:\d{2}$/', $time)) {
            $time = '00:00';
        }

        // Build a string strtotime understands: "03 Jan 2026 09:45"
        $monthNice = ucfirst(strtolower($month));
        $dayNice = str_pad((string) $day, 2, '0', STR_PAD_LEFT);
        $dateString = $dayNice.' '.$monthNice.' '.$parsedYear.' '.$time;

        $timestamp = strtotime($dateString);

        if ($timestamp === false) {
            return null;
        }

        return $timestamp;
    }

    /**
     * Get 3-letter airport code from text like "Dubai (DXB)" => "DXB"
     */
    public function airportCode(?string $location): string
    {
        if ($location === null || $location === '') {
            return '';
        }

        // Prefer code inside brackets: (DXB)
        if (preg_match('/\(([A-Z]{3})\)/', $location, $matches)) {
            return $matches[1];
        }

        // Fallback: any standalone 3-letter code
        if (preg_match('/\b([A-Z]{3})\b/', $location, $matches)) {
            return $matches[1];
        }

        return '';
    }

    /**
     * Old name kept so existing calls still work.
     */
    public function locationCode(?string $location): string
    {
        return $this->airportCode($location);
    }
}
