<x-filament-panels::page>
    <div class="space-y-4">
        {{-- Toolbar --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <x-filament::button wire:click="previousMonth" color="gray" icon="heroicon-m-chevron-left" />
                <span class="text-lg font-semibold min-w-44 text-center">{{ $this->monthLabel }}</span>
                <x-filament::button wire:click="nextMonth" color="gray" icon="heroicon-m-chevron-right" />
                <x-filament::button wire:click="today" color="gray">Today</x-filament::button>
            </div>

            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-500">Hall</label>
                <select wire:model.live="hallId"
                        class="fi-select-input block rounded-lg border-gray-300 text-sm dark:bg-gray-800 dark:border-gray-600">
                    <option value="">All halls</option>
                    @foreach ($this->halls as $hall)
                        <option value="{{ $hall->id }}">{{ $hall->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Legend --}}
        <div class="flex flex-wrap items-center gap-4 text-xs">
            @foreach (['tentative' => 'Tentative', 'booked' => 'Booked', 'paid' => 'Fully paid', 'cancelled' => 'Cancelled'] as $status => $label)
                <span class="inline-flex items-center gap-1.5">
                    <span class="inline-block h-3 w-3 rounded-full" style="background: {{ \App\Filament\Pages\BookingCalendar::statusColor($status) }}"></span>
                    {{ $label }}
                </span>
            @endforeach
        </div>

        {{-- Calendar grid --}}
        <div class="overflow-hidden rounded-xl ring-1 ring-gray-200 dark:ring-white/10">
            <div class="grid grid-cols-7 bg-gray-50 dark:bg-white/5 text-center text-xs font-medium text-gray-500">
                @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dow)
                    <div class="py-2">{{ $dow }}</div>
                @endforeach
            </div>

            <div class="grid grid-cols-7">
                @foreach ($this->weeks as $week)
                    @foreach ($week as $cell)
                        @php $isToday = $cell['date']->isToday(); @endphp
                        <div @class([
                            'min-h-28 border-b border-r border-gray-100 dark:border-white/5 p-1.5 align-top',
                            'bg-gray-50/70 dark:bg-white/[0.02] text-gray-400' => ! $cell['inMonth'],
                            'bg-white dark:bg-transparent' => $cell['inMonth'],
                        ])>
                            <div class="flex items-center justify-between">
                                <span @class([
                                    'text-xs font-medium',
                                    'inline-flex h-5 w-5 items-center justify-center rounded-full bg-primary-600 text-white' => $isToday,
                                ])>{{ $cell['date']->day }}</span>
                                @if ($cell['bookings']->isNotEmpty())
                                    <span class="text-[10px] text-gray-400">{{ $cell['bookings']->count() }}</span>
                                @endif
                            </div>

                            <div class="mt-1 space-y-1">
                                @foreach ($cell['bookings']->take(4) as $booking)
                                    <a href="{{ \App\Filament\Resources\Bookings\BookingResource::getUrl('edit', ['record' => $booking]) }}"
                                       class="block truncate rounded px-1 py-0.5 text-[10px] leading-tight text-white"
                                       style="background: {{ \App\Filament\Pages\BookingCalendar::statusColor($booking->status) }}"
                                       title="{{ $booking->booking_no }} · {{ $booking->customer?->name }} · {{ $booking->hall?->name }} · {{ ucfirst($booking->slot) }} · {{ ucfirst($booking->status) }}">
                                        {{ ucfirst(substr($booking->slot, 0, 1)) }} · {{ $booking->customer?->name ?? $booking->booking_no }}
                                    </a>
                                @endforeach
                                @if ($cell['bookings']->count() > 4)
                                    <div class="px-1 text-[10px] text-gray-400">+{{ $cell['bookings']->count() - 4 }} more</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>
    </div>
</x-filament-panels::page>
