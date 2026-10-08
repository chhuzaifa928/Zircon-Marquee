<?php

namespace App\Filament\Pages;

use App\Filament\Support\ReportPage;
use App\Services\Reports\BookingsMasterReport;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class BookingsMaster extends ReportPage
{
    protected string $view = 'filament.pages.reports.bookings-master';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Bookings Master Report';

    protected function pdfRouteName(): string
    {
        return 'reports.bookings-master';
    }

    public function getReportProperty(): array
    {
        return app(BookingsMasterReport::class)->build($this->from, $this->to);
    }
}
