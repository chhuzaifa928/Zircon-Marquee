<x-filament-panels::page>
    <div class="space-y-5">
        @include('filament.pages.reports._toolbar')

        @php $report = $this->report; @endphp

        @forelse ($report['accounts'] as $section)
            <div class="overflow-hidden rounded-xl ring-1 ring-gray-200 dark:ring-white/10">
                <div class="flex items-center justify-between bg-gray-50 dark:bg-white/5 px-3 py-2">
                    <span class="font-semibold">{{ $section['account']->code }} — {{ $section['account']->name }}</span>
                    <span class="text-sm text-gray-500">Opening: PKR {{ number_format($section['opening'], 2) }}</span>
                </div>
                <table class="w-full text-sm">
                    <thead class="text-left text-gray-500 border-b border-gray-100 dark:border-white/5">
                        <tr>
                            <th class="px-3 py-2">Date</th>
                            <th class="px-3 py-2">Voucher</th>
                            <th class="px-3 py-2">Remark</th>
                            <th class="px-3 py-2">Contra</th>
                            <th class="px-3 py-2 text-right">In (Dr)</th>
                            <th class="px-3 py-2 text-right">Out (Cr)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($section['rows'] as $row)
                            <tr>
                                <td class="px-3 py-1.5 whitespace-nowrap">{{ $row['date']?->format('d M Y') }}</td>
                                <td class="px-3 py-1.5">{{ $row['voucher_no'] }}</td>
                                <td class="px-3 py-1.5">{{ $row['remark'] ?: '—' }}</td>
                                <td class="px-3 py-1.5">{{ $row['contra'] ?: '—' }}</td>
                                <td class="px-3 py-1.5 text-right tabular-nums">{{ $row['in'] ? number_format($row['in'], 2) : '' }}</td>
                                <td class="px-3 py-1.5 text-right tabular-nums">{{ $row['out'] ? number_format($row['out'], 2) : '' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-3 py-3 text-center text-gray-400">No movement in this period.</td></tr>
                        @endforelse
                        <tr class="bg-gray-50 dark:bg-white/5 font-medium">
                            <td class="px-3 py-2" colspan="4">Collections / Payments</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ number_format($section['total_in'], 2) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ number_format($section['total_out'], 2) }}</td>
                        </tr>
                        <tr class="bg-amber-50/60 dark:bg-amber-500/5 font-semibold">
                            <td class="px-3 py-2" colspan="5">Closing balance</td>
                            <td class="px-3 py-2 text-right tabular-nums">PKR {{ number_format($section['closing'], 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @empty
            <div class="rounded-xl ring-1 ring-gray-200 dark:ring-white/10 p-6 text-center text-gray-500">No cash or bank accounts.</div>
        @endforelse

        <div class="rounded-xl bg-primary-600 text-white px-4 py-3 flex flex-wrap gap-x-8 gap-y-1 justify-end text-sm font-semibold">
            <span>Opening: PKR {{ number_format($report['totals']['opening'], 2) }}</span>
            <span>In: PKR {{ number_format($report['totals']['in'], 2) }}</span>
            <span>Out: PKR {{ number_format($report['totals']['out'], 2) }}</span>
            <span>Closing: PKR {{ number_format($report['totals']['closing'], 2) }}</span>
        </div>
    </div>
</x-filament-panels::page>
