<?php

namespace App\Filament\Pages;

use App\Models\Registration;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

/**
 * Fill-in-the-blanks e-certificate builder. Everything is typed by hand (or
 * prefilled from a registration), rendered live into a print-ready A4 landscape
 * certificate that the admin sends to the printer or saves as a PDF from the
 * browser's print dialog.
 */
class CertificateGenerator extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $navigationLabel = 'E-certificates';

    protected static ?string $title = 'E-certificate generator';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.certificate-generator';

    protected Width|string|null $maxContentWidth = Width::Full;

    /**
     * @var array<string, mixed>
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(static::defaultState());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Recipient')
                    ->description('Type the details, or pull them from an existing registration.')
                    ->columns(2)
                    ->schema([
                        Select::make('registration_id')
                            ->label('Prefill from a registration')
                            ->placeholder('Search a registrant…')
                            ->searchable()
                            ->options(fn (): array => Registration::query()
                                ->with('event')
                                ->latest('id')
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(fn (Registration $registration): array => [
                                    $registration->id => "{$registration->name} — {$registration->registration_number}",
                                ])
                                ->all())
                            ->getSearchResultsUsing(fn (string $search): array => Registration::query()
                                ->with('event')
                                ->where(fn ($query) => $query
                                    ->where('name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%")
                                    ->orWhere('registration_number', 'like', "%{$search}%"))
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(fn (Registration $registration): array => [
                                    $registration->id => "{$registration->name} — {$registration->registration_number}",
                                ])
                                ->all())
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set): void {
                                $registration = $state ? Registration::with('event')->find($state) : null;

                                if (! $registration) {
                                    return;
                                }

                                $set('recipient_name', $registration->name);

                                if ($event = $registration->event) {
                                    $set('activity_title', $event->title);
                                    $set('activity_label', 'for participating in the '.strtolower((string) ($event->type ?: 'session')));
                                    $set('issued_on', $event->starts_at?->toDateString());
                                    $set('duration', $event->duration ?: null);
                                }
                            })
                            ->helperText('Optional. Overwrites the name, session, date and duration below.')
                            ->columnSpanFull(),
                        TextInput::make('recipient_name')
                            ->label('Recipient name')
                            ->required()
                            ->maxLength(120)
                            ->live(onBlur: true)
                            ->columnSpanFull(),
                    ]),

                Section::make('Certificate copy')
                    ->columns(2)
                    ->schema([
                        TextInput::make('certificate_title')
                            ->label('Certificate title')
                            ->required()
                            ->maxLength(80)
                            ->live(onBlur: true),
                        TextInput::make('presented_label')
                            ->label('Line above the name')
                            ->maxLength(80)
                            ->placeholder('is presented to')
                            ->live(onBlur: true),
                        TextInput::make('activity_label')
                            ->label('Line above the session')
                            ->maxLength(120)
                            ->placeholder('for participating in the webinar')
                            ->live(onBlur: true),
                        TextInput::make('activity_title')
                            ->label('Session title')
                            ->maxLength(160)
                            ->live(onBlur: true),
                        TextInput::make('conducted_by')
                            ->label('Line below the session')
                            ->maxLength(160)
                            ->placeholder('conducted by ABBADev.')
                            ->live(onBlur: true)
                            ->columnSpanFull(),
                        Textarea::make('body_text')
                            ->label('Body paragraph')
                            ->rows(3)
                            ->maxLength(400)
                            ->live(onBlur: true)
                            ->columnSpanFull(),
                    ]),

                Section::make('Signatories')
                    ->description('Leave a block blank to print a single signature.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('signatory_one_name')
                            ->label('Left signatory')
                            ->maxLength(80)
                            ->live(onBlur: true),
                        TextInput::make('signatory_one_role')
                            ->label('Left role')
                            ->maxLength(80)
                            ->live(onBlur: true),
                        TextInput::make('signatory_two_name')
                            ->label('Right signatory')
                            ->maxLength(80)
                            ->live(onBlur: true),
                        TextInput::make('signatory_two_role')
                            ->label('Right role')
                            ->maxLength(80)
                            ->live(onBlur: true),
                    ]),

                Section::make('Credential')
                    ->columns(3)
                    ->schema([
                        TextInput::make('credential_id')
                            ->label('Credential ID')
                            ->maxLength(64)
                            ->live(onBlur: true)
                            ->suffixAction(
                                Action::make('regenerateCredentialId')
                                    ->icon(Heroicon::OutlinedArrowPath)
                                    ->tooltip('Generate a new credential ID')
                                    ->action(fn (callable $set) => $set('credential_id', static::newCredentialId())),
                            ),
                        DatePicker::make('issued_on')
                            ->label('Session date')
                            ->native(false)
                            ->live(),
                        TextInput::make('duration')
                            ->label('Duration')
                            ->placeholder('e.g. 2 Hours')
                            ->maxLength(40)
                            ->live(onBlur: true),
                        Textarea::make('footer_note')
                            ->label('Footer disclaimer')
                            ->rows(2)
                            ->maxLength(300)
                            ->live(onBlur: true)
                            ->columnSpanFull(),
                    ]),

                Section::make('Branding')
                    ->columns(3)
                    ->schema([
                        TextInput::make('organization_name')
                            ->label('Wordmark')
                            ->maxLength(40)
                            ->live(onBlur: true),
                        Select::make('accent')
                            ->label('Accent')
                            ->options([
                                'gold' => 'Gold',
                                'blue' => 'ABBADev blue',
                            ])
                            ->selectablePlaceholder(false)
                            ->live(),
                        TextInput::make('seal_label')
                            ->label('Seal text')
                            ->maxLength(16)
                            ->live(onBlur: true),
                        Toggle::make('show_logo')
                            ->label('Show logo mark')
                            ->live(),
                        Toggle::make('show_seal')
                            ->label('Show seal')
                            ->live(),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Print / Save as PDF')
                ->icon(Heroicon::OutlinedPrinter)
                ->action(fn () => $this->js('window.print()')),
            Action::make('reset')
                ->label('Reset')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->requiresConfirmation()
                ->action(fn () => $this->form->fill(static::defaultState())),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultState(): array
    {
        return [
            'registration_id' => null,
            'recipient_name' => '',
            'certificate_title' => 'Certificate of Participation',
            'presented_label' => 'is presented to',
            'activity_label' => 'for participating in the webinar',
            'activity_title' => '',
            'conducted_by' => 'conducted by ABBADev.',
            'body_text' => 'This certificate confirms attendance and participation in the above learning session and reflects engagement in professional development activities related to the stated topic.',
            'signatory_one_name' => '',
            'signatory_one_role' => 'Resource Speaker',
            'signatory_two_name' => '',
            'signatory_two_role' => 'Founder / Program Lead',
            'credential_id' => static::newCredentialId(),
            'issued_on' => now()->toDateString(),
            'duration' => '2 Hours',
            'footer_note' => 'Attendance or recognition only. Not a professional certification, license, or academic credential. Misuse is prohibited.',
            'organization_name' => 'ABBADEV',
            'accent' => 'gold',
            'seal_label' => 'CERTIFIED',
            'show_logo' => true,
            'show_seal' => true,
        ];
    }

    protected static function newCredentialId(): string
    {
        return bin2hex(random_bytes(16));
    }
}
