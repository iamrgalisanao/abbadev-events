<?php

namespace App\Filament\Resources\Certificates\Pages;

use App\Filament\Certificates\CertificateActions;
use App\Filament\Resources\Certificates\CertificateResource;
use Filament\Resources\Pages\ViewRecord;

class ViewCertificate extends ViewRecord
{
    protected static string $resource = CertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CertificateActions::verificationPage(),
            CertificateActions::print(),
            CertificateActions::amend(),
            CertificateActions::revoke(),
            CertificateActions::reinstate(),
            CertificateActions::delete(),
        ];
    }
}
