<?php

namespace App\Filament\Resources\PaymentSlips\Pages;

use App\Filament\Resources\PaymentSlips\PaymentSlipResource;
use App\Models\PaymentSlip;
use App\Services\PaymentPosting;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPaymentSlip extends EditRecord
{
    protected static string $resource = PaymentSlipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->after(fn (PaymentSlip $record) => $this->repost($record->booking)),
        ];
    }

    protected function afterSave(): void
    {
        /** @var PaymentSlip $slip */
        $slip = $this->record;
        $this->repost($slip->booking);
    }

    private function repost(?\App\Models\Booking $booking): void
    {
        if ($booking !== null) {
            app(PaymentPosting::class)->apply($booking);
        }
    }
}
