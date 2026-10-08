<x-filament-panels::page>
    <div class="space-y-5">
        @include('filament.pages.reports._toolbar')

        @php
            $report = $this->report;
            $money = fn ($n) => number_format($n, 2);
        @endphp

        <div class="overflow-x-auto rounded-xl ring-1 ring-gray-200 dark:ring-white/10">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-white/5 text-left text-gray-500">
                    <tr>
                        <th class="px-3 py-2">Supplier</th>
                        <th class="px-3 py-2 text-right">Opening</th>
                        <th class="px-3 py-2 text-right">Receivings</th>
                        <th class="px-3 py-2 text-right">Payments</th>
                        <th class="px-3 py-2 text-right">Balance owed</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @forelse ($report['rows'] as $row)
                        <tr>
                            <td class="px-3 py-1.5">{{ $row['supplier']->code }} — {{ $row['supplier']->name }}</td>
                            <td class="px-3 py-1.5 text-right tabular-nums">{{ $money($row['opening']) }}</td>
                            <td class="px-3 py-1.5 text-right tabular-nums">{{ $money($row['receivings']) }}</td>
                            <td class="px-3 py-1.5 text-right tabular-nums">{{ $money($row['payments']) }}</td>
                            <td class="px-3 py-1.5 text-right tabular-nums font-medium">{{ $money($row['closing']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-3 py-4 text-center text-gray-400">No supplier accounts.</td></tr>
                    @endforelse
                    <tr class="bg-primary-600 text-white font-semibold">
                        <td class="px-3 py-2">Grand totals</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ $money($report['totals']['opening']) }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ $money($report['totals']['receivings']) }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ $money($report['totals']['payments']) }}</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ $money($report['totals']['closing']) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
