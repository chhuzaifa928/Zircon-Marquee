<?php

namespace App\Filament\Support;

use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Shared base for date-ranged report pages (SRS §15.1): a From–To range with
 * month/year presets, accounts-scoped access, and a "Print PDF" header action.
 * Not under the discovered Pages path, so it is never registered on its own.
 */
abstract class ReportPage extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    public ?string $from = null;

    public ?string $to = null;

    public function mount(): void
    {
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->endOfMonth()->toDateString();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'accounts']) ?? false;
    }

    public function thisMonth(): void
    {
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->endOfMonth()->toDateString();
    }

    public function thisYear(): void
    {
        $this->from = now()->startOfYear()->toDateString();
        $this->to = now()->endOfYear()->toDateString();
    }

    /** Route name of the PDF export for this report. */
    abstract protected function pdfRouteName(): string;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pdf')
                ->label('Print PDF')
                ->icon(Heroicon::OutlinedPrinter)
                ->color('gray')
                ->url(
                    fn (): string => route($this->pdfRouteName(), ['from' => $this->from, 'to' => $this->to]),
                    shouldOpenInNewTab: true,
                ),
        ];
    }
}
