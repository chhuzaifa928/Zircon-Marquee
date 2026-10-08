@extends('pdf.reports._layout')

@section('body')
    @php $side = $debitNormal ? 'Dr' : 'Cr'; $fmt = fn ($n) => number_format(abs($n), 2).' '.($n < 0 ? ($debitNormal ? 'Cr' : 'Dr') : $side); @endphp

    <p style="font-size:11px; font-weight:bold; margin:0 0 6px">
        {{ $account->code }} — {{ $account->name }}
        <span class="muted" style="font-weight:normal">({{ ucfirst($account->type) }}, {{ $debitNormal ? 'Debit' : 'Credit' }} normal)</span>
    </p>

    <table class="grid">
        <tr>
            <th style="width:12%">Date</th>
            <th style="width:12%">Voucher</th>
            <th>Description</th>
            <th>Contra</th>
            <th class="num" style="width:13%">Debit</th>
            <th class="num" style="width:13%">Credit</th>
            <th class="num" style="width:15%">Balance</th>
        </tr>
        <tr class="subtotal">
            <td colspan="6">Opening balance (carried forward)</td>
            <td class="num">{{ $fmt($opening) }}</td>
        </tr>
        @forelse ($rows as $row)
            <tr>
                <td>{{ $row['date']?->format('d M Y') }}</td>
                <td>{{ $row['voucher_no'] }}</td>
                <td>{{ $row['description'] ?: '—' }}</td>
                <td>{{ $row['contra'] ?: '—' }}</td>
                <td class="num">{{ $row['debit'] ? number_format($row['debit'], 2) : '' }}</td>
                <td class="num">{{ $row['credit'] ? number_format($row['credit'], 2) : '' }}</td>
                <td class="num">{{ $fmt($row['balance']) }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="muted" style="text-align:center">No postings in this period.</td></tr>
        @endforelse
        <tr class="grand">
            <td colspan="6">Closing balance</td>
            <td class="num">{{ $fmt($closing) }}</td>
        </tr>
    </table>
@endsection
