<?php

namespace Tests\Unit;

use App\Services\TicketJourneyClassifier;
use App\Services\TicketPdfParser;
use Tests\TestCase;

class TicketJourneyClassifierTest extends TestCase
{
    private TicketJourneyClassifier $classifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->classifier = new TicketJourneyClassifier;
    }

    public function test_splits_connecting_round_trip_by_date_gap(): void
    {
        // Real-world style ticket: 2 outbound legs + 2 return legs
        $segments = [
            $this->makeLeg('EK-828', 'Dammam (DMM)', 'Dubai (DXB)', '03 Jan', '09:45'),
            $this->makeLeg('EK-618', 'Dubai (DXB)', 'Sialkot (SKT)', '03 Jan', '15:00'),
            $this->makeLeg('EK-2115', 'Sialkot (SKT)', 'Dubai (DXB)', '25 Mar', '17:35'),
            $this->makeLeg('EK-823', 'Dubai (DXB)', 'Dammam (DMM)', '26 Mar', '02:20'),
        ];

        $split = $this->classifier->split($segments);

        $this->assertSame(2, $split['return_start_index']);
        $this->assertCount(2, $split['outbound']);
        $this->assertCount(2, $split['return']);
        $this->assertSame('EK-828', $split['outbound'][0]['flight_number']);
        $this->assertSame('EK-618', $split['outbound'][1]['flight_number']);
        $this->assertSame('EK-2115', $split['return'][0]['flight_number']);
        $this->assertSame('EK-823', $split['return'][1]['flight_number']);
        $this->assertSame('Return', $this->classifier->labelForIndex($segments, 2));
        $this->assertSame('Return · Connection', $this->classifier->labelForIndex($segments, 3));
    }

    public function test_splits_simple_two_leg_round_trip(): void
    {
        $segments = [
            $this->makeLeg('PK-786', 'Karachi (KHI)', 'Islamabad (ISB)', '12 Dec', '08:30'),
            $this->makeLeg('PK-787', 'Islamabad (ISB)', 'Karachi (KHI)', '15 Dec', '19:00'),
        ];

        $split = $this->classifier->split($segments);

        $this->assertSame(1, $split['return_start_index']);
        $this->assertSame('PK-787', $split['return'][0]['flight_number']);
    }

    public function test_same_day_round_trip_uses_route_turnaround(): void
    {
        $segments = [
            $this->makeLeg('PF-100', 'Islamabad (ISB)', 'Karachi (KHI)', '10 Nov', '08:00'),
            $this->makeLeg('PF-101', 'Karachi (KHI)', 'Islamabad (ISB)', '10 Nov', '18:00'),
        ];

        $split = $this->classifier->split($segments);

        $this->assertSame(1, $split['return_start_index']);
        $this->assertCount(1, $split['outbound']);
        $this->assertCount(1, $split['return']);
    }

    public function test_one_way_connecting_has_no_return(): void
    {
        $segments = [
            $this->makeLeg('EK-828', 'Dammam (DMM)', 'Dubai (DXB)', '03 Jan', '09:45'),
            $this->makeLeg('EK-618', 'Dubai (DXB)', 'Sialkot (SKT)', '03 Jan', '15:00'),
        ];

        $split = $this->classifier->split($segments);

        $this->assertNull($split['return_start_index']);
        $this->assertCount(2, $split['outbound']);
        $this->assertSame([], $split['return']);
    }

    public function test_real_jm5ljr_pdf_text_classifies_return_correctly(): void
    {
        $text = 'Booking Reference: CLI_22494- 2847494 Agency PNR: JM5LJR '
            .'EK-828 Emirates 09:45 ( 03 Jan ) Dammam (DMM) Terminal - NA '
            .'12:15 ( 03 Jan ) Dubai (DXB) Terminal - 3 '
            .'Status : Confirm Class : Economy (T) Baggage : 35.0 KG PNR : GHZMG6 '
            .'EK-618 Emirates 15:00 ( 03 Jan ) Dubai (DXB) Terminal - 3 '
            .'19:00 ( 03 Jan ) Sialkot (SKT) Terminal - NA '
            .'Status : Confirm Class : Economy (T) Baggage : 35.0 KG PNR : GHZMG6 '
            .'EK-2115 Emirates Operated by Flydubai 17:35 ( 25 Mar ) Sialkot (SKT) Terminal - NA '
            .'20:30 ( 25 Mar ) Dubai (DXB) Terminal - 2 '
            .'Status : Confirm Class : Economy (T) Baggage : 25.0 KG PNR : GHZMG6 '
            .'EK-823 Emirates 02:20 ( 26 Mar ) Dubai (DXB) Terminal - 3 '
            .'02:50 ( 26 Mar ) Dammam (DMM) Terminal - NA '
            .'Status : Confirm Class : Economy (T) Baggage : 25.0 KG PNR : GHZMG6 '
            .'TRAVELLER NAME Mr QAISER RAZZAQ (ADT) FREQUENT FLYER - TICKET NO. 1765866216274';

        $parser = new TicketPdfParser;
        $parsed = $parser->parse($text);
        $split = $this->classifier->split($parsed['flight_segments']);

        $this->assertCount(4, $parsed['flight_segments']);
        $this->assertSame(2, $split['return_start_index']);
        $this->assertSame('EK-2115', $split['return'][0]['flight_number']);
        $this->assertSame('Emirates Operated by Flydubai', $split['return'][0]['airline']);
        $this->assertSame('EK-823', $split['return'][1]['flight_number']);
        $this->assertSame('Sialkot (SKT)', $split['return'][0]['departure_location']);
        $this->assertSame('Dammam (DMM)', $split['return'][1]['arrival_location']);
    }

    /**
     * Helper: build one fake flight leg for tests.
     */
    private function makeLeg(string $flight, string $from, string $to, string $date, string $time): array
    {
        return [
            'flight_number' => $flight,
            'airline' => 'Test Air',
            'departure_time' => $time,
            'departure_date' => $date,
            'departure_location' => $from,
            'departure_terminal' => 'NA',
            'arrival_time' => $time,
            'arrival_date' => $date,
            'arrival_location' => $to,
            'arrival_terminal' => 'NA',
            'status' => 'Confirm',
            'class' => 'Economy',
            'baggage' => '',
            'pnr' => 'ABC123',
        ];
    }
}
