<?php

namespace App\Filament\Resources\Registrations\Pages;

use App\Enums\RegistrationStatus;
use App\Filament\Resources\Registrations\RegistrationResource;
use App\Models\Registration;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListRegistrations extends ListRecords
{
    protected static string $resource = RegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export CSV')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->url(route('admin.registrations.export'))
                ->openUrlInNewTab(),
        ];
    }

    public function getTabs(): array
    {
        $tab = fn (RegistrationStatus $status) => Tab::make()
            ->modifyQueryUsing(fn (Builder $query) => $query->where('status', $status->value))
            ->badge(Registration::where('status', $status->value)->count());

        return [
            'all' => Tab::make()->label('All')->badge(Registration::count()),
            'pending' => $tab(RegistrationStatus::Pending)->label('Pending'),
            'confirmed' => $tab(RegistrationStatus::Confirmed)->label('Confirmed'),
            'cancelled' => $tab(RegistrationStatus::Cancelled)->label('Cancelled'),
        ];
    }
}
