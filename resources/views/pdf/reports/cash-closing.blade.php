@extends('pdf.reports._layout')

@section('body')
    @foreach ($report['accounts'] as $section)
        <table class="grid">
            <tr class="section-head">
                <td colspan="4">{{ $section['account']->code }} — {{ $section['account']->name }}</td>
                <td class="num">Opening: {{ number_format($section['opening'], 2) }}</td>
            </tr>
            <tr>
                <th style="width:14%">Date</th>
                <th style="width:14%">Voucher</th>
                <th>Remark</th>
                <th class="num" style="width:15%">In (Dr)</th>
                <th class="num" style="width:15%">Out (Cr)</th>
            </tr>
            @forelse ($section['rows'] as $row)
                <tr>
                    <td>{{ $row['date']?->format('d M Y') }}</td>
                    <td>{{ $row['voucher_no'] }}</td>
                    <td>{{ $row['remark'] ?: '—' }}@if($row['contra']) <span class="muted">({{ $row['contra'] }})</span>@endif</td>
                    <td class="num">{{ $row['in'] ? number_format($row['in'], 2) : '' }}</td>
                    <td class="num">{{ $row['out'] ? number_format($row['out'], 2) : '' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted" style="text-align:center">No movement in this period.</td></tr>
            @endforelse
            <tr class="subtotal">
                <td colspan="3">Collections / Payments</td>
                <td class="num">{{ number_format($section['total_in'], 2) }}</td>
                <td class="num">{{ number_format($section['total_out'], 2) }}</td>
            </tr>
            <tr class="subtotal">
                <td colspan="4">Closing balance</td>
                <td class="num">{{ number_format($section['closing'], 2) }}</td>
            </tr>
        </table>
    @endforeach

    <table class="grid">
        <tr class="grand">
            <td>Grand totals</td>
            <td class="num">Opening {{ number_format($report['totals']['opening'], 2) }}</td>
            <td class="num">In {{ number_format($report['totals']['in'], 2) }}</td>
            <td class="num">Out {{ number_format($report['totals']['out'], 2) }}</td>
            <td class="num">Closing {{ number_format($report['totals']['closing'], 2) }}</td>
        </tr>
    </table>
@endsection
