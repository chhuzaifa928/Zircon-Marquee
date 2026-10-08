<?php

namespace App\Filament\Pages;

use App\Filament\Support\ReportPage;
use App\Services\Reports\SupplierSummaryReport;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class SupplierSummary extends ReportPage
{
    protected string $view = 'filament.pages.reports.supplier-summary';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Supplier Summary';

    protected function pdfRouteName(): string
    {
        return 'reports.supplier-summary';
    }

    public function getReportProperty(): array
    {
        return app(SupplierSummaryReport::class)->build($this->from, $this->to);
    }
}
