@props(['import'])

@php
    $segments = $import->flight_segments ?? [];
    $outbound = $segments[0] ?? null;
    $return = $segments[1] ?? null;
    $payment = $import->paymentEntry;
    $currency = config('payment_status.currency', 'PKR');
    $airline = $outbound['airline'] ?? ($return['airline'] ?? 'the selected carrier');
    $carrierTerms = array_map(
        fn (string $term) => str_replace(':airline', $airline, $term),
        config('ticket_carrier_terms.terms', [])
    );
@endphp

<div {{ $attributes->merge(['class' => 'ticket-doc']) }}>
    <header class="ticket-doc__header">
        <div class="ticket-doc__brand-card">
            <img src="{{ asset('images/logo-icon.png') }}" alt="DHOTHAR" class="ticket-doc__logo">
            <div class="ticket-doc__brand-text">
                <strong class="ticket-doc__brand-name">DHOTHAR</strong>
                <span class="ticket-doc__brand-group">International Group</span>
                <small class="ticket-doc__brand-tagline">Travel &amp; Tours (Pvt Ltd)</small>
                <!-- <small class="ticket-doc__brand-tagline">Private &amp; Limited</small> -->
            </div>
        </div>

        <div class="ticket-doc__title-block">
            <p class="ticket-doc__eyebrow">Official Operations Document</p>
            <h1 class="ticket-doc__title">{{ $import->document_title ?: 'Ticket / PNR' }}</h1>
            <p class="ticket-doc__subtitle">Tickets &amp; PNR Management</p>
            <div class="ticket-doc__badges">
                <span class="ticket-doc__badge ticket-doc__badge--success">Completed</span>
                <span class="ticket-doc__badge ticket-doc__badge--success">Approved</span>
            </div>
        </div>
    </header>

    <div class="ticket-doc__meta-row">
        <div class="ticket-doc__meta-item">
            <span class="ticket-doc__meta-label">Document / Serial Number</span>
            <span class="ticket-doc__meta-value">{{ $import->documentSerial() }}</span>
        </div>
        <div class="ticket-doc__meta-item">
            <span class="ticket-doc__meta-label">CRM Reference</span>
            <span class="ticket-doc__meta-value">{{ $import->booking_reference ?: '—' }}</span>
        </div>
        <div class="ticket-doc__meta-item">
            <span class="ticket-doc__meta-label">Prepared By</span>
            <span class="ticket-doc__meta-value">{{ $import->user?->name ?? '—' }}</span>
        </div>
        <div class="ticket-doc__meta-item">
            <span class="ticket-doc__meta-label">Generated On</span>
            <span class="ticket-doc__meta-value">{{ $import->confirmed_at ? format_datetime($import->confirmed_at) : '—' }}</span>
        </div>
    </div>

    <section class="ticket-doc__section">
        <div class="ticket-doc__section-head">Confirmed Flight Schedule</div>
        <div class="ticket-doc__schedule">
            <div class="ticket-doc__journey">
                <p class="ticket-doc__journey-label">Outbound Journey</p>
                @if ($outbound)
                    <p class="ticket-doc__journey-airline">{{ $outbound['airline'] ?? '—' }} · {{ $outbound['flight_number'] ?? '—' }}</p>
                    <p class="ticket-doc__journey-route">{{ $import->segmentRouteCode($outbound) }}</p>
                    <div class="ticket-doc__journey-times">
                        <div>
                            <span class="ticket-doc__time-label">Departure</span>
                            <strong>{{ $outbound['departure_time'] ?? '—' }}</strong>
                            <span>{{ $outbound['departure_date'] ?? '—' }}</span>
                        </div>
                        <div>
                            <span class="ticket-doc__time-label">Arrival</span>
                            <strong>{{ $outbound['arrival_time'] ?? '—' }}</strong>
                            <span>{{ $outbound['arrival_date'] ?? '—' }}</span>
                        </div>
                    </div>
                    <p class="ticket-doc__journey-locations">
                        {{ $outbound['departure_location'] ?? '—' }}
                        <span>→</span>
                        {{ $outbound['arrival_location'] ?? '—' }}
                    </p>
                @else
                    <p class="ticket-doc__empty">No outbound flight saved.</p>
                @endif
            </div>

            <div class="ticket-doc__journey">
                <p class="ticket-doc__journey-label">Return Journey</p>
                @if ($return)
                    <p class="ticket-doc__journey-airline">{{ $return['airline'] ?? '—' }} · {{ $return['flight_number'] ?? '—' }}</p>
                    <p class="ticket-doc__journey-route">{{ $import->segmentRouteCode($return) }}</p>
                    <div class="ticket-doc__journey-times">
                        <div>
                            <span class="ticket-doc__time-label">Departure</span>
                            <strong>{{ $return['departure_time'] ?? '—' }}</strong>
                            <span>{{ $return['departure_date'] ?? '—' }}</span>
                        </div>
                        <div>
                            <span class="ticket-doc__time-label">Arrival</span>
                            <strong>{{ $return['arrival_time'] ?? '—' }}</strong>
                            <span>{{ $return['arrival_date'] ?? '—' }}</span>
                        </div>
                    </div>
                    <p class="ticket-doc__journey-locations">
                        {{ $return['departure_location'] ?? '—' }}
                        <span>→</span>
                        {{ $return['arrival_location'] ?? '—' }}
                    </p>
                @else
                    <p class="ticket-doc__empty">No return flight saved.</p>
                @endif
            </div>
        </div>
    </section>

    <section class="ticket-doc__section">
        <div class="ticket-doc__section-head">Passenger &amp; Ticket Identity</div>
        <div class="ticket-doc__grid ticket-doc__grid--3">
            <div class="ticket-doc__field">
                <span class="ticket-doc__field-label">Passenger / Group Name</span>
                <span class="ticket-doc__field-value">{{ $import->passenger_name ?: '—' }}</span>
            </div>
            <div class="ticket-doc__field">
                <span class="ticket-doc__field-label">PNR</span>
                <span class="ticket-doc__field-value">{{ $import->agency_pnr ?: ($outbound['pnr'] ?? '—') }}</span>
            </div>
            <div class="ticket-doc__field">
                <span class="ticket-doc__field-label">Ticket Number</span>
                <span class="ticket-doc__field-value">{{ $import->ticket_number ?: '—' }}</span>
            </div>
        </div>
        <div class="ticket-doc__field ticket-doc__field--full">
            <span class="ticket-doc__field-label">Ticket Supplier</span>
            <span class="ticket-doc__field-value">{{ $import->agency_name ?: '—' }}</span>
        </div>
    </section>

    <section class="ticket-doc__section">
        <div class="ticket-doc__section-head">Ticketing &amp; Fare Details</div>
        <div class="ticket-doc__grid ticket-doc__grid--4">
            <div class="ticket-doc__field">
                <span class="ticket-doc__field-label">Booking Reference</span>
                <span class="ticket-doc__field-value">{{ $import->booking_reference ?: '—' }}</span>
            </div>
            <div class="ticket-doc__field">
                <span class="ticket-doc__field-label">Baggage Allowance</span>
                <span class="ticket-doc__field-value">{{ $outbound['baggage'] ?? ($return['baggage'] ?? '—') }}</span>
            </div>
            <div class="ticket-doc__field">
                <span class="ticket-doc__field-label">Ticket Cost</span>
                <span class="ticket-doc__field-value">
                    @if ($payment && $payment->amount_agreed > 0)
                        {{ number_format((float) $payment->amount_agreed, 0) }}
                    @else
                        —
                    @endif
                </span>
            </div>
            <div class="ticket-doc__field">
                <span class="ticket-doc__field-label">Currency</span>
                <span class="ticket-doc__field-value">{{ $currency }}</span>
            </div>
        </div>
    </section>

    <section class="ticket-doc__section">
        <div class="ticket-doc__section-head">Additional Information</div>
        <div class="ticket-doc__grid ticket-doc__grid--3">
            <div class="ticket-doc__field">
                <span class="ticket-doc__field-label">Airline</span>
                <span class="ticket-doc__field-value">{{ $outbound['airline'] ?? ($return['airline'] ?? '—') }}</span>
            </div>
            <div class="ticket-doc__field">
                <span class="ticket-doc__field-label">Flight Number</span>
                <span class="ticket-doc__field-value">{{ $import->flightNumbersLabel() }}</span>
            </div>
            <div class="ticket-doc__field">
                <span class="ticket-doc__field-label">Sector</span>
                <span class="ticket-doc__field-value">{{ $import->routeLabel() }}</span>
            </div>
        </div>

        @if (count($segments) > 2)
            <div class="ticket-doc__extra-segments">
                @foreach (array_slice($segments, 2) as $segment)
                    <div class="ticket-doc__extra-segment">
                        <strong>{{ $segment['flight_number'] ?? '—' }}</strong>
                        <span>{{ $import->segmentRouteCode($segment) }}</span>
                        <span>{{ $segment['departure_date'] ?? '—' }} {{ $segment['departure_time'] ?? '' }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="ticket-doc__grid ticket-doc__grid--2 ticket-doc__grid--muted">
            <div class="ticket-doc__field">
                <span class="ticket-doc__field-label">Class</span>
                <span class="ticket-doc__field-value">{{ $outbound['class'] ?? ($return['class'] ?? '—') }}</span>
            </div>
            <div class="ticket-doc__field">
                <span class="ticket-doc__field-label">Status</span>
                <span class="ticket-doc__field-value">{{ $outbound['status'] ?? ($return['status'] ?? '—') }}</span>
            </div>
            <div class="ticket-doc__field">
                <span class="ticket-doc__field-label">Frequent Flyer</span>
                <span class="ticket-doc__field-value">{{ $import->frequent_flyer ?: '—' }}</span>
            </div>
            <div class="ticket-doc__field">
                <span class="ticket-doc__field-label">Agency Phone</span>
                <span class="ticket-doc__field-value">{{ $import->agency_phone ?: '—' }}</span>
            </div>
        </div>
    </section>

    <section class="ticket-doc__section ticket-doc__section--terms">
        <div class="ticket-doc__section-head">{{ strtoupper($airline) }} &bull; Terms &amp; Conditions</div>

        <div class="ticket-doc__terms-intro">
            <div class="ticket-doc__terms-carrier">
                <span class="ticket-doc__terms-eyebrow">Selected Carrier &mdash; Only</span>
                <strong>{{ $airline }}</strong>
            </div>
            <p class="ticket-doc__terms-note">{{ config('ticket_carrier_terms.disclaimer') }}</p>
        </div>

        <ol class="ticket-doc__terms-list">
            @foreach ($carrierTerms as $term)
                <li class="ticket-doc__terms-item">{{ $term }}</li>
            @endforeach
        </ol>
    </section>

    <div class="ticket-doc__notice">
        <strong>Important — Final Authority</strong>
        <p>Refund and cancellation policies are governed by the respective fare rules applicable to this ticket. This document is generated from confirmed import data in the DHOTHAR operations system.</p>
    </div>

    <footer class="ticket-doc__footer">
        <div class="ticket-doc__footer-signatures">
            <div>
                <span class="ticket-doc__signature-line"></span>
                <span class="ticket-doc__signature-label">Prepared by</span>
                <strong>{{ $import->user?->name ?? '—' }}</strong>
            </div>
            <div>
                <span class="ticket-doc__signature-line"></span>
                <span class="ticket-doc__signature-label">Authorized approval</span>
                <strong>Operations Team</strong>
            </div>
        </div>
        <p class="ticket-doc__footer-contact">
            <x-admin-icon name="map-pin" :size="14" class="ticket-doc__footer-location-icon" />
            Office # 5, 2nd Floor,City Plaza, Main Commercial Market, Satellite Town, 5th Road Rawalpindi
            @if ($import->agency_phone)
                · {{ $import->agency_phone }}
            @endif
        </p>
    </footer>
</div>
