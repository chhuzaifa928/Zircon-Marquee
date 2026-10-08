<x-filament-panels::page>
    <div class="space-y-5">
        @include('filament.pages.reports._toolbar')

        @php
            $report = $this->report;
            $money = fn ($n) => number_format($n, 2);
        @endphp

        <p class="text-xs text-gray-400">Expense &amp; Net Profit read from event costing (recorded after full payment); zero until costs are entered.</p>

        <div class="overflow-x-auto rounded-xl ring-1 ring-gray-200 dark:ring-white/10">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-white/5 text-left text-gray-500">
                    <tr>
                        <th class="px-3 py-2">Booking</th>
                        <th class="px-3 py-2">Date</th>
                        <th class="px-3 py-2">Host</th>
                        <th class="px-3 py-2">Hall</th>
                        <th class="px-3 py-2">Event</th>
                        <th class="px-3 py-2 text-right">Guests</th>
                        <th class="px-3 py-2 text-right">Total</th>
                        <th class="px-3 py-2 text-right">Payments</th>
                        <th class="px-3 py-2 text-right">Balance</th>
                        <th class="px-3 py-2 text-right">Expense</th>
                        <th class="px-3 py-2 text-right">Net Profit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @forelse ($report['rows'] as $row)
                        @php $b = $row['booking']; @endphp
                        <tr>
                            <td class="px-3 py-1.5 whitespace-nowrap">{{ $b->booking_no }}</td>
                            <td class="px-3 py-1.5 whitespace-nowrap">{{ $b->event_date?->format('d M Y') }}</td>
                            <td class="px-3 py-1.5">{{ $b->customer?->name }}</td>
                            <td class="px-3 py-1.5">{{ $b->hall?->name }}</td>
                            <td class="px-3 py-1.5">{{ $b->event_type ?: '—' }}</td>
                            <td class="px-3 py-1.5 text-right tabular-nums">{{ $b->guests }}</td>
                            <td class="px-3 py-1.5 text-right tabular-nums">{{ $money($row['total']) }}</td>
                            <td class="px-3 py-1.5 text-right tabular-nums">{{ $money($row['payments']) }}</td>
                            <td class="px-3 py-1.5 text-right tabular-nums">{{ $money($row['balance']) }}</td>
                            <td class="px-3 py-1.5 text-right tabular-nums">{{ $money($row['expense']) }}</td>
                            <td class="px-3 py-1.5 text-right tabular-nums font-medium">{{ $money($row['profit']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="px-3 py-4 text-center text-gray-400">No events in this period.</td></tr>
                    @endforelse
                    <tr class="bg-primary-600 text-white font-semibold">
                        <td class="px-3 py-2" colspan="6">{{ $report['count'] }} event(s)</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ $money($report['totals']['total']) }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ $money($report['totals']['payments']) }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ $money($report['totals']['balance']) }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ $money($report['totals']['expense']) }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ $money($report['totals']['profit']) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
