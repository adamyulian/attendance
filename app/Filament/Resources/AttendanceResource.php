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
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance\checkOnTime;
use Filament\Forms\Components\Section;
use Filament\Support\Enums\FontWeight;
use Filament\Infolists\Components\Group;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Cheesegrits\FilamentGoogleMaps\Fields\Map;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\AttendanceResource\Pages;
use Filament\Infolists\Components\Section as InfolistSection;
use App\Filament\Resources\AttendanceResource\RelationManagers;
use App\Models\Lokasikerja;

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
                    ->rules([
                        
                        fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                // The allowed location (latitude and longitude).

                                $userTeam = auth()->user()->team_id;
                                $allowedLocation = [Lokasikerja::where('team_id',$userTeam)->value('lat'), Lokasikerja::where('team_id',$userTeam)->value('lng')];
                                // dd($allowedLocation);
                                // $allowedLocation = [-7.309865473166658, 112.74843818425389];
                    
                                // The radius in meters.
                                $radius = 100;
                    
                                // Convert the value (user's location) to an array [latitude, longitude].
                                // $userLocation = explode(',', $value);
                                $userLocation = [$get('lat'), $get('lng')];
                    
                                // Calculate the distance between user and allowed location.
                                $distance = LocationHelpers::haversineDistance($userLocation, $allowedLocation);

                                                                  
                                // Check if the user is within the specified radius.
                                if ($distance > $radius) {
                                    $fail("Lokasi Anda tidak berada pada Radius yang diizinkan");
                                }
                            
                        }])
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
                    ->fetchFileInformation(false)
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

                if (Auth::user()->role === 'admin') {
                    return $query;
                }

                // Non-admin users can only view their own component 
                $userId = Auth::user()->id;
                return $query->where('user_id', $userId);
            })
            ->defaultSort(column:'created_at', direction:'desc')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nama')
                    ->sortable(),
               
                Tables\Columns\TextColumn::make('status')
                    ->label('Datang/Pulang')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'datang' => 'info',
                        'pulang' => 'success',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime()
                    ->sortable(),
               
                Tables\Columns\TextColumn::make('Absensi')
                    ->state(function (Attendance $record) {
                        $action = $record->status;
                    
                        if ($action === 'datang') {
                            $submissionTime = Carbon::parse($record->created_at);
                            $deadlineTime = Carbon::parse('08:00:00');
                    
                            $deadlineDay = $record->created_at->format('Y-m-d');
                            $deadline = Carbon::parse($deadlineDay)->setTime($deadlineTime->hour, $deadlineTime->minute, $deadlineTime->second);
                    
                            if ($submissionTime->lte($deadline)) {
                                return 'On Time';
                            } else {
                                $lateDuration = $submissionTime->diff($deadline);
                                $hours = $lateDuration->format('%h');
                                $minutes = $lateDuration->format('%i');
                    
                                if ($hours >= 1) {
                                    return $hours . ' Hours and ' . $minutes . ' Minutes Late';
                                } else {
                                    return $minutes . ' Minutes Late';
                                }
                            };
                        } elseif ($action === 'pulang') {
                            $departureTime = Carbon::parse($record->created_at);
                            $agreedDepartureTime = Carbon::parse('17:00:00');

                            $departureDay = $record->created_at->format('Y-m-d');
                            $agreedDeparture = Carbon::parse($departureDay)->setTime($agreedDepartureTime->hour, $agreedDepartureTime->minute, $agreedDepartureTime->second);
                    
                            if ($departureTime->lt($agreedDeparture)) {
                                $earlyDuration = $agreedDeparture->diff($departureTime);
                                $hours = $earlyDuration->format('%h');
                                $minutes = $earlyDuration->format('%i');
                    
                                if ($hours >= 1) {
                                    return $hours . ' Hours and ' . $minutes . ' Minutes Early';
                                } else {
                                    return $minutes . ' Minutes Early';
                                }
                            } else {
                                return 'On Time';
                            };
                        } else {
                            return 'Invalid action type'; // Handle other cases as needed
                        }
                    }
                ),

                Tables\Columns\ImageColumn::make('img')
                ->searchable()
                ->label('Image'),
                Tables\Columns\TextColumn::make('address')
                ->label('Lokasi Absen')
                ->limit(20)
                ->searchable(),
                
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

    public static function infolist(Infolist $infolist): Infolist

    {
        return $infolist
            ->schema([
                    InfolistSection::make('Attendance Information')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('user.name')
                            ->columnSpan(1)
                            ->label('Name')
                            ->weight(FontWeight::Bold)
                            ->size(TextEntry\TextEntrySize::Large),
                        TextEntry::make('status')
                            ->columnSpan(1)
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'datang' => 'info',
                                'pulang' => 'success',
                            })
                            ->label('Status'),
                        TextEntry::make('created_at')
                            ->columnSpan(1)
                            ->label('Time'),
                        ImageEntry::make('img')
                            ->label('Image')
                            ->columnSpan(1)
                            ->extraImgAttributes([
                            'alt' => 'Activity Picture',
                            'loading' => 'lazy',
                            ])
                            ->size(300)
                        ]),
            ]);
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
