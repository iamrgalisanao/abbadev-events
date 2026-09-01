<?php

namespace App\Filament\Resources\Registrations\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RegistrationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Registrant')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('registration_number')->label('Registration #')->copyable(),
                        TextEntry::make('status')->label('Registration status')->badge(),
                        TextEntry::make('name')->label('Name'),
                        TextEntry::make('email')->label('Email')->copyable(),
                        TextEntry::make('phone')->label('Phone')->placeholder('—'),
                        TextEntry::make('organization')->label('Organization')->placeholder('—'),
                        TextEntry::make('audience')->label('Audience')->placeholder('—'),
                        TextEntry::make('event.title')->label('Event'),
                    ]),

                Section::make('Attribution')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('lead_source')->label('Lead source')->placeholder('—'),
                        TextEntry::make('source')->label('Source')->placeholder('—'),
                        TextEntry::make('meta.utm.utm_campaign')->label('UTM campaign')->placeholder('—'),
                        TextEntry::make('created_at')->label('Registered')->dateTime('M j, Y g:i A'),
                    ]),

                Section::make('Payment')
                    ->columns(2)
                    ->visible(fn ($record) => $record->payment !== null)
                    ->schema([
                        TextEntry::make('payment.status')->label('Payment status')->badge(),
                        TextEntry::make('payment.amount_submitted')->label('Amount submitted')->money('PHP'),
                        TextEntry::make('payment.reference_number')->label('GCash reference')->copyable(),
                        TextEntry::make('payment.email_status')->label('Confirmation email')->badge(),
                    ]),
            ]);
    }
}
