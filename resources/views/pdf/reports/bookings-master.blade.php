@extends('pdf.reports._layout')

@section('body')
    <table class="grid">
        <tr>
            <th>Booking</th>
            <th>Date</th>
            <th>Host</th>
            <th>Hall</th>
            <th>Event</th>
            <th class="num">Guests</th>
            <th class="num">Total</th>
            <th class="num">Payments</th>
            <th class="num">Balance</th>
            <th class="num">Expense</th>
            <th class="num">Net Profit</th>
        </tr>
        @forelse ($report['rows'] as $row)
            @php $b = $row['booking']; @endphp
            <tr>
                <td>{{ $b->booking_no }}</td>
                <td>{{ $b->event_date?->format('d M Y') }}</td>
                <td>{{ $b->customer?->name }}</td>
                <td>{{ $b->hall?->name }}</td>
                <td>{{ $b->event_type ?: '—' }}</td>
                <td class="num">{{ $b->guests }}</td>
                <td class="num">{{ number_format($row['total'], 2) }}</td>
                <td class="num">{{ number_format($row['payments'], 2) }}</td>
                <td class="num">{{ number_format($row['balance'], 2) }}</td>
                <td class="num">{{ number_format($row['expense'], 2) }}</td>
                <td class="num">{{ number_format($row['profit'], 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="11" class="muted" style="text-align:center">No events in this period.</td></tr>
        @endforelse
        <tr class="grand">
            <td colspan="6">{{ $report['count'] }} event(s)</td>
            <td class="num">{{ number_format($report['totals']['total'], 2) }}</td>
            <td class="num">{{ number_format($report['totals']['payments'], 2) }}</td>
            <td class="num">{{ number_format($report['totals']['balance'], 2) }}</td>
            <td class="num">{{ number_format($report['totals']['expense'], 2) }}</td>
            <td class="num">{{ number_format($report['totals']['profit'], 2) }}</td>
        </tr>
    </table>
    <p class="muted" style="font-size:8px">Expense &amp; Net Profit read from event costing (recorded after full payment); zero until costs are entered.</p>
@endsection
