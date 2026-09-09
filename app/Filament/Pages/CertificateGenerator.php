<?php

namespace App\Filament\Pages;

use App\Enums\RegistrationStatus;
use App\Models\Certificate;
use App\Models\Event;
use App\Models\Registration;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

/**
 * Fill-in-the-blanks e-certificate builder. Everything is typed by hand (or
 * prefilled from a registration), rendered live into a print-ready A4 landscape
 * certificate that the admin sends to the printer or saves as a PDF from the
 * browser's print dialog.
 *
 * Issuing stores the certificate, which is what makes the printed QR code
 * meaningful: it points at the public verification page for that credential.
 */
class CertificateGenerator extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $navigationLabel = 'Certificate generator';

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
        $state = static::defaultState();

        // Arriving from "Amend in generator" on an issued certificate: load
        // it so re-issuing updates the record its QR already points at.
        $record = Certificate::find(request()->integer('certificate'));

        if ($record) {
            $state = [
                ...$state,
                ...$record->toTemplateArray(),
                'certificate_id' => $record->getKey(),
                'registration_id' => $record->registration_id,
                'event_id' => $record->registration?->event_id,
            ];
        }

        $this->form->fill($state);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Recipient')
                    ->description('Pick the session, then the attendee. Every field stays editable afterwards.')
                    ->columns(2)
                    ->schema([
                        Select::make('event_id')
                            ->label('Session')
                            ->placeholder('Search a conducted session…')
                            ->searchable()
                            ->options(fn (): array => static::sessionOptions())
                            ->getSearchResultsUsing(fn (string $search): array => static::sessionOptions($search))
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set): void {
                                // The attendee list belongs to the old session, so drop it.
                                $set('registration_id', null);

                                $event = $state ? Event::find($state) : null;

                                if (! $event) {
                                    return;
                                }

                                $set('activity_title', $event->title);
                                $set('activity_label', 'for participating in the '.strtolower((string) ($event->type ?: 'session')));
                                $set('issued_on', $event->starts_at?->toDateString());
                                $set('duration', $event->duration ?: null);
                            })
                            ->helperText('Sessions that have already taken place. Fills the session title, date and duration.'),
                        Select::make('registration_id')
                            ->label('Attendee')
                            ->placeholder(fn (callable $get): string => filled($get('event_id'))
                                ? 'Search an attendee…'
                                : 'Pick a session first')
                            ->searchable()
                            ->options(fn (callable $get): array => static::attendeeOptions($get('event_id')))
                            ->getSearchResultsUsing(fn (string $search, callable $get): array => static::attendeeOptions($get('event_id'), $search))
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set): void {
                                $registration = $state ? Registration::find($state) : null;

                                if ($registration) {
                                    $set('recipient_name', $registration->name);
                                }
                            })
                            ->helperText('Everyone registered for that session. Fills the recipient name.'),
                        TextInput::make('recipient_name')
                            ->label('Recipient name')
                            ->required()
                            ->maxLength(120)
                            ->live(onBlur: true)
                            ->columnSpanFull(),
                        Select::make('certificate_id')
                            ->label('Load an issued certificate')
                            ->placeholder('Search an issued certificate…')
                            ->searchable()
                            ->options(fn (): array => static::certificateOptions())
                            ->getSearchResultsUsing(fn (string $search): array => static::certificateOptions($search))
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set): void {
                                $certificate = $state ? Certificate::with('registration')->find($state) : null;

                                if (! $certificate) {
                                    return;
                                }

                                foreach ($certificate->toTemplateArray() as $field => $value) {
                                    $set($field, $value);
                                }

                                $set('registration_id', $certificate->registration_id);
                                $set('event_id', $certificate->registration?->event_id);
                            })
                            ->helperText('Reprint or amend a certificate that has already been issued. Overwrites everything above.')
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
                        Toggle::make('show_qr')
                            ->label('Show verification QR')
                            ->helperText('The QR takes the centre spot between the signatures.')
                            ->live(),
                        Toggle::make('show_seal')
                            ->label('Show seal')
                            ->helperText('Shown in place of the QR when the QR is off.')
                            ->live(),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('issue')
                ->label('Issue & save')
                ->icon(Heroicon::OutlinedShieldCheck)
                ->action(fn () => $this->issue()),
            Action::make('print')
                ->label('Print / Save as PDF')
                ->icon(Heroicon::OutlinedPrinter)
                ->color('gray')
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
     * Persist the current form as an issued certificate, keyed on its
     * credential ID so re-issuing the same ID amends the record the QR
     * already points at rather than orphaning it.
     */
    public function issue(): void
    {
        $data = $this->form->getState();

        $certificate = Certificate::updateOrCreate(
            ['credential_id' => $data['credential_id']],
            [
                ...collect(Certificate::TEMPLATE_FIELDS)
                    ->mapWithKeys(fn (string $field): array => [$field => $data[$field] ?? null])
                    ->all(),
                'registration_id' => $data['registration_id'] ?? null,
                'issued_by' => Auth::id(),
            ],
        );

        $this->data['certificate_id'] = $certificate->id;

        Notification::make()
            ->title('Certificate issued')
            ->body('The printed QR code now resolves to '.$certificate->verificationUrl())
            ->success()
            ->persistent()
            ->actions([
                Action::make('open')
                    ->label('Open verification page')
                    ->url($certificate->verificationUrl(), shouldOpenInNewTab: true),
            ])
            ->send();
    }

    /**
     * The verification URL the preview's QR encodes. Derived from the credential
     * ID alone, so the QR is correct in the preview before the record is saved.
     */
    public function getVerificationUrl(): ?string
    {
        $credential = $this->data['credential_id'] ?? null;

        return filled($credential) ? route('certificates.verify', $credential) : null;
    }

    /**
     * Sessions that have already run. A certificate attests to attendance, so
     * one for a session that has not happened yet would be issuing a claim
     * about the future.
     *
     * @return array<int, string>
     */
    protected static function sessionOptions(?string $search = null): array
    {
        return Event::query()
            ->where('starts_at', '<=', now())
            ->when($search, fn ($query) => $query->where('title', 'like', "%{$search}%"))
            ->orderByDesc('starts_at')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (Event $event): array => [
                $event->id => $event->title.' — '.($event->starts_at?->format('M j, Y') ?? 'no date'),
            ])
            ->all();
    }

    /**
     * Everyone registered for a session. Registrations that never reached
     * confirmed are flagged rather than hidden - someone can attend on a
     * pending payment, and that is the admin's call, not this list's.
     *
     * @return array<int, string>
     */
    protected static function attendeeOptions(mixed $eventId, ?string $search = null): array
    {
        if (blank($eventId)) {
            return [];
        }

        return Registration::query()
            ->where('event_id', $eventId)
            ->when($search, fn ($query) => $query
                ->where(fn ($inner) => $inner
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('registration_number', 'like', "%{$search}%")))
            ->orderBy('name')
            ->limit(200)
            ->get()
            ->mapWithKeys(fn (Registration $registration): array => [
                $registration->id => $registration->name.' — '.$registration->email.(
                    $registration->status === RegistrationStatus::Confirmed
                        ? ''
                        : ' ('.$registration->status->getLabel().')'
                ),
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    protected static function certificateOptions(?string $search = null): array
    {
        return Certificate::query()
            ->when($search, fn ($query) => $query
                ->where(fn ($inner) => $inner
                    ->where('recipient_name', 'like', "%{$search}%")
                    ->orWhere('credential_id', 'like', "%{$search}%")
                    ->orWhere('activity_title', 'like', "%{$search}%")))
            ->latest('id')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (Certificate $certificate): array => [
                $certificate->id => $certificate->recipient_name.' — '.$certificate->credential_id,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultState(): array
    {
        return [
            'certificate_id' => null,
            'event_id' => null,
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
            'show_qr' => true,
            'show_seal' => true,
        ];
    }

    protected static function newCredentialId(): string
    {
        return bin2hex(random_bytes(16));
    }
}
