<?php

namespace Tests\Unit;

use App\Services\TicketPdfParser;
use Tests\TestCase;

class TicketPdfParserTest extends TestCase
{
    private TicketPdfParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new TicketPdfParser;
    }

    public function test_parses_flight_segment_without_baggage_line(): void
    {
        $text = 'Booking Reference: CLI_22494- 2818031 Agency PNR: GKVTA6 '
            .'Refund and Cancellation policies are Governed by the respective Fare Rules applicable1. '
            .'PF-734 Air Sial 11:05 ( 09 Nov ) Islamabad (ISB) Terminal - NA '
            .'13:10 ( 09 Nov ) Muscat (MCT) TermInal - NA '
            .'Status : ConFim Class : Economy () PNR : GKVTA6 '
            .'TRAVELLER NAME Mr MIRZA ATIF RAHEEM (ADT) FREQUENT FLYER - TICKET NO. 6734005596371';

        $parsed = $this->parser->parse($text);

        $this->assertSame('Mr MIRZA ATIF RAHEEM (ADT)', $parsed['fields']['passenger_name']);
        $this->assertSame('6734005596371', $parsed['fields']['ticket_number']);
        $this->assertSame('GKVTA6', $parsed['fields']['agency_pnr']);
        $this->assertCount(1, $parsed['flight_segments']);

        $segment = $parsed['flight_segments'][0];
        $this->assertSame('PF-734', $segment['flight_number']);
        $this->assertSame('Air Sial', $segment['airline']);
        $this->assertSame('11:05', $segment['departure_time']);
        $this->assertSame('09 Nov', $segment['departure_date']);
        $this->assertSame('Islamabad (ISB)', $segment['departure_location']);
        $this->assertSame('NA', $segment['departure_terminal']);
        $this->assertSame('13:10', $segment['arrival_time']);
        $this->assertSame('09 Nov', $segment['arrival_date']);
        $this->assertSame('Muscat (MCT)', $segment['arrival_location']);
        $this->assertSame('NA', $segment['arrival_terminal']);
        $this->assertSame('Confirm', $segment['status']);
        $this->assertSame('Economy', $segment['class']);
        $this->assertSame('', $segment['baggage']);
        $this->assertSame('GKVTA6', $segment['pnr']);
    }

    public function test_parses_flight_segment_with_baggage_line(): void
    {
        $text = 'PK-786 Pakistan International 08:30 ( 12 Dec ) Karachi (KHI) Terminal - 1 '
            .'10:45 ( 12 Dec ) Islamabad (ISB) Terminal - NA '
            .'Status : Confirm Class : Economy (O) Baggage : 2.0 P PNR : ABC123 '
            .'TRAVELLER NAME Mr TEST USER (ADT) FREQUENT FLYER - TICKET NO. 1234567890123';

        $segments = $this->parser->parseFlightSegments($text);

        $this->assertCount(1, $segments);
        $this->assertSame('PK-786', $segments[0]['flight_number']);
        $this->assertSame('Pakistan International', $segments[0]['airline']);
        $this->assertSame('Economy (O)', $segments[0]['class']);
        $this->assertSame('2.0 P', $segments[0]['baggage']);
        $this->assertSame('ABC123', $segments[0]['pnr']);
        $this->assertSame('Confirm', $segments[0]['status']);
    }

    public function test_parses_multiple_flight_segments(): void
    {
        $text = 'PF-734 Air Sial 11:05 ( 09 Nov ) Islamabad (ISB) Terminal - NA '
            .'13:10 ( 09 Nov ) Muscat (MCT) Terminal - NA '
            .'Status : Confirm Class : Economy PNR : GKVTA6 '
            .'PF-735 Air Sial 15:00 ( 15 Nov ) Muscat (MCT) Terminal - NA '
            .'18:20 ( 15 Nov ) Islamabad (ISB) Terminal - NA '
            .'Status : Confirm Class : Economy Baggage : 20 KG PNR : GKVTA6';

        $segments = $this->parser->parseFlightSegments($text);

        $this->assertCount(2, $segments);
        $this->assertSame('PF-734', $segments[0]['flight_number']);
        $this->assertSame('', $segments[0]['baggage']);
        $this->assertSame('PF-735', $segments[1]['flight_number']);
        $this->assertSame('20 KG', $segments[1]['baggage']);
    }
}
