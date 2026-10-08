@extends('pdf.reports._layout')

@section('body')
    @if ($lowOnly)
        <p class="muted" style="margin:0 0 6px">Showing low-stock items only.</p>
    @endif
    <table class="grid">
        <tr>
            <th>Code</th>
            <th>Item</th>
            <th>Category</th>
            <th>Unit</th>
            <th class="num">On hand</th>
            <th class="num">Reorder</th>
            <th class="num">Unit cost</th>
            <th class="num">Stock value</th>
            <th>Status</th>
        </tr>
        @forelse ($items as $item)
            <tr>
                <td>{{ $item->code }}</td>
                <td>{{ $item->name }}</td>
                <td>{{ $item->category ?: '—' }}</td>
                <td>{{ $item->unit ?: '—' }}</td>
                <td class="num">{{ number_format((float) $item->qty_on_hand, 2) }}</td>
                <td class="num">{{ number_format((float) $item->reorder_level, 2) }}</td>
                <td class="num">{{ number_format((float) $item->unit_cost, 2) }}</td>
                <td class="num">{{ number_format($item->stockValue(), 2) }}</td>
                <td>{{ $item->isLowStock() ? 'LOW' : 'OK' }}</td>
            </tr>
        @empty
            <tr><td colspan="9" class="muted" style="text-align:center">No inventory items.</td></tr>
        @endforelse
        <tr class="grand">
            <td colspan="7">Total stock value</td>
            <td class="num">PKR {{ number_format($totalValue, 2) }}</td>
            <td></td>
        </tr>
    </table>
@endsection
