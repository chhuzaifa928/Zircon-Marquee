<x-filament-panels::page>
    <div class="space-y-5">
        @include('filament.pages.reports._toolbar')

        @php
            $report = $this->report;
            $money = fn ($n) => number_format($n, 2);
        @endphp

        <div class="grid gap-5 md:grid-cols-2">
            {{-- Income --}}
            <div class="overflow-hidden rounded-xl ring-1 ring-gray-200 dark:ring-white/10">
                <div class="bg-emerald-50 dark:bg-emerald-500/10 px-3 py-2 font-semibold">Income</div>
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach ($report['income'] as $line)
                            <tr>
                                <td class="px-3 py-1.5">{{ $line['account']->code }} — {{ $line['account']->name }}</td>
                                <td class="px-3 py-1.5 text-right tabular-nums">{{ $money($line['amount']) }}</td>
                            </tr>
                        @endforeach
                        <tr class="bg-gray-50 dark:bg-white/5 font-semibold">
                            <td class="px-3 py-2">Total income</td>
                            <td class="px-3 py-2 text-right tabular-nums">PKR {{ $money($report['totalIncome']) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Expense --}}
            <div class="overflow-hidden rounded-xl ring-1 ring-gray-200 dark:ring-white/10">
                <div class="bg-rose-50 dark:bg-rose-500/10 px-3 py-2 font-semibold">Expenses</div>
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach ($report['expense'] as $line)
                            <tr>
                                <td class="px-3 py-1.5">{{ $line['account']->code }} — {{ $line['account']->name }}</td>
                                <td class="px-3 py-1.5 text-right tabular-nums">{{ $money($line['amount']) }}</td>
                            </tr>
                        @endforeach
                        <tr class="bg-gray-50 dark:bg-white/5 font-semibold">
                            <td class="px-3 py-2">Total expenses</td>
                            <td class="px-3 py-2 text-right tabular-nums">PKR {{ $money($report['totalExpense']) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        @php $net = $report['netProfit']; @endphp
        <div class="rounded-xl px-4 py-3 text-white text-right text-lg font-bold {{ $net < 0 ? 'bg-rose-600' : 'bg-primary-600' }}">
            Net {{ $net < 0 ? 'Loss' : 'Profit' }}:
            PKR {{ $net < 0 ? '('.$money(abs($net)).')' : $money($net) }}
        </div>
    </div>
</x-filament-panels::page>
