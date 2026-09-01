<?php

namespace App\Filament\Resources\Events\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Session')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(160)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, ?string $old, $record) {
                                if (! $record && filled($state)) {
                                    $set('slug', Str::slug($state));
                                }
                            }),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(160)
                            ->unique(ignoreRecord: true)
                            ->helperText('Used in the registration URL — lowercase, no spaces.'),
                        Select::make('type')
                            ->options(['Seminar' => 'Seminar', 'Workshop' => 'Workshop', 'Webinar' => 'Webinar'])
                            ->default('Seminar')
                            ->required(),
                        Select::make('mode')
                            ->options(['Online' => 'Online', 'In-person' => 'In-person'])
                            ->default('Online')
                            ->required(),
                        TextInput::make('event_code')
                            ->required()
                            ->maxLength(12)
                            ->helperText('Short code for registration numbers, e.g. SEM, WRK, WEB.'),
                        Select::make('audience')
                            ->multiple()
                            ->options([
                                'Students' => 'Students',
                                'SME owners' => 'SME owners',
                                'Developers' => 'Developers',
                                'Professionals' => 'Professionals',
                            ]),
                        Textarea::make('blurb')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Schedule & pricing')
                    ->columns(2)
                    ->schema([
                        DateTimePicker::make('starts_at')
                            ->seconds(false)
                            ->required(),
                        TextInput::make('duration')->placeholder('e.g. 3 hours'),
                        TextInput::make('level')->placeholder('e.g. Beginner'),
                        TextInput::make('location')->placeholder('Venue (in-person only)'),
                        TextInput::make('expected_amount')
                            ->numeric()
                            ->default(0)
                            ->prefix('₱')
                            ->helperText('0 = free (no payment step).'),
                        TextInput::make('price_label')
                            ->placeholder('Free or ₱399')
                            ->helperText('Shown on the card; leave blank to derive from the amount.'),
                    ]),

                Section::make('Visibility')
                    ->columns(3)
                    ->schema([
                        Toggle::make('is_active')->label('Active')->default(true),
                        Toggle::make('is_featured')->label('Featured on homepage'),
                        TextInput::make('sort_order')->numeric()->default(0)->label('Sort order'),
                    ]),
            ]);
    }
}
