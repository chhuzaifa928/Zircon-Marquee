<?php

namespace App\Filament\Resources\PaymentSlips\Schemas;

use App\Models\Account;
use App\Models\Booking;
use App\Models\PaymentSlip;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PaymentSlipForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Payment slip')
                    ->description('Slip number is assigned automatically (SL-0001).')
                    ->columns(2)
                    ->components([
                        TextInput::make('slip_no')
                            ->label('Slip #')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated')
                            ->visibleOn('edit'),

                        Select::make('booking_id')
                            ->label('Booking')
                            ->relationship(
                                'booking',
                                'booking_no',
                                // Payments may only be taken against live bookings.
                                fn ($query) => $query->whereIn('status', ['tentative', 'booked'])
                            )
                            ->getOptionLabelFromRecordUsing(fn (Booking $r): string => "{$r->booking_no} — {$r->customer?->name}")
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->columnSpanFull(),

                        Placeholder::make('booking_balance')
                            ->label('Current balance')
                            ->content(function (Get $get): string {
                                $booking = Booking::find($get('booking_id'));

                                if ($booking === null) {
                                    return '—';
                                }

                                return 'Grand total PKR '.number_format((float) $booking->grand_total, 2)
                                    .'  ·  Advance PKR '.number_format((float) $booking->advance_total, 2)
                                    .'  ·  Due PKR '.number_format((float) $booking->due, 2);
                            })
                            ->columnSpanFull(),

                        Select::make('account_id')
                            ->label('Received into (cash/bank)')
                            ->relationship(
                                'account',
                                'name',
                                fn ($query) => $query->whereIn('type', ['cash', 'bank'])->where('is_active', true)
                            )
                            ->getOptionLabelFromRecordUsing(fn (Account $r): string => "{$r->code} — {$r->name}")
                            ->required()
                            ->native(false),

                        TextInput::make('amount')
                            ->numeric()
                            ->prefix('PKR')
                            ->minValue(0.01)
                            ->required(),

                        DatePicker::make('date')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d M Y'),

                        Select::make('method')
                            ->options(collect(PaymentSlip::METHODS)
                                ->mapWithKeys(fn (string $m): array => [$m => ucwords(str_replace('_', ' ', $m))])
                                ->all())
                            ->default('cash')
                            ->native(false),

                        TextInput::make('reference')
                            ->maxLength(255)
                            ->helperText('Cheque no, transaction id, etc.'),

                        Textarea::make('remark')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
