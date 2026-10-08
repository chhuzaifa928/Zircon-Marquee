<?php

namespace App\Filament\Pages;

use App\Models\Booking;
use App\Models\Hall;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use UnitEnum;

/**
 * Colour-coded monthly booking calendar (SRS §6.5). Shows each date's bookings
 * across all halls with their status, so staff can see availability at a glance.
 */
class BookingCalendar extends Page
{
    protected string $view = 'filament.pages.booking-calendar';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Bookings';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Booking calendar';

    /** Current month anchor (Y-m). */
    public int $year;

    public int $month;

    /** Hall filter — null = all halls. */
    public ?int $hallId = null;

    public function mount(): void
    {
        $this->year = (int) now()->year;
        $this->month = (int) now()->month;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->inAnyRole(\App\Models\User::BOOKING_VIEW) ?? false;
    }

    public function previousMonth(): void
    {
        $anchor = Carbon::create($this->year, $this->month, 1)->subMonth();
        $this->year = $anchor->year;
        $this->month = $anchor->month;
    }

    public function nextMonth(): void
    {
        $anchor = Carbon::create($this->year, $this->month, 1)->addMonth();
        $this->year = $anchor->year;
        $this->month = $anchor->month;
    }

    public function today(): void
    {
        $this->year = (int) now()->year;
        $this->month = (int) now()->month;
    }

    public function getMonthLabelProperty(): string
    {
        return Carbon::create($this->year, $this->month, 1)->format('F Y');
    }

    /** @return Collection<int,Hall> */
    public function getHallsProperty(): Collection
    {
        return Hall::query()->where('is_active', true)->orderBy('id')->get();
    }

    /**
     * Build the calendar grid: a list of weeks, each a list of 7 day cells. Days
     * outside the current month are included (greyed) to fill the grid.
     *
     * @return array<int,array<int,array{date:Carbon,inMonth:bool,bookings:Collection}>>
     */
    public function getWeeksProperty(): array
    {
        $first = Carbon::create($this->year, $this->month, 1)->startOfDay();
        $last = $first->copy()->endOfMonth();

        // Grid starts on Monday of the first week and ends on Sunday of the last.
        $gridStart = $first->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $last->copy()->endOfWeek(Carbon::SUNDAY);

        $bookings = Booking::query()
            ->with(['customer', 'hall'])
            ->when($this->hallId, fn ($q) => $q->where('hall_id', $this->hallId))
            ->whereBetween('event_date', [$gridStart->toDateString(), $gridEnd->toDateString()])
            ->orderBy('slot')
            ->get()
            ->groupBy(fn (Booking $b): string => $b->event_date->toDateString());

        $weeks = [];
        $cursor = $gridStart->copy();

        while ($cursor <= $gridEnd) {
            $week = [];

            for ($i = 0; $i < 7; $i++) {
                $key = $cursor->toDateString();
                $week[] = [
                    'date' => $cursor->copy(),
                    'inMonth' => $cursor->month === $this->month,
                    'bookings' => $bookings->get($key, collect()),
                ];
                $cursor->addDay();
            }

            $weeks[] = $week;
        }

        return $weeks;
    }

    public static function statusColor(string $status): string
    {
        return match ($status) {
            'tentative' => '#f59e0b',
            'booked' => '#3b82f6',
            'paid' => '#22c55e',
            'cancelled' => '#ef4444',
            default => '#9ca3af',
        };
    }
}
