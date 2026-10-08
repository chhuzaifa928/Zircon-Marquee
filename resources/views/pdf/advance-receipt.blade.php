<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Advance Receipt — {{ $booking->booking_no }}</title>
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
        .onaccount { text-align: center; font-size: 11px; margin-bottom: 10px; color: #444; }
        table.grid { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.grid th, table.grid td { border: 1px solid #ccc; padding: 5px 7px; text-align: left; }
        table.grid th { background: #f3ecd9; font-weight: bold; }
        .num { text-align: right; }
        .total-row td { background: #8a6400; color: #fff; font-weight: bold; font-size: 12px; }
        .empty { text-align: center; color: #888; padding: 16px; }
        .sign { margin-top: 40px; width: 100%; }
        .sign td { width: 50%; font-size: 10px; padding-top: 24px; }
        .sign .line { border-top: 1px solid #333; padding-top: 3px; width: 70%; }
        .foot { margin-top: 14px; font-size: 8px; color: #888; text-align: center;
            border-top: 1px solid #ddd; padding-top: 6px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="header">
        <div class="company">{{ $settings->company_name ?? 'Zircon Marquee' }}</div>
        <div class="company-meta">
            {{ $settings->company_address }}@if($settings->company_phone) · {{ $settings->company_phone }}@endif
            @if($settings->company_email) · {{ $settings->company_email }}@endif
        </div>
    </div>

    <div class="doc-title">Advance Receipt</div>

    <table class="ids"><tr>
        <td><strong>Host:</strong> {{ $booking->customer?->name }}</td>
        <td><strong>Client ID:</strong> {{ $booking->customer?->code }}</td>
        <td style="text-align:right"><strong>Booking No (Event ID):</strong> {{ $booking->booking_no }}</td>
    </tr><tr>
        <td><strong>Function Date:</strong> {{ $booking->event_date?->format('d M Y') }} ({{ ucfirst($booking->slot) }})</td>
        <td><strong>Hall:</strong> {{ $booking->hall?->name }}</td>
        <td style="text-align:right"><strong>Generated:</strong> {{ now()->format('d M Y H:i') }}</td>
    </tr></table>

    <div class="onaccount"><strong>On Account Of:</strong> Banquet Booking</div>

    <table class="grid">
        <thead><tr>
            <th style="width:8%">#</th>
            <th style="width:18%">Date</th>
            <th style="width:16%">Slip No</th>
            <th>Received Into</th>
            <th style="width:20%">Created By</th>
            <th class="num" style="width:18%">Amount</th>
        </tr></thead>
        <tbody>
            @forelse($slips as $i => $slip)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $slip->date?->format('d M Y') }}</td>
                    <td>{{ $slip->slip_no }}</td>
                    <td>{{ $slip->account?->name }}@if($slip->method) <small>({{ ucwords(str_replace('_', ' ', $slip->method)) }})</small>@endif</td>
                    <td>{{ $slip->createdBy?->name ?? '—' }}</td>
                    <td class="num">{{ number_format($slip->amount, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">No payments recorded yet.</td></tr>
            @endforelse
            <tr class="total-row">
                <td colspan="5">Total Received</td>
                <td class="num">PKR {{ number_format($slips->sum('amount'), 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="grid" style="width:55%; float:right">
        <tr><th>Grand Total</th><td class="num">PKR {{ number_format($booking->grand_total, 2) }}</td></tr>
        <tr><th>Total Received</th><td class="num">PKR {{ number_format($booking->advance_total, 2) }}</td></tr>
        <tr><th>Balance Due</th><td class="num">PKR {{ number_format($booking->due, 2) }}</td></tr>
    </table>
    <div style="clear:both"></div>

    <table class="sign">
        <tr>
            <td><div class="line">Received By</div></td>
            <td style="text-align:right"><div class="line" style="margin-left:auto">For {{ $settings->company_name ?? 'Zircon Marquee' }}</div></td>
        </tr>
    </table>

    <div class="foot">
        {{ $booking->booking_no }} · Client {{ $booking->customer?->code }} · Generated {{ now()->format('d M Y H:i') }} by {{ auth()->user()?->name }} · Built by Khan Group of Technologies
    </div>
</div>
</body>
</html>
