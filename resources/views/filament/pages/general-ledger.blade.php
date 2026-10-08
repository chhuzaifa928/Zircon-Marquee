<x-filament-panels::page>
    <div class="space-y-4">
        {{-- Controls --}}
        <div class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-sm text-gray-500 mb-1">Account head</label>
                <select wire:model.live="accountId"
                        class="fi-select-input block w-72 rounded-lg border-gray-300 text-sm dark:bg-gray-800 dark:border-gray-600">
                    @foreach ($this->accountOptions as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">From</label>
                <input type="date" wire:model.live="from"
                       class="fi-input block rounded-lg border-gray-300 text-sm dark:bg-gray-800 dark:border-gray-600">
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">To</label>
                <input type="date" wire:model.live="to"
                       class="fi-input block rounded-lg border-gray-300 text-sm dark:bg-gray-800 dark:border-gray-600">
            </div>
        </div>

        @php $ledger = $this->ledger; @endphp

        @if ($ledger === null)
            <div class="rounded-xl ring-1 ring-gray-200 dark:ring-white/10 p-6 text-center text-gray-500">
                Select an account head to view its ledger.
            </div>
        @else
            @php
                $account = $this->account;
                $sideLabel = $ledger['debitNormal'] ? 'Dr' : 'Cr';
                $fmt = fn ($n) => number_format(abs($n), 2) . ' ' . ($n < 0 ? ($ledger['debitNormal'] ? 'Cr' : 'Dr') : $sideLabel);
            @endphp

            <div class="flex items-center justify-between">
                <div>
                    <div class="text-lg font-semibold">{{ $account->code }} — {{ $account->name }}</div>
                    <div class="text-sm text-gray-500">{{ ucfirst($account->type) }} account · {{ $ledger['debitNormal'] ? 'Debit' : 'Credit' }} normal</div>
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl ring-1 ring-gray-200 dark:ring-white/10">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-white/5 text-left text-gray-500">
                        <tr>
                            <th class="px-3 py-2">Date</th>
                            <th class="px-3 py-2">Voucher</th>
                            <th class="px-3 py-2">Description</th>
                            <th class="px-3 py-2">Contra account</th>
                            <th class="px-3 py-2 text-right">Debit</th>
                            <th class="px-3 py-2 text-right">Credit</th>
                            <th class="px-3 py-2 text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        <tr class="bg-amber-50/50 dark:bg-amber-500/5 font-medium">
                            <td class="px-3 py-2" colspan="6">Opening balance (carried forward)</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ $fmt($ledger['opening']) }}</td>
                        </tr>
                        @forelse ($ledger['rows'] as $row)
                            <tr>
                                <td class="px-3 py-2 whitespace-nowrap">{{ $row['date']?->format('d M Y') }}</td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <span class="font-medium">{{ $row['voucher_no'] }}</span>
                                </td>
                                <td class="px-3 py-2">{{ $row['description'] ?: '—' }}</td>
                                <td class="px-3 py-2">{{ $row['contra']?->code }} — {{ $row['contra']?->name }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $row['debit'] ? number_format($row['debit'], 2) : '' }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $row['credit'] ? number_format($row['credit'], 2) : '' }}</td>
                                <td class="px-3 py-2 text-right tabular-nums">{{ $fmt($row['balance']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-3 py-6 text-center text-gray-400">No postings in this period.</td></tr>
                        @endforelse
                        <tr class="bg-gray-50 dark:bg-white/5 font-semibold border-t-2 border-gray-200 dark:border-white/10">
                            <td class="px-3 py-2" colspan="4">Period totals</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ number_format($ledger['totalDebit'], 2) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ number_format($ledger['totalCredit'], 2) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ $fmt($ledger['closing']) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-gray-400">Closing balance {{ $fmt($ledger['closing']) }} — positive is a normal {{ $ledger['debitNormal'] ? 'debit' : 'credit' }} balance.</p>
        @endif
    </div>
</x-filament-panels::page>
