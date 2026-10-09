<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Models\Booking;
use App\Models\BookingCharge;
use App\Models\Customer;
use App\Models\Setting;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Booking details')
                    ->columnSpan(2)
                    ->columns(2)
                    ->components([
                        TextInput::make('booking_no')
                            ->label('Booking #')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated')
                            ->visibleOn('edit'),

                        Select::make('status')
                            ->options(fn (): array => collect(Booking::STATUSES)
                                ->mapWithKeys(fn (string $s): array => [$s => ucfirst($s)])
                                ->all())
                            ->default('tentative')
                            ->disabled()
                            ->dehydrated(false)
                            ->visibleOn('edit'),

                        Select::make('customer_id')
                            ->label('Host')
                            ->relationship('customer', 'name')
                            // CNIC is encrypted (exact-match only, via the
                            // Customers list); search hosts here by name/phone/code.
                            ->searchable(['name', 'phone', 'code'])
                            ->preload()
                            ->required()
                            ->columnSpanFull()
                            ->getOptionLabelFromRecordUsing(fn (Customer $record): string => "{$record->code} — {$record->name}")
                            ->createOptionForm([
                                TextInput::make('name')->required()->maxLength(255),
                                TextInput::make('care_of')->label('Care of')->maxLength(255),
                                TextInput::make('phone')->tel()->maxLength(50),
                                TextInput::make('cnic')->label('CNIC')->maxLength(20),
                                TextInput::make('email')->email()->maxLength(255),
                                Textarea::make('address')->rows(2),
                            ]),

                        Select::make('hall_id')
                            ->label('Hall / venue')
                            ->relationship('hall', 'name', fn ($query) => $query->where('is_active', true))
                            ->required()
                            ->native(false),

                        Select::make('slot')
                            ->options(['lunch' => 'Lunch', 'dinner' => 'Dinner'])
                            ->required()
                            ->native(false),

                        DatePicker::make('event_date')
                            ->required()
                            ->native(false)
                            ->displayFormat('d M Y'),

                        DatePicker::make('booking_date')
                            ->label('Booking taken on')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d M Y'),

                        TimePicker::make('start_time')
                            ->seconds(false),

                        TimePicker::make('end_time')
                            ->seconds(false),

                        Select::make('event_type')
                            ->options(collect(Booking::EVENT_TYPES)
                                ->mapWithKeys(fn (string $t): array => [$t => $t])
                                ->all())
                            ->native(false),

                        TextInput::make('guests')
                            ->label('Guaranteed guests')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->default(0)
                            ->live(onBlur: true),
                    ]),

                Section::make('Live totals')
                    ->columnSpan(1)
                    ->components([
                        Placeholder::make('summary')
                            ->hiddenLabel()
                            ->content(fn (Get $get) => static::summaryView($get)),
                    ]),

                Section::make('Pricing (per head)')
                    ->columnSpanFull()
                    ->columns(3)
                    ->components([
                        TextInput::make('rack_rate')
                            ->label('Rack rate (list)')
                            ->numeric()
                            ->prefix('PKR')
                            ->default(0),
                        TextInput::make('discounted_rate')
                            ->label('Discounted rate')
                            ->numeric()
                            ->prefix('PKR')
                            ->default(0)
                            ->required()
                            ->live(onBlur: true),
                        TextInput::make('gst_rate')
                            ->label('GST rate (snapshot)')
                            ->numeric()
                            ->suffix('%')
                            ->default(fn (): string => (string) Setting::current()->gst_rate)
                            ->live(onBlur: true)
                            ->helperText('Defaults to the current system GST rate; snapshotted onto the booking.'),
                    ]),

                Section::make('Menu (dishes)')
                    ->description('Food items for the booking and the Kitchen Voucher (SRS §7.8).')
                    ->columnSpanFull()
                    ->collapsed()
                    ->components([
                        Repeater::make('foods')
                            ->relationship()
                            ->hiddenLabel()
                            ->orderColumn('sort')
                            ->reorderable()
                            ->columns(2)
                            ->defaultItems(0)
                            ->addActionLabel('Add dish')
                            ->components([
                                TextInput::make('name')
                                    ->label('Dish')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('urdu_name')
                                    ->label('Urdu name')
                                    ->maxLength(255),
                            ]),
                    ]),

                Section::make('Additional charges')
                    ->description('Itemised extras beyond the per-head charge (SRS §7.3).')
                    ->columnSpanFull()
                    ->collapsed()
                    ->components([
                        Repeater::make('charges')
                            ->relationship()
                            ->hiddenLabel()
                            ->columns(5)
                            ->defaultItems(0)
                            ->addActionLabel('Add charge')
                            ->live()
                            ->components([
                                Select::make('charge_type')
                                    ->options(collect(BookingCharge::TYPES)
                                        ->mapWithKeys(fn (string $t): array => [$t => static::chargeLabel($t)])
                                        ->all())
                                    ->required()
                                    ->native(false),
                                TextInput::make('description')
                                    ->maxLength(255),
                                TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => static::recomputeChargeAmount($get, $set)),
                                TextInput::make('unit_price')
                                    ->numeric()
                                    ->prefix('PKR')
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => static::recomputeChargeAmount($get, $set)),
                                TextInput::make('amount')
                                    ->numeric()
                                    ->prefix('PKR')
                                    ->default(0),
                            ]),
                    ]),

                Section::make('Arrangements & notes')
                    ->columnSpanFull()
                    ->columns(2)
                    ->collapsed()
                    ->components([
                        Textarea::make('arrangements')
                            ->rows(3)
                            ->helperText('Prints on the Event Booking Sheet.'),
                        Textarea::make('bulletin_board')
                            ->label('Bulletin board notes')
                            ->rows(3),
                        Textarea::make('remarks')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Keep a charge line's amount = quantity × unit price.
     */
    protected static function recomputeChargeAmount(Get $get, Set $set): void
    {
        $amount = (float) $get('quantity') * (float) $get('unit_price');
        $set('amount', round($amount, 2));
    }

    /**
     * Live, non-authoritative totals preview. The authoritative snapshot is
     * recomputed server-side by the BookingTotals service on save.
     */
    protected static function summaryView(Get $get): \Illuminate\Contracts\Support\Htmlable
    {
        $guests = (int) $get('guests');
        $rate = (float) $get('discounted_rate');
        $gstRate = (float) $get('gst_rate');

        $headcharge = round($rate * $guests, 2);
        $chargesTotal = collect($get('charges') ?? [])
            ->sum(fn ($c): float => (float) ($c['amount'] ?? 0));
        $subtotal = round($headcharge + $chargesTotal, 2);
        $gstAmount = round($subtotal * ($gstRate / 100), 2);
        $grand = round($subtotal + $gstAmount, 2);

        $fmt = fn (float $n): string => 'PKR '.number_format($n, 2);

        $rows = [
            ['Head charge', "{$guests} × {$fmt($rate)}", $fmt($headcharge)],
            ['Additional charges', '', $fmt($chargesTotal)],
            ['Subtotal', '', $fmt($subtotal)],
            ["GST ({$gstRate}%)", '', $fmt($gstAmount)],
        ];

        $html = '<div class="space-y-1 text-sm">';
        foreach ($rows as [$label, $sub, $value]) {
            $subHtml = $sub ? "<span class='text-gray-400'> {$sub}</span>" : '';
            $html .= "<div class='flex justify-between gap-2'><span>{$label}{$subHtml}</span><span class='font-medium tabular-nums'>{$value}</span></div>";
        }
        $html .= "<div class='flex justify-between gap-2 border-t pt-1 mt-1 font-semibold'><span>Grand total</span><span class='tabular-nums'>{$fmt($grand)}</span></div>";
        $html .= '</div>';

        return new \Illuminate\Support\HtmlString($html);
    }

    protected static function chargeLabel(string $type): string
    {
        return ucwords(str_replace('_', ' ', $type));
    }
}
