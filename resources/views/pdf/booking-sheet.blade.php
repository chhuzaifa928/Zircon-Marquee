<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Event Booking Sheet — {{ $booking->booking_no }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; margin: 0; }
        .wrap { padding: 24px 28px; }
        .header { border-bottom: 2px solid #b8860b; padding-bottom: 10px; margin-bottom: 12px; }
        .company { font-size: 20px; font-weight: bold; color: #8a6400; }
        .company-meta { font-size: 10px; color: #555; margin-top: 2px; }
        .doc-title { text-align: center; font-size: 14px; font-weight: bold; letter-spacing: 1px;
            text-transform: uppercase; margin: 10px 0 4px; }
        .ids { width: 100%; font-size: 10px; color: #333; margin-bottom: 10px; }
        .ids td { padding: 1px 0; }
        table.grid { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.grid th, table.grid td { border: 1px solid #ccc; padding: 4px 6px; text-align: left;
            vertical-align: top; }
        table.grid th { background: #f3ecd9; font-weight: bold; }
        .section-title { font-size: 11px; font-weight: bold; text-transform: uppercase; color: #8a6400;
            border-bottom: 1px solid #e0d4ad; margin: 12px 0 6px; padding-bottom: 2px; }
        .kv { width: 100%; border-collapse: collapse; }
        .kv td { padding: 3px 6px; vertical-align: top; }
        .kv .label { color: #666; width: 110px; }
        .num { text-align: right; }
        .totals { width: 55%; float: right; border-collapse: collapse; }
        .totals td { padding: 4px 6px; border: 1px solid #ccc; }
        .totals .label { background: #f3ecd9; font-weight: bold; }
        .grand td { background: #8a6400; color: #fff; font-weight: bold; font-size: 12px; }
        .notes { font-size: 10px; white-space: pre-line; }
        .terms { font-size: 9px; color: #444; margin-top: 10px; line-height: 1.4; }
        .sign { margin-top: 36px; width: 100%; }
        .sign td { width: 50%; font-size: 10px; padding-top: 24px; }
        .sign .line { border-top: 1px solid #333; padding-top: 3px; width: 70%; }
        .foot { margin-top: 14px; font-size: 8px; color: #888; text-align: center;
            border-top: 1px solid #ddd; padding-top: 6px; }
        .clear { clear: both; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="header">
        <table style="width:100%"><tr>
            <td>
                <div class="company">{{ $settings->company_name ?? 'Zircon Marquee' }}</div>
                <div class="company-meta">
                    {{ $settings->company_address }}@if($settings->company_phone) · {{ $settings->company_phone }}@endif
                    @if($settings->company_email) · {{ $settings->company_email }}@endif
                </div>
            </td>
        </tr></table>
    </div>

    <div class="doc-title">Event Booking Sheet</div>

    <table class="ids"><tr>
        <td><strong>Booking No (Event ID):</strong> {{ $booking->booking_no }}</td>
        <td><strong>Client ID:</strong> {{ $booking->customer?->code }}</td>
        <td style="text-align:right"><strong>Status:</strong> {{ ucfirst($booking->status) }}</td>
    </tr><tr>
        <td><strong>Booking Date:</strong> {{ $booking->booking_date?->format('d M Y') }}</td>
        <td><strong>Book By:</strong> {{ $booking->createdBy?->name ?? '—' }}</td>
        <td style="text-align:right"><strong>Generated:</strong> {{ now()->format('d M Y H:i') }}</td>
    </tr></table>

    <div class="section-title">Host &amp; Event</div>
    <table class="grid">
        <tr>
            <th style="width:25%">Host</th>
            <td style="width:25%">{{ $booking->customer?->name }}
                @if($booking->customer?->care_of)<br><small>c/o {{ $booking->customer->care_of }}</small>@endif
            </td>
            <th style="width:25%">Contact</th>
            <td style="width:25%">{{ $booking->customer?->phone }}</td>
        </tr>
        <tr>
            <th>CNIC</th>
            <td>{{ $booking->customer?->cnic ?? '—' }}</td>
            <th>Address</th>
            <td>{{ $booking->customer?->address ?? '—' }}</td>
        </tr>
        <tr>
            <th>Event Nature</th>
            <td>{{ $booking->event_type ?? '—' }}</td>
            <th>Hall / Venue</th>
            <td>{{ $booking->hall?->name }}</td>
        </tr>
        <tr>
            <th>Event Date / Day</th>
            <td>{{ $booking->event_date?->format('d M Y') }} ({{ $booking->event_date?->format('l') }})</td>
            <th>Slot / Time</th>
            <td>{{ ucfirst($booking->slot) }}
                @if($booking->start_time) · {{ \Illuminate\Support\Carbon::parse($booking->start_time)->format('h:i A') }}@endif
                @if($booking->end_time) – {{ \Illuminate\Support\Carbon::parse($booking->end_time)->format('h:i A') }}@endif
            </td>
        </tr>
        <tr>
            <th>Guaranteed Guests</th>
            <td>{{ $booking->guests }}</td>
            <th>Rack / Discounted Rate</th>
            <td>PKR {{ number_format($booking->rack_rate, 2) }} / PKR {{ number_format($booking->discounted_rate, 2) }}</td>
        </tr>
    </table>

    @if($booking->foods->isNotEmpty())
        <div class="section-title">Menu</div>
        <table class="grid">
            <thead><tr><th style="width:8%">#</th><th>Dish</th><th>اردو نام</th></tr></thead>
            <tbody>
            @foreach($booking->foods as $i => $dish)
                <tr><td>{{ $i + 1 }}</td><td>{{ $dish->name }}</td><td>{{ $dish->urdu_name }}</td></tr>
            @endforeach
            </tbody>
        </table>
    @endif

    <div class="section-title">Charges</div>
    <table class="grid">
        <thead><tr>
            <th>Item</th><th class="num">Guests / Qty</th><th class="num">Rate</th><th class="num">Amount</th>
        </tr></thead>
        <tbody>
            <tr>
                <td>Per-head charge (menu / venue)</td>
                <td class="num">{{ $booking->guests }}</td>
                <td class="num">{{ number_format($booking->discounted_rate, 2) }}</td>
                <td class="num">{{ number_format($booking->headcharge_total, 2) }}</td>
            </tr>
            @foreach($booking->charges as $charge)
                <tr>
                    <td>{{ $chargeLabel($charge->charge_type) }}@if($charge->description) — {{ $charge->description }}@endif</td>
                    <td class="num">{{ rtrim(rtrim(number_format($charge->quantity, 2), '0'), '.') }}</td>
                    <td class="num">{{ number_format($charge->unit_price, 2) }}</td>
                    <td class="num">{{ number_format($charge->amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td class="label">Head-charge Total</td><td class="num">PKR {{ number_format($booking->headcharge_total, 2) }}</td></tr>
        <tr><td class="label">Additional Charges</td><td class="num">PKR {{ number_format($booking->charges_total, 2) }}</td></tr>
        <tr><td class="label">Subtotal</td><td class="num">PKR {{ number_format($booking->subtotal, 2) }}</td></tr>
        <tr><td class="label">GST ({{ rtrim(rtrim(number_format($booking->gst_rate, 2), '0'), '.') }}%)</td><td class="num">PKR {{ number_format($booking->gst_amount, 2) }}</td></tr>
        <tr class="grand"><td>Grand Total</td><td class="num">PKR {{ number_format($booking->grand_total, 2) }}</td></tr>
        <tr><td class="label">Advance Received</td><td class="num">PKR {{ number_format($booking->advance_total, 2) }}</td></tr>
        <tr><td class="label">Balance Due</td><td class="num">PKR {{ number_format($booking->due, 2) }}</td></tr>
    </table>
    <div class="clear"></div>

    @if($booking->arrangements || $booking->bulletin_board)
        <div class="section-title">Arrangements &amp; Bulletin Board</div>
        @if($booking->arrangements)<div class="notes"><strong>Arrangements:</strong> {{ $booking->arrangements }}</div>@endif
        @if($booking->bulletin_board)<div class="notes"><strong>Bulletin Board:</strong> {{ $booking->bulletin_board }}</div>@endif
    @endif

    <div class="section-title">Terms &amp; Conditions</div>
    <div class="terms">
        1. This booking sheet is a provisional contract; the slot is secured only on confirmation and advance payment.
        2. The per-head bill is calculated on the discounted rate × guaranteed guests. Billing is on the guaranteed count or actual attendance, whichever is higher.
        3. GST is charged at the rate shown and is non-negotiable. 4. Advances are non-refundable on cancellation.
        5. The management is not responsible for guests' valuables. 6. Any additional services availed on the event day will be billed separately.
    </div>

    <table class="sign">
        <tr>
            <td><div class="line">Host Signature</div></td>
            <td style="text-align:right"><div class="line" style="margin-left:auto">For {{ $settings->company_name ?? 'Zircon Marquee' }}</div></td>
        </tr>
    </table>

    <div class="foot">
        {{ $booking->booking_no }} · Client {{ $booking->customer?->code }} · Generated {{ now()->format('d M Y H:i') }} by {{ auth()->user()?->name }} · Built by Khan Group of Technologies
    </div>
</div>
</body>
</html>
