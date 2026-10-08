@extends('pdf.reports._layout')

@section('body')
    <table class="grid">
        <tr>
            <th>Supplier</th>
            <th class="num" style="width:16%">Opening</th>
            <th class="num" style="width:16%">Receivings</th>
            <th class="num" style="width:16%">Payments</th>
            <th class="num" style="width:16%">Balance owed</th>
        </tr>
        @forelse ($report['rows'] as $row)
            <tr>
                <td>{{ $row['supplier']->code }} — {{ $row['supplier']->name }}</td>
                <td class="num">{{ number_format($row['opening'], 2) }}</td>
                <td class="num">{{ number_format($row['receivings'], 2) }}</td>
                <td class="num">{{ number_format($row['payments'], 2) }}</td>
                <td class="num">{{ number_format($row['closing'], 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="muted" style="text-align:center">No supplier accounts.</td></tr>
        @endforelse
        <tr class="grand">
            <td>Grand totals</td>
            <td class="num">{{ number_format($report['totals']['opening'], 2) }}</td>
            <td class="num">{{ number_format($report['totals']['receivings'], 2) }}</td>
            <td class="num">{{ number_format($report['totals']['payments'], 2) }}</td>
            <td class="num">{{ number_format($report['totals']['closing'], 2) }}</td>
        </tr>
    </table>
@endsection
