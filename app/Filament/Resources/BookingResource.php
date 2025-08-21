<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource\Pages;
use App\Filament\Resources\BookingResource\RelationManagers;
use App\Models\Booking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Prenotazioni';

    protected static ?string $modelLabel = 'Prenotazione';

    protected static ?string $pluralModelLabel = 'Prenotazioni';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('table_id')
                    ->label('Tavolo')
                    ->relationship('table', 'name')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if ($state) {
                            $table = \App\Models\Table::find($state);
                            if ($table) {
                                $set('guests_count', $table->capacity);
                            }
                        }
                    }),
                    
                Forms\Components\DatePicker::make('date')
                    ->label('Data')
                    ->required()
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        // Reset dell'orario quando cambia la data
                        $set('time_slot_id', null);
                    }),
                    
                Forms\Components\Select::make('time_slot_id')
                    ->label('Orario')
                    ->required()
                    ->live()
                    ->options(function (Forms\Get $get) {
                        $tableId = $get('table_id');
                        $date = $get('date');
                        
                        if (!$tableId || !$date) {
                            return [];
                        }
                        
                        // Ottieni tutti gli slot
                        $allSlots = \App\Models\TimeSlot::where('is_active', true)
                            ->orderBy('time')
                            ->get();
                        
                        // Ottieni gli slot già occupati per questo tavolo in questa data
                        $occupiedSlots = \App\Models\Booking::where('table_id', $tableId)
                            ->where('date', $date)
                            ->where('status', 'confirmed')
                            ->pluck('time_slot_id')
                            ->toArray();
                        
                        // Filtra solo gli slot liberi
                        $availableSlots = $allSlots->whereNotIn('id', $occupiedSlots);
                        
                        return $availableSlots->pluck('time', 'id')->toArray();
                    })
                    ->placeholder('Seleziona tavolo e data prima')
                    ->hint(function (Forms\Get $get) {
                        $tableId = $get('table_id');
                        $date = $get('date');
                        
                        if (!$tableId || !$date) {
                            return 'Seleziona tavolo e data per vedere gli orari disponibili';
                        }
                        
                        return 'Solo orari liberi per questo tavolo';
                    }),
                    
                Forms\Components\TextInput::make('guests_count')
                    ->label('Numero Persone')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        $tableId = $get('table_id');
                        if ($tableId) {
                            $table = \App\Models\Table::find($tableId);
                            if ($table && $state > $table->capacity) {
                                $set('guests_count', $table->capacity);
                            }
                        }
                    })
                    ->hint(function (Forms\Get $get) {
                        $tableId = $get('table_id');
                        if ($tableId) {
                            $table = \App\Models\Table::find($tableId);
                            return $table ? "Massimo {$table->capacity} posti" : '';
                        }
                        return 'Seleziona prima un tavolo';
                    }),
                    
                Forms\Components\TextInput::make('customer_name')
                    ->label('Nome Cliente')
                    ->required()
                    ->maxLength(255),
                    
                Forms\Components\TextInput::make('customer_email')
                    ->label('Email Cliente')
                    ->email()
                    ->required(),
                    
                Forms\Components\TextInput::make('customer_phone')
                    ->label('Telefono Cliente')
                    ->tel()
                    ->required(),

                Forms\Components\Textarea::make('special_requests')
                    ->label('Richieste Speciali')
                    ->placeholder('Seggiolone, decorazioni, tavolo specifico...')
                    ->maxLength(1000)
                    ->columnSpanFull(),
                    
                Forms\Components\Select::make('status')
                    ->label('Stato')
                    ->options([
                        'confirmed' => 'Confermata',
                        'cancelled_by_restaurant' => 'Cancellata dal Ristorante',
                        'cancelled_by_customer' => 'Cancellata dal Cliente',
                    ])
                    ->default('confirmed'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('table.name')
                    ->label('Tavolo')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('primary'),
                    
                Tables\Columns\TextColumn::make('date')
                    ->label('Data')
                    ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->locale('it')->isoFormat('D MMMM YYYY'))
                    ->sortable()
                    ->weight('medium'),
                    
                Tables\Columns\TextColumn::make('timeSlot.time')
                    ->label('Orario')
                    ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->format('H:i'))
                    ->sortable()
                    ->badge()
                    ->color('success'),
                    
                Tables\Columns\TextColumn::make('guests_count')
                    ->label('Persone')
                    ->sortable()
                    ->badge()
                    ->color('gray'),
                    
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Cliente')
                    ->searchable()
                    ->weight('medium'),
                    
                Tables\Columns\TextColumn::make('customer_phone')
                    ->label('Telefono')
                    ->copyable()
                    ->copyMessage('Numero copiato!')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('special_requests')
                    ->label('Richieste Speciali')
                    ->limit(30)
                    ->searchable()
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
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Prenotata il')
                    ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->locale('it')->isoFormat('D MMM, HH:mm'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'confirmed' => 'Confermata',
                        'cancelled_by_restaurant' => 'Cancellata',
                        'cancelled_by_customer' => 'Cancellata Cliente',
                    ]),
            ])
            ->defaultSort('date', 'desc')
            ->poll('5s')
            ->striped()
            ->paginated([10, 25, 50]);
    }

    public static function getRelations(): array
    {
        return [
            
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }
}
