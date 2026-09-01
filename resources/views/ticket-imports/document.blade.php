<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $import->documentSerial() }} — Ticket Document</title>

    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v=3">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body class="ticket-doc-print-page">
    <div class="ticket-doc-page__toolbar">
        <p>{{ $import->passenger_name ?: 'Ticket document' }} · {{ $import->documentSerial() }}</p>
        <button type="button" class="hero-btn hero-btn--primary" onclick="window.print()">Save as PDF</button>
    </div>

    <div class="ticket-doc-page ticket-doc-page--a4">
        <x-ticket-document :import="$import" />
    </div>

    @if ($autoPrint)
        <script>window.addEventListener('load', () => setTimeout(() => window.print(), 300));</script>
    @endif
</body>
</html>
