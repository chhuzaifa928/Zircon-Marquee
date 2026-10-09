<?php

namespace App\Filament\Resources\Bookings\RelationManagers;

use App\Models\Account;
use App\Models\Booking;
use App\Models\PaymentSlip;
use App\Services\PaymentPosting;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Payment slips recorded against a booking (SRS §8). Recording a slip here runs
 * the same posting as the Payments module: advance/balance recompute and status
 * transitions. Slips are immutable once posted (Super-Admin edits in the
 * Payments module), so this manager only adds and views.
 */
class PaymentSlipsRelationManager extends RelationManager
{
    protected static string $relationship = 'paymentSlips';

    protected static ?string $title = 'Payments';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
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
                    ->maxLength(255),
                Textarea::make('remark')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('slip_no')
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('slip_no')->label('Slip #')->searchable(),
                TextColumn::make('date')->date('d M Y')->sortable(),
                TextColumn::make('account.name')->label('Received into'),
                TextColumn::make('method')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? ucwords(str_replace('_', ' ', $state)) : '—'),
                TextColumn::make('amount')
                    ->money('PKR')
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->money('PKR')),
                TextColumn::make('createdBy.name')->label('Created by')->toggleable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Record payment')
                    ->visible(fn (): bool => (auth()->user()?->hasAnyRole(['super_admin', 'admin', 'accounts']) ?? false)
                        && ! $this->getOwnerRecord()->isLocked())
                    ->mutateDataUsing(function (array $data): array {
                        $data['created_by'] = auth()->id();

                        return $data;
                    })
                    ->after(function (): void {
                        /** @var Booking $booking */
                        $booking = $this->getOwnerRecord();
                        app(PaymentPosting::class)->apply($booking);
                    }),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
