@props(['voucher'])

@php
    $guests = $voucher->guests ?? [];
    $stays = $voucher->stays ?? [];
    $flights = $voucher->flights ?? [];
    $transport = $voucher->transport ?? [];
    $status = $voucher->status === 'issued' ? 'Issued' : 'Draft';
@endphp

<div {{ $attributes->merge(['class' => 'ticket-doc hotel-voucher-doc']) }}>
    <header class="ticket-doc__header">
        <div class="ticket-doc__brand-card">
            <img src="{{ asset('images/logo-icon.png') }}" alt="DHOTHAR" class="ticket-doc__logo">
            <div class="ticket-doc__brand-text">
                <strong class="ticket-doc__brand-name">DHOTHAR</strong>
                <span class="ticket-doc__brand-group">International Group</span>
                <small class="ticket-doc__brand-tagline">Travel &amp; Tours (Pvt Ltd)</small>
            </div>
        </div>

        <div class="ticket-doc__qr" title="Scan to verify voucher">
            {!! QrCode::format('svg')->encoding('UTF-8')->size(72)->margin(0)->generate($voucher->qrPayload()) !!}
        </div>

        <div class="ticket-doc__title-block">
            <p class="ticket-doc__eyebrow">Official Operations Document</p>
            <h1 class="ticket-doc__title">Umrah Hotel Voucher</h1>
            <p class="ticket-doc__subtitle">Accommodation &amp; Ground Services Confirmation</p>
            <div class="ticket-doc__badges">
                <span class="ticket-doc__badge ticket-doc__badge--success">{{ $status }}</span>
                <span class="ticket-doc__badge ticket-doc__badge--success">Approved</span>
            </div>
        </div>
    </header>

    <div class="ticket-doc__meta-row">
        <div class="ticket-doc__meta-item">
            <span class="ticket-doc__meta-label">Voucher Number</span>
            <span class="ticket-doc__meta-value">{{ $voucher->show($voucher->voucher_number) }}</span>
        </div>
        <div class="ticket-doc__meta-item">
            <span class="ticket-doc__meta-label">Issue Date</span>
            <span class="ticket-doc__meta-value">{{ optional($voucher->issue_date)->format('d M Y') ?: '—' }}</span>
        </div>
        <div class="ticket-doc__meta-item">
            <span class="ticket-doc__meta-label">Booking Reference</span>
            <span class="ticket-doc__meta-value">{{ $voucher->show($voucher->booking_reference) }}</span>
        </div>
        <div class="ticket-doc__meta-item">
            <span class="ticket-doc__meta-label">Group Number</span>
            <span class="ticket-doc__meta-value">{{ $voucher->show($voucher->group_number) }}</span>
        </div>
    </div>

    <section class="ticket-doc__section">
        <div class="ticket-doc__section-head">Booking &amp; Passenger Summary</div>
        <div class="ticket-doc__grid ticket-doc__grid--3">
            <div class="ticket-doc__field">
                <span class="ticket-doc__field-label">Family / Group Head</span>
                <span class="ticket-doc__field-value">{{ $voucher->show($voucher->family_head) }}</span>
            </div>
            <div class="ticket-doc__field">
                <span class="ticket-doc__field-label">Agent</span>
                <span class="ticket-doc__field-value">{{ $voucher->show($voucher->agent_customer) }}</span>
            </div>
            <div class="ticket-doc__field">
                <span class="ticket-doc__field-label">Country</span>
                <span class="ticket-doc__field-value">{{ $voucher->show($voucher->country) }}</span>
            </div>
            <div class="ticket-doc__field">
                <span class="ticket-doc__field-label">Package / Program</span>
                <span class="ticket-doc__field-value">{{ $voucher->show($voucher->package_program) }}</span>
            </div>
            <div class="ticket-doc__field">
                <span class="ticket-doc__field-label">Visa Type</span>
                <span class="ticket-doc__field-value">{{ $voucher->show($voucher->visa_type) }}</span>
            </div>
            <div class="ticket-doc__field">
                <span class="ticket-doc__field-label">Pax / Beds</span>
                <span class="ticket-doc__field-value">{{ $voucher->paxBedsLabel() }}</span>
            </div>
        </div>
        <div class="ticket-doc__field ticket-doc__field--full">
            <span class="ticket-doc__field-label">Service Scope</span>
            <span class="ticket-doc__field-value">{{ $voucher->show($voucher->service_scope) }}</span>
        </div>
    </section>

    <section class="ticket-doc__section">
        <div class="ticket-doc__section-head">Mutamers / Guest Manifest</div>
        <div class="hv-doc-table">
            <div class="hv-doc-table__head hv-doc-table__head--guests">
                <span>#</span>
                <span>Passenger Name</span>
                <span>Passport</span>
                <span>Gender</span>
                <span>Pax</span>
                <span>Bed</span>
                <span>MOFA</span>
                <span>Visa</span>
                <span>PNR</span>
            </div>
            @forelse ($guests as $i => $guest)
                <div class="hv-doc-table__row hv-doc-table__row--guests">
                    <span>{{ $i + 1 }}</span>
                    <span>{{ $voucher->show($guest['passenger_name'] ?? null) }}</span>
                    <span>{{ $voucher->show($guest['passport'] ?? null) }}</span>
                    <span>{{ $voucher->show($guest['gender'] ?? null) }}</span>
                    <span>{{ $voucher->show($guest['pax_type'] ?? null) }}</span>
                    <span>{{ strtoupper($voucher->show($guest['bed'] ?? null)) }}</span>
                    <span>{{ $voucher->show($guest['mofa'] ?? null) }}</span>
                    <span>{{ $voucher->show($guest['visa'] ?? null) }}</span>
                    <span>{{ $voucher->show($guest['pnr'] ?? null) }}</span>
                </div>
            @empty
                <div class="hv-doc-table__empty">No guests recorded.</div>
            @endforelse
        </div>
    </section>

    <section class="ticket-doc__section">
        <div class="ticket-doc__section-head">Accommodation Confirmation</div>
        <div class="hv-doc-table">
            <div class="hv-doc-table__head hv-doc-table__head--stays">
                <span>#</span>
                <span>City</span>
                <span>Hotel / Location</span>
                <span>Category</span>
                <span>Room / Qty</span>
                <span>Meal</span>
                <span>Confirmation</span>
                <span>Check-in</span>
                <span>Check-out</span>
                <span>Nights</span>
            </div>
            @forelse ($stays as $i => $stay)
                <div class="hv-doc-table__row hv-doc-table__row--stays">
                    <span>{{ $i + 1 }}</span>
                    <span>{{ $voucher->show($stay['city'] ?? null) }}</span>
                    <span>
                        {{ $voucher->show($stay['hotel'] ?? null) }}
                        @if (! empty($stay['distance']))
                            <small class="hv-doc-muted"> · {{ $stay['distance'] }}</small>
                        @endif
                    </span>
                    <span>{{ $voucher->show($stay['category'] ?? null) }}</span>
                    <span>{{ trim(($stay['room_type'] ?? '').' × '.($stay['rooms'] ?? '—')) }}</span>
                    <span>{{ $voucher->show($stay['meal'] ?? null) }}</span>
                    <span>{{ $voucher->show($stay['confirmation'] ?? null) }}</span>
                    <span>{{ $voucher->showDate($stay['check_in'] ?? null) }}</span>
                    <span>{{ $voucher->showDate($stay['check_out'] ?? null) }}</span>
                    <span>{{ $voucher->showNights($stay) }}</span>
                </div>
            @empty
                <div class="hv-doc-table__empty">No stays recorded.</div>
            @endforelse
        </div>
    </section>

    <section class="ticket-doc__section">
        <div class="ticket-doc__section-head">Flight Information</div>
        <div class="hv-doc-table">
            <div class="hv-doc-table__head hv-doc-table__head--flights">
                <span>Type</span>
                <span>Airline</span>
                <span>Flight</span>
                <span>Sector</span>
                <span>Departure</span>
                <span>Arrival</span>
            </div>
            @forelse ($flights as $flight)
                <div class="hv-doc-table__row hv-doc-table__row--flights">
                    <span>{{ $voucher->show($flight['type'] ?? null) }}</span>
                    <span>{{ $voucher->show($flight['airline'] ?? null) }}</span>
                    <span>{{ $voucher->show($flight['flight'] ?? null) }}</span>
                    <span>{{ $voucher->flightSector($flight) }}</span>
                    <span>{{ $voucher->showDateTime($flight['departure_at'] ?? null) }}</span>
                    <span>{{ $voucher->showDateTime($flight['arrival_at'] ?? null) }}</span>
                </div>
            @empty
                <div class="hv-doc-table__empty">No flights recorded.</div>
            @endforelse
        </div>
    </section>

    <section class="ticket-doc__section">
        <div class="ticket-doc__section-head">Transport &amp; Ground Services</div>
        <div class="hv-doc-table">
            <div class="hv-doc-table__head hv-doc-table__head--transport">
                <span>Date</span>
                <span>Route / Service</span>
                <span>Vehicle</span>
                <span>Supplier / Driver</span>
                <span>Reference</span>
            </div>
            @forelse ($transport as $row)
                <div class="hv-doc-table__row hv-doc-table__row--transport">
                    <span>{{ $voucher->showDate($row['date'] ?? null) }}</span>
                    <span>{{ $voucher->show($row['route_service'] ?? null) }}</span>
                    <span>{{ $voucher->show($row['vehicle'] ?? null) }}</span>
                    <span>{{ $voucher->show($row['supplier_driver'] ?? null) }}</span>
                    <span>{{ $voucher->show($row['reference'] ?? null) }}</span>
                </div>
            @empty
                <div class="hv-doc-table__empty">No transport services recorded.</div>
            @endforelse
        </div>
    </section>

    @if ($voucher->special_instructions)
        <section class="ticket-doc__section">
            <div class="ticket-doc__section-head">Special Instructions / Inclusions</div>
            <div class="ticket-doc__field ticket-doc__field--full">
                <span class="ticket-doc__field-value" style="white-space: pre-wrap;">{{ $voucher->special_instructions }}</span>
            </div>
        </section>
    @endif

    <div class="ticket-doc__notice">
        <strong>Important — Guest Guidance</strong>
        <p>
            Please verify hotel confirmation numbers, check-in windows and flight timings before travel.
            KSA helpline: {{ $voucher->show($voucher->ksa_helpline) }} ·
            Pakistan helpline: {{ $voucher->show($voucher->pakistan_helpline) }}.
        </p>
    </div>

    <footer class="ticket-doc__footer">
        <div class="ticket-doc__footer-signatures">
            <div>
                <span class="ticket-doc__signature-line"></span>
                <span class="ticket-doc__signature-label">Prepared by</span>
                <strong>{{ $voucher->show($voucher->prepared_by ?: $voucher->user?->name) }}</strong>
            </div>
            <div>
                <span class="ticket-doc__signature-line"></span>
                <span class="ticket-doc__signature-label">Authorized approval</span>
                <strong>Operations Team</strong>
            </div>
        </div>
        <p class="ticket-doc__footer-contact">
            <x-admin-icon name="map-pin" :size="14" class="ticket-doc__footer-location-icon" />
            Office # 5, 2nd Floor, City Plaza, Main Commercial Market, Satellite Town, 5th Road Rawalpindi
            @if ($voucher->pakistan_helpline)
                · {{ $voucher->pakistan_helpline }}
            @endif
        </p>
    </footer>
</div>
