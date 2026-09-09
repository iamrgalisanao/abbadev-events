<?php

namespace App\Filament\Resources\Certificates\Pages;

use App\Enums\CertificateStatus;
use App\Filament\Pages\CertificateGenerator;
use App\Filament\Resources\Certificates\CertificateResource;
use App\Models\Certificate;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListCertificates extends ListRecords
{
    protected static string $resource = CertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('issue')
                ->label('Issue a certificate')
                ->icon(Heroicon::OutlinedPlus)
                ->url(CertificateGenerator::getUrl()),
        ];
    }

    public function getTabs(): array
    {
        $count = fn (CertificateStatus $status) => Certificate::where('status', $status->value)->count();
        $tab = fn (CertificateStatus $status) => Tab::make()
            ->modifyQueryUsing(fn (Builder $query) => $query->where('status', $status->value))
            ->badge($count($status));

        return [
            'active' => $tab(CertificateStatus::Active)->label('Active'),
            'revoked' => $tab(CertificateStatus::Revoked)->label('Revoked'),
            'all' => Tab::make()->label('All'),
        ];
    }
}
