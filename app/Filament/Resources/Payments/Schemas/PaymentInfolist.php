<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Models\Payment;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PaymentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Registrant')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('registration.registration_number')->label('Registration #')->copyable(),
                        TextEntry::make('registration.status')->label('Registration status')->badge(),
                        TextEntry::make('registration.name')->label('Name'),
                        TextEntry::make('registration.email')->label('Email')->copyable(),
                        TextEntry::make('registration.phone')->label('Phone')->placeholder('—'),
                        TextEntry::make('registration.organization')->label('Organization')->placeholder('—'),
                        TextEntry::make('registration.event.title')->label('Event'),
                        TextEntry::make('registration.lead_source')->label('Lead source')->placeholder('—'),
                    ]),

                Section::make('Payment')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('expected_amount')->label('Amount expected')->money('PHP'),
                        TextEntry::make('amount_submitted')
                            ->label('Amount submitted')
                            ->money('PHP')
                            ->color(fn (Payment $record) => $record->hasAmountMismatch() ? 'warning' : null)
                            ->hint(fn (Payment $record) => $record->hasAmountMismatch() ? 'Mismatch' : null),
                        TextEntry::make('reference_number')->label('GCash reference')->copyable(),
                        TextEntry::make('payment_method')->label('Method'),
                        TextEntry::make('status')->label('Payment status')->badge(),
                        TextEntry::make('created_at')->label('Submitted')->dateTime('M j, Y g:i A'),
                    ]),

                Section::make('Verification')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('verifier.name')->label('Verified by')->placeholder('—'),
                        TextEntry::make('verified_at')->label('Verified at')->dateTime('M j, Y g:i A')->placeholder('—'),
                        TextEntry::make('email_status')->label('Confirmation email')->badge(),
                        TextEntry::make('confirmation_email_sent_at')->label('Email sent at')->dateTime('M j, Y g:i A')->placeholder('—'),
                        TextEntry::make('verification_notes')->label('Notes')->columnSpanFull()->placeholder('—'),
                    ]),
            ]);
    }
}
