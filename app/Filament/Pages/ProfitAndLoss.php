<?php

namespace App\Filament\Pages;

use App\Filament\Support\ReportPage;
use App\Services\Reports\ProfitAndLossReport;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class ProfitAndLoss extends ReportPage
{
    protected string $view = 'filament.pages.reports.profit-and-loss';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Profit & Loss';

    protected function pdfRouteName(): string
    {
        return 'reports.profit-loss';
    }

    public function getReportProperty(): array
    {
        return app(ProfitAndLossReport::class)->build($this->from, $this->to);
    }
}
