<?php

namespace App\Filament\Resources;

use Closure;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Form;
use App\Models\Attendance;
use Filament\Tables\Table;
use App\Helpers\LocationHelpers;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Section;
use Illuminate\Database\Eloquent\Builder;
use Cheesegrits\FilamentGoogleMaps\Fields\Map;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\AttendanceResource\Pages;
use App\Filament\Resources\AttendanceResource\RelationManagers;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Absensi Kedatangan/Kepulangan')
                // ->placeholder('Pilih Datang/Pulang')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('status')
                    ->options([
                        'datang' => 'Datang',
                        'pulang' => 'Pulang'
                    ])
                    ->columnSpanFull()
                    ->native(false),
                    Map::make('location')
                    ->columnSpanFull()
                    // ->rules([
                        
                    //     fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                    //             // The allowed location (latitude and longitude).
                    //             // $allowedLocation = [Target::find($get('target_id'))->lat, Target::find($get('target_id'))->lng];
                    //             // dd($allowedLocation);
                    //             $allowedLocation = [-7.309865473166658, 112.74843818425389];
                    
                    //             // The radius in meters.
                    //             $radius = 100;
                    
                    //             // Convert the value (user's location) to an array [latitude, longitude].
                    //             // $userLocation = explode(',', $value);
                    //             $userLocation = [$get('lat'), $get('lng')];
                    
                    //             // Calculate the distance between user and allowed location.
                    //             $distance = LocationHelpers::haversineDistance($userLocation, $allowedLocation);

                                                                  
                    //             // Check if the user is within the specified radius.
                    //             if ($distance > $radius) {
                    //                 $fail("The selected location is not within the allowed radius.");
                    //             }
                            
                    //     }])
                    ->label('Your Location')
                    ->geolocate() // adds a button to request device location and set map marker accordingly
                    ->geolocateOnLoad(true, 'always')// Enable geolocation on load for every form
                    ->draggable(false) // Disable dragging to move the marker
                    ->clickable(false) // Disable clicking to move the marker
                    ->defaultZoom(15) // Set the initial zoom level to 500
                    ->autocomplete('address') // field on form to use as Places geocompletion field
                    ->autocompleteReverse(true) // reverse geocode marker location to autocomplete field
                    ->reactive()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                        $set('lat', $state['lat']);
                        $set('lng', $state['lng']);}),
                Forms\Components\TextInput::make('lat')
                    ->readOnly()
                    ->maxLength(255),
                Forms\Components\TextInput::make('lng')
                    ->readOnly()
                    ->maxLength(255),
                Forms\Components\FileUpload::make('img')
                    ->label('Foto Kehadiran'),
                Forms\Components\TextInput::make('address')
                    ->readOnly(),
                ]),
                
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $userId = Auth::user()->id;
                $query->where('user_id', $userId);
            })
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.role')
                    ->visible(Auth::user()->role === 'admin')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'datang' => 'info',
                        'pulang' => 'success',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('img')
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->limit(20)
                    ->searchable(),
                Tables\Columns\TextColumn::make('Status')
                    ->state(function (Attendance $record) {
                        $submissionTime = Carbon::parse($record->created_at); // Assuming the column holds the submission time
                        $deadlineTime = Carbon::parse('07:00:00'); // Static value for the deadline time

                        $deadlineDay = $record->created_at->format('Y-m-d');
                        $deadline = Carbon::parse($deadlineDay)->setTime($deadlineTime->hour, $deadlineTime->minute, $deadlineTime->second);

                        if ($submissionTime->lte($deadline)) {
                            // On time or before the deadline
                            return 'On Time';
                        } else {
                            // Late
                            $lateDuration = $submissionTime->diff($deadline);
                            $hours = $lateDuration->format('%h');
                            $minutes = $lateDuration->format('%i');
                        
                            if ($hours >= 1) {
                                // Late by more than 1 hour
                                return $hours . ' Hours and ' . $minutes . ' Minutes Late';
                            } else {
                                // Late by less than 1 hour
                                return $minutes . ' Minutes Late';
                            }
                        }

                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Time')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'view' => Pages\ViewAttendance::route('/{record}'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}
