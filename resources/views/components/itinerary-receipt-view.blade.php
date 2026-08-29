@props(['import'])

@php
    $segments = $import->flight_segments ?? [];
@endphp

<div class="itinerary-receipt itinerary-receipt--view">
    <div class="itinerary-receipt__agency-meta">
        <span class="itinerary-receipt__value itinerary-receipt__value--agency">{{ $import->agency_name ?: '—' }}</span>
        <span class="itinerary-receipt__value itinerary-receipt__value--phone">{{ $import->agency_phone ?: '—' }}</span>
    </div>

    <div class="itinerary-receipt__hero">
        <div class="itinerary-receipt__hero-left">
            <h4 class="itinerary-receipt__title-display">Itinerary Receipt</h4>
            <p class="itinerary-receipt__subtitle">Below are the details of your electronic ticket. Note: All timings are local</p>
        </div>
        <div class="itinerary-receipt__hero-right">
            <div class="itinerary-receipt__ref-row">
                <span>Booking Reference:</span>
                <span class="itinerary-receipt__value">{{ $import->booking_reference ?: '—' }}</span>
            </div>
            <div class="itinerary-receipt__ref-row">
                <span>Agency PNR:</span>
                <span class="itinerary-receipt__value">{{ $import->agency_pnr ?: '—' }}</span>
            </div>
        </div>
    </div>

    <section class="itinerary-receipt__section">
        <div class="itinerary-receipt__section-head">Flight Information</div>

        @forelse ($segments as $segment)
            <div class="itinerary-receipt__flight">
                <div class="itinerary-receipt__flight-grid">
                    <div class="itinerary-receipt__flight-col">
                        <span class="itinerary-receipt__flight-code">{{ $segment['flight_number'] ?? '—' }}</span>
                        <span class="itinerary-receipt__value itinerary-receipt__value--bold">{{ $segment['airline'] ?? '—' }}</span>
                    </div>

                    <div class="itinerary-receipt__flight-col">
                        <div class="itinerary-receipt__time-row">
                            <span class="itinerary-receipt__value">{{ $segment['departure_time'] ?? '—' }}</span>
                            <span>(</span>
                            <span class="itinerary-receipt__value">{{ $segment['departure_date'] ?? '—' }}</span>
                            <span>)</span>
                        </div>
                        <span class="itinerary-receipt__value">{{ $segment['departure_location'] ?? '—' }}</span>
                        <span class="itinerary-receipt__muted">Terminal - NA</span>
                    </div>

                    <div class="itinerary-receipt__flight-col">
                        <div class="itinerary-receipt__time-row">
                            <span class="itinerary-receipt__value">{{ $segment['arrival_time'] ?? '—' }}</span>
                            <span>(</span>
                            <span class="itinerary-receipt__value">{{ $segment['arrival_date'] ?? '—' }}</span>
                            <span>)</span>
                        </div>
                        <span class="itinerary-receipt__value">{{ $segment['arrival_location'] ?? '—' }}</span>
                        <span class="itinerary-receipt__muted">Terminal - NA</span>
                    </div>

                    <div class="itinerary-receipt__flight-col itinerary-receipt__flight-col--meta">
                        <div class="itinerary-receipt__meta-line">
                            <span>Status :</span>
                            <span class="itinerary-receipt__value">{{ $segment['status'] ?? '—' }}</span>
                        </div>
                        <div class="itinerary-receipt__meta-line">
                            <span>Class :</span>
                            <span class="itinerary-receipt__value">{{ $segment['class'] ?? '—' }}</span>
                        </div>
                        <div class="itinerary-receipt__meta-line">
                            <span>Baggage :</span>
                            <span class="itinerary-receipt__value">{{ $segment['baggage'] ?? '—' }}</span>
                        </div>
                        <div class="itinerary-receipt__meta-line">
                            <span>PNR :</span>
                            <span class="itinerary-receipt__value">{{ $segment['pnr'] ?? '—' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <p class="itinerary-receipt__empty">No flight segments saved.</p>
        @endforelse
    </section>

    <section class="itinerary-receipt__section">
        <div class="itinerary-receipt__section-head">Passenger &amp; Ticket Details</div>

        <div class="itinerary-receipt__passenger-table">
            <div class="itinerary-receipt__passenger-head">
                <span>Traveller Name</span>
                <span>Frequent Flyer</span>
                <span>Ticket No.</span>
            </div>
            <div class="itinerary-receipt__passenger-row itinerary-receipt__passenger-row--view">
                <span class="itinerary-receipt__value">{{ $import->passenger_name ?: '—' }}</span>
                <span class="itinerary-receipt__value">{{ $import->frequent_flyer ?: '—' }}</span>
                <span class="itinerary-receipt__value">{{ $import->ticket_number ?: '—' }}</span>
            </div>
        </div>
    </section>

    <div class="itinerary-receipt__notice">
        <strong>Notice:</strong>
        <p>Refund and Cancellation policies are Governed by the respective Fare Rules applicable</p>
    </div>

    @if ($import->raw_pdf_text)
        <details class="import-ticket__raw">
            <summary>Raw PDF text (for tracing import errors)</summary>
            <pre>{{ $import->raw_pdf_text }}</pre>
        </details>
    @endif
</div>
