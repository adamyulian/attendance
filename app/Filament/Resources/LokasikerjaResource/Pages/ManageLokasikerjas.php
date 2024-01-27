<?php

namespace App\Filament\Resources\LokasikerjaResource\Pages;

use App\Filament\Resources\LokasikerjaResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageLokasikerjas extends ManageRecords
{
    protected static string $resource = LokasikerjaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
