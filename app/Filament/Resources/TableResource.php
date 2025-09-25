<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TableResource\Pages;
use App\Filament\Resources\TableResource\RelationManagers;
use App\Models\Table as TableModel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\CheckboxList;

class TableResource extends Resource
{
    protected static ?string $model = TableModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-table-cells';

    protected static ?string $navigationLabel = 'Tavoli';

    protected static ?string $modelLabel = 'Tavolo';

    protected static ?string $pluralModelLabel = 'Tavoli';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nome Tavolo')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TagsInput::make('capacities')
                    ->label('Capacità multiple (solo numeri)')
                    ->placeholder('Es. 2,3,4')
                    ->required(false)
                    ->rules(['array']) // l’input deve essere array
                    ->nestedRecursiveRules(['integer', 'min:1', 'max:20'])
                    ->suggestions([2,3,4,5,6,7,8]) // opzionale: suggerimenti rapidi
                    ->reorderable(),

                Forms\Components\Toggle::make('is_active')
                    ->label('Attivo')
                    ->default(true),

                Forms\Components\CheckboxList::make('opening_days')
                    ->label('Giorni di Apertura')
                    ->options([
                        '1' => 'Lunedì',
                        '2' => 'Martedì', 
                        '3' => 'Mercoledì',
                        '4' => 'Giovedì',
                        '5' => 'Venerdì',
                        '6' => 'Sabato',
                        '0' => 'Domenica',
                    ])
                    ->default(['1','2','3','4','5','6','0'])
                    ->columns(3)
                    ->required(),

                Forms\Components\CheckboxList::make('timeSlots')
                    ->label('Orari Disponibili')
                        ->relationship(
        name: 'timeSlots',
        titleAttribute: 'time',
        modifyQueryUsing: fn ($query) => $query->orderBy('time')
    )
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->time->format('H:i'))
                    ->columns(3)
                    ->bulkToggleable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome Tavolo')
                    ->searchable(),

                // 🔹 Mostra capacities se ci sono, altrimenti capacity
                Tables\Columns\TextColumn::make('capacities')
                    ->label('Posti')
                    ->formatStateUsing(fn ($state, $record) =>
                        is_array($state)
                            ? implode(', ', $state)       // Se è array → "3, 5, 6"
                            : ($state ?? $record->capacity) // Se è stringa o null → mostra lo stato o la capacity
                    )
                    ->sortable(),


                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Attivo/Disattivo'),

                Tables\Columns\TextColumn::make('bookings_count')  
                    ->label('Prenotazioni')
                    ->counts('bookings')
                    ->sortable()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creato il')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Solo Attivi'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\BookingsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTables::route('/'),
            'create' => Pages\CreateTable::route('/create'),
            'edit' => Pages\EditTable::route('/{record}/edit'),
        ];
    }
}
