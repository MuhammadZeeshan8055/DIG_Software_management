<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $voucher->voucher_number }} — Verified Hotel Voucher</title>

    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v=3">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/hotel-voucher-print.css') }}">
</head>
<body class="ticket-doc-print-page invoice-verify-page hotel-voucher-print-page hotel-voucher-verify-page">
    <header class="invoice-verify-bar">
        <div class="invoice-verify-bar__inner">
            <div class="invoice-verify-bar__brand">
                <img src="{{ asset('images/logo-icon.png') }}" alt="DHOTHAR" class="invoice-verify-bar__logo">
                <div>
                    <strong>DHOTHAR</strong>
                    <span>Hotel voucher verification</span>
                </div>
            </div>

            <div class="invoice-verify-bar__status">
                <span class="invoice-verify-bar__check" aria-hidden="true">✓</span>
                <div>
                    <strong>Verified</strong>
                    <span>Live status from our records</span>
                </div>
            </div>

            <div class="invoice-verify-bar__meta">
                <span class="{{ $voucher->statusBadgeClass() }}">{{ $voucher->statusLabel() }}</span>
                <span class="invoice-verify-bar__number">{{ $voucher->voucher_number }}</span>
            </div>
        </div>
    </header>

    <main class="invoice-verify-main">
        <div class="ticket-doc-page ticket-doc-page--a4 hotel-voucher-doc-page" id="hv-print-page">
            <div class="hotel-voucher-doc-scale" id="hv-print-scale">
                <x-hotel-voucher-document :voucher="$voucher" />
            </div>
        </div>
    </main>
    <script src="{{ asset('js/hotel-voucher-print.js') }}" defer></script>
</body>
</html>
