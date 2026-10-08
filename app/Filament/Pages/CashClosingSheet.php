<?php

namespace App\Filament\Pages;

use App\Filament\Support\ReportPage;
use App\Services\Reports\CashClosingReport;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class CashClosingSheet extends ReportPage
{
    protected string $view = 'filament.pages.reports.cash-closing-sheet';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Cash Closing Sheet';

    protected function pdfRouteName(): string
    {
        return 'reports.cash-closing';
    }

    public function getReportProperty(): array
    {
        return app(CashClosingReport::class)->build($this->from, $this->to);
    }
}
