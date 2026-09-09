<?php

namespace App\Filament\Resources\Certificates\Schemas;

use App\Models\Certificate;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class CertificateInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Credential')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('credential_id')->label('Credential ID')->copyable(),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('verification_url')
                            ->label('Verification page')
                            ->state(fn (Certificate $record) => $record->verificationUrl())
                            ->url(fn (Certificate $record) => $record->verificationUrl(), shouldOpenInNewTab: true)
                            ->copyable()
                            ->columnSpanFull(),
                    ]),

                Section::make('Revocation')
                    ->visible(fn (Certificate $record) => ! $record->isActive())
                    ->schema([
                        TextEntry::make('revocation_reason')
                            ->label('Reason shown on the verification page')
                            ->placeholder('—'),
                    ]),

                Section::make('Certificate')
                    ->description('Exactly what this credential renders, then and now.')
                    ->columnSpanFull()
                    ->schema([
                        View::make('certificates.partials.admin-preview'),
                    ]),

                Section::make('Details')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('recipient_name')->label('Recipient'),
                        TextEntry::make('activity_title')->label('Session')->placeholder('—'),
                        TextEntry::make('issued_on')->label('Session date')->date('F j, Y')->placeholder('—'),
                        TextEntry::make('duration')->label('Duration')->placeholder('—'),
                        TextEntry::make('signatory_one_name')->label('Left signatory')->placeholder('—'),
                        TextEntry::make('signatory_two_name')->label('Right signatory')->placeholder('—'),
                    ]),

                Section::make('Issuance')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('issuer.name')->label('Issued by')->placeholder('—'),
                        TextEntry::make('created_at')->label('Issued')->dateTime('M j, Y g:i A'),
                        TextEntry::make('registration.registration_number')
                            ->label('From registration')
                            ->placeholder('—'),
                        TextEntry::make('updated_at')->label('Last amended')->dateTime('M j, Y g:i A'),
                    ]),
            ]);
    }
}
