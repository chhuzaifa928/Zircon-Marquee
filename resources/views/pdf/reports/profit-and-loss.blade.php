@extends('pdf.reports._layout')

@section('body')
    <table class="grid" style="width:49%; float:left">
        <tr class="section-head"><td colspan="2">Income</td></tr>
        @foreach ($report['income'] as $line)
            <tr>
                <td>{{ $line['account']->code }} — {{ $line['account']->name }}</td>
                <td class="num">{{ number_format($line['amount'], 2) }}</td>
            </tr>
        @endforeach
        <tr class="subtotal"><td>Total income</td><td class="num">{{ number_format($report['totalIncome'], 2) }}</td></tr>
    </table>

    <table class="grid" style="width:49%; float:right">
        <tr class="section-head"><td colspan="2">Expenses</td></tr>
        @foreach ($report['expense'] as $line)
            <tr>
                <td>{{ $line['account']->code }} — {{ $line['account']->name }}</td>
                <td class="num">{{ number_format($line['amount'], 2) }}</td>
            </tr>
        @endforeach
        <tr class="subtotal"><td>Total expenses</td><td class="num">{{ number_format($report['totalExpense'], 2) }}</td></tr>
    </table>

    <div style="clear:both"></div>

    @php $net = $report['netProfit']; @endphp
    <table class="grid">
        <tr class="grand">
            <td>Net {{ $net < 0 ? 'Loss' : 'Profit' }}</td>
            <td class="num">{{ $net < 0 ? '('.number_format(abs($net), 2).')' : number_format($net, 2) }}</td>
        </tr>
    </table>
@endsection
