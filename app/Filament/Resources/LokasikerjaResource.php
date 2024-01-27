<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Lokasikerja;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Cheesegrits\FilamentGoogleMaps\Fields\Map;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\LokasikerjaResource\Pages;
use App\Filament\Resources\LokasikerjaResource\RelationManagers;

class LokasikerjaResource extends Resource
{
    protected static ?string $model = Lokasikerja::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Location';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->maxLength(255),
                Forms\Components\Select::make('team')
                    ->native(false)
                    ->options([
                        'team 1' =>'Team Bendul',
                        'team 2' => 'Team Wawan',
                    ])
                    ->required(),
                    Map::make('location')
                    ->columnSpan(2)
                    ->label('Create Location')
                    ->geolocate() // adds a button to request device location and set map marker accordingly
                    ->geolocateOnLoad(true, 'always')// Enable geolocation on load for every form
                    ->draggable() // Disable dragging to move the marker
                    ->clickable() // Disable clicking to move the marker
                    ->defaultZoom(15) // Set the initial zoom level to 500
                    ->autocomplete('address') // field on form to use as Places geocompletion field
                    ->autocompleteReverse(true) // reverse geocode marker location to autocomplete field
                    ->reactive()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                        $set('lat', $state['lat']);
                        $set('lng', $state['lng']);}),
                Forms\Components\TextInput::make('address')
                    ->label('Location Address')
                    ->readOnly(),
                Forms\Components\TextInput::make('lat')
                    ->maxLength(255)
                    ->readOnly(),
                Forms\Components\TextInput::make('lng')
                    ->maxLength(255)
                    ->readOnly(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('location')
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('team')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'team 1' => 'info',
                        'team 2' => 'success',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageLokasikerjas::route('/'),
        ];
    }
}
