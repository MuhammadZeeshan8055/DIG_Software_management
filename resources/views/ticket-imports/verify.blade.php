@php
    $segments = array_values(array_filter($import->flight_segments ?? []));
    $outbound = $segments[0] ?? [];
    $payment = $import->paymentEntry;
    $currency = config('payment_status.currency', 'PKR');
    $pnr = $import->agency_pnr ?: ($outbound['pnr'] ?? null);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0085b6">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $import->documentSerial() }} — Ticket verification | DHOTHAR</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v=3">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/ticket-verify.css') }}?v={{ filemtime(public_path('css/ticket-verify.css')) }}">
</head>
<body>
    <svg class="icon-library" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <symbol id="icon-check" viewBox="0 0 24 24"><path d="m7 12 3 3 7-7"/><path d="M12 3 4 6v6c0 4 4 7 8 9 4-2 8-5 8-9V6Z"/></symbol>
        <symbol id="icon-plane" viewBox="0 0 24 24"><path d="m22 2-7 20-4-9-9-4Z M22 2 11 13"/></symbol>
    </svg>
    <header class="site-header">
        <div class="page-width header-inner">
            <div class="brand">
                <img src="{{ asset('images/logo-icon.png') }}" alt="DHOTHAR logo" width="56" height="68">
                <div><strong>DHOTHAR</strong><span>International Group</span><small>Travel &amp; Tours (Pvt Ltd)</small></div>
            </div>
            <span class="header-label"><svg class="icon" aria-hidden="true"><use href="#icon-check"/></svg> Verified</span>
        </div>
    </header>
    <main class="page-width">
        <div class="ticket-layout">
            <div class="journey-column">
                <section class="passenger-card" aria-labelledby="passenger-title">
                    <h2 class="card-title">Passenger &amp; ticket identity</h2>
                    <div class="passenger-heading">
                        <p class="field-label">Official ticket document</p>
                        <h1 class="passenger-name" id="passenger-title">{{ $import->passenger_name ?: 'Passenger' }}</h1>
                        <p class="document-serial">{{ $import->documentSerial() }}</p>
                    </div>
                    <div class="ticket-identifiers">
                        <div><span class="field-label">Booking PNR</span><strong class="pnr">{{ $pnr ?: '—' }}</strong></div>
                        <div><span class="field-label">Ticket number</span><strong>{{ $import->ticket_number ?: '—' }}</strong></div>
                    </div>
                </section>
                <section class="itinerary" aria-labelledby="itinerary-title">
                    <div class="section-heading"><h2 id="itinerary-title">Flight schedule</h2></div>
                    @forelse ($segments as $segment)
                        <article class="flight-card" aria-label="Flight {{ $loop->iteration }}">
                            <div class="flight-heading">
                                <div class="flight-airline"><span class="airline-icon"><svg class="icon" aria-hidden="true"><use href="#icon-plane"/></svg></span><div><h3>{{ $segment['airline'] ?? 'Airline not provided' }}</h3><p>{{ $segment['flight_number'] ?? '—' }}</p></div></div>
                                <span class="journey-label">{{ $loop->first ? 'Outbound' : ($loop->iteration === 2 ? 'Return / onward' : 'Onward') }}</span>
                            </div>
                            <div class="flight-route">
                                <div class="airport"><span class="field-label">Departure</span><strong class="airport-code">{{ !empty($segment['departure_location']) ? $import->locationCode($segment['departure_location']) : '—' }}</strong><p class="airport-name">{{ $segment['departure_location'] ?? '—' }}</p><strong class="flight-time">{{ $segment['departure_time'] ?? '—' }}</strong><span class="flight-date">{{ $segment['departure_date'] ?? '—' }}</span></div>
                                <div class="route-connector" aria-hidden="true"><span></span><svg class="icon"><use href="#icon-plane"/></svg><span></span></div>
                                <div class="airport airport-arrival"><span class="field-label">Arrival</span><strong class="airport-code">{{ !empty($segment['arrival_location']) ? $import->locationCode($segment['arrival_location']) : '—' }}</strong><p class="airport-name">{{ $segment['arrival_location'] ?? '—' }}</p><strong class="flight-time">{{ $segment['arrival_time'] ?? '—' }}</strong><span class="flight-date">{{ $segment['arrival_date'] ?? '—' }}</span></div>
                            </div>
                            <dl class="flight-extras"><div><dt>Cabin class</dt><dd>{{ $segment['class'] ?? '—' }}</dd></div><div><dt>Baggage</dt><dd>{{ $segment['baggage'] ?? '—' }}</dd></div><div><dt>Flight status</dt><dd>{{ $segment['status'] ?? '—' }}</dd></div></dl>
                        </article>
                    @empty
                        <div class="empty-state"><svg class="icon" aria-hidden="true"><use href="#icon-plane"/></svg><h3>Flight details are not available yet</h3><p>Please contact your travel agent for your itinerary.</p></div>
                    @endforelse
                </section>
            </div>
            <aside class="details-column" aria-label="Booking information">
                <section class="details-card">
                    <h2>Booking details</h2>
                    <dl class="booking-list">
                        <div><dt>Document number</dt><dd>{{ $import->documentSerial() }}</dd></div>
                        @if ($import->booking_reference)
                            <div><dt>Booking reference</dt><dd>{{ $import->booking_reference }}</dd></div>
                        @endif
                        <div><dt>Supplier</dt><dd>{{ $import->agency_name ?: '—' }}</dd></div>
                        <div><dt>Prepared by</dt><dd>{{ $import->user?->name ?? '—' }}</dd></div>
                        <div><dt>Issued on</dt><dd>{{ $import->confirmed_at ? format_datetime($import->confirmed_at) : '—' }}</dd></div>
                    </dl>
                    <div class="ticket-cost"><span>Ticket cost</span><strong>@if ($payment && $payment->amount_agreed > 0)<small>{{ $currency }}</small> {{ number_format((float) $payment->amount_agreed, 0) }}@else — @endif</strong></div>
                </section>
            </aside>
        </div>
        <footer class="site-footer"><div class="authority-note"><strong>Important — final authority</strong><p>This page verifies an official DHOTHAR ticket record. Refunds and cancellations follow the fare rules for this ticket.</p></div><p class="office-address">Office # 5, 2nd Floor, City Plaza, Main Commercial Market, Satellite Town, 5th Road, Rawalpindi @if ($import->agency_phone)<br>{{ $import->agency_phone }}@endif</p></footer>
    </main>
</body>
</html>
