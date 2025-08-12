<?php

namespace App\Filament\Resources\TableResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BookingsRelationManager extends RelationManager
{
    protected static string $relationship = 'bookings';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date')
                    ->label('Data')
                    ->required(),
                Forms\Components\Select::make('time_slot_id')
                    ->label('Orario')
                    ->relationship('timeSlot', 'time')
                    ->required(),
                Forms\Components\TextInput::make('guests_count')
                    ->label('Persone')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('customer_name')
                    ->label('Nome Cliente')
                    ->required(),
                Forms\Components\TextInput::make('customer_email')
                    ->label('Email')
                    ->email()
                    ->required(),
                Forms\Components\TextInput::make('customer_phone')
                    ->label('Telefono')
                    ->required(),
                Forms\Components\Textarea::make('special_requests')
                    ->label('Richieste Speciali')
                    ->placeholder('Seggiolone, decorazioni, tavolo specifico...')
                    ->maxLength(1000)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('customer_name')
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Data')
                    ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->locale('it')->isoFormat('D MMMM YYYY'))
                    ->sortable()
                    ->weight('medium'),
                    
                Tables\Columns\TextColumn::make('timeSlot.time')
                    ->label('Orario')
                    ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->format('H:i'))
                    ->badge()
                    ->color('success'),
                    
                Tables\Columns\TextColumn::make('guests_count')
                    ->label('Persone')
                    ->badge()
                    ->color('gray'),
                    
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Cliente')
                    ->weight('medium'),
                    
                Tables\Columns\TextColumn::make('customer_phone')
                    ->label('Telefono')
                    ->copyable()
                    ->copyMessage('Numero copiato!'),
                    
                Tables\Columns\TextColumn::make('special_requests')
                    ->label('Richieste Speciali')
                    ->limit(30)
                    ->toggleable()
                    ->placeholder('Nessuna richiesta')
                    ->tooltip(fn ($record) => $record->special_requests),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Stato')
                    ->colors([
                        'success' => 'confirmed',
                        'danger' => 'cancelled_by_restaurant',
                        'warning' => 'cancelled_by_customer',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'confirmed' => 'Confermata',
                        'cancelled_by_restaurant' => 'Cancellata',
                        'cancelled_by_customer' => 'Cancellata Cliente',
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('date', 'desc')
            ->striped();
    }
}