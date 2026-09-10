<?php

namespace App\Filament\Pages;

use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Filament\Resources\Certificates\CertificateResource;
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
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

                Section::make('Signatory')
                    ->description('One signature, printed beside the verification QR.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('signatory_one_name')
                            ->label('Name')
                            ->maxLength(80)
                            ->live(onBlur: true),
                        TextInput::make('signatory_one_role')
                            ->label('Role')
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
                        TextInput::make('tagline')
                            ->label('Tagline')
                            ->maxLength(40)
                            ->helperText('Printed under the wordmark. Leave blank to omit it.')
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
            Action::make('issueForSession')
                ->label('Issue for the whole session')
                ->icon(Heroicon::OutlinedUserGroup)
                ->color('gray')
                ->visible(fn (): bool => filled($this->data['event_id'] ?? null))
                ->modalHeading('Issue for the whole session')
                ->modalSubmitActionLabel('Issue certificates')
                ->modalWidth(Width::Large)
                ->schema([
                    Toggle::make('include_unconfirmed')
                        ->label('Include registrations that are not confirmed')
                        ->helperText('A registration with a verified payment is always included, whatever its status. Turn this on to also cover registrations that neither confirmed nor paid.')
                        ->live(),
                    TextEntry::make('summary')
                        ->label('What this will do')
                        ->state(fn (callable $get): array => $this->bulkSummary((bool) $get('include_unconfirmed')))
                        ->listWithLineBreaks()
                        ->bulleted(),
                ])
                ->action(fn (array $data) => $this->bulkIssue((bool) ($data['include_unconfirmed'] ?? false))),
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
     * Issue the copy currently in the form to every attendee of the selected
     * session, one certificate each with its own credential ID.
     *
     * This only ever inserts into `certificates`. Registrations and sessions
     * are read, never written, and an attendee who already holds a certificate
     * is skipped rather than overwritten - so a second run tops up the people
     * who were missed instead of reissuing everyone. The whole batch is one
     * transaction: if any row fails, none are written.
     */
    public function bulkIssue(bool $includeUnconfirmed = false): void
    {
        $event = Event::find($this->data['event_id'] ?? null);

        if (! $event) {
            Notification::make()
                ->title('Pick a session first')
                ->danger()
                ->send();

            return;
        }

        $template = collect(Certificate::TEMPLATE_FIELDS)
            ->mapWithKeys(fn (string $field): array => [$field => $this->data[$field] ?? null])
            ->except(['credential_id', 'recipient_name'])
            ->all();

        if (blank($template['certificate_title'])) {
            Notification::make()
                ->title('Give the certificate a title first')
                ->danger()
                ->send();

            return;
        }

        $recipients = $this->bulkRecipients($event->getKey(), $includeUnconfirmed);
        $alreadyIssued = static::registrationsWithCertificates($recipients->modelKeys());

        $issued = 0;
        $skipped = 0;

        DB::transaction(function () use ($recipients, $alreadyIssued, $template, &$issued, &$skipped): void {
            foreach ($recipients as $registration) {
                if (in_array($registration->getKey(), $alreadyIssued, strict: true)) {
                    $skipped++;

                    continue;
                }

                Certificate::create([
                    ...$template,
                    'credential_id' => static::newCredentialId(),
                    'recipient_name' => $registration->name,
                    'registration_id' => $registration->getKey(),
                    'issued_by' => Auth::id(),
                ]);

                $issued++;
            }
        });

        if ($issued === 0) {
            Notification::make()
                ->title('Nothing to issue')
                ->body($skipped > 0
                    ? "Every attendee of {$event->title} already holds a certificate."
                    : "No attendees of {$event->title} matched.")
                ->warning()
                ->send();

            return;
        }

        Notification::make()
            ->title($issued.' '.str('certificate')->plural($issued).' issued')
            ->body(trim($event->title.'. '.($skipped > 0 ? $skipped.' already held one and were left untouched.' : '')))
            ->success()
            ->persistent()
            ->actions([
                Action::make('review')
                    ->label('Review issued certificates')
                    ->url(CertificateResource::getUrl()),
            ])
            ->send();
    }

    /**
     * A plain-language account of what the batch will do, shown in the
     * confirmation modal before anything is written.
     *
     * @return array<int, string>
     */
    public function bulkSummary(bool $includeUnconfirmed = false): array
    {
        $event = Event::find($this->data['event_id'] ?? null);

        if (! $event) {
            return ['Pick a session first.'];
        }

        $registrations = static::sessionRegistrations($event->getKey(), $includeUnconfirmed);
        $recipients = static::deduplicate($registrations);
        $collapsed = $registrations->count() - $recipients->count();
        $alreadyIssued = count(static::registrationsWithCertificates($recipients->modelKeys()));
        $toIssue = $recipients->count() - $alreadyIssued;

        $lines = [
            $event->title,
            $registrations->count().' '.str('registration')->plural($registrations->count())
                .($includeUnconfirmed ? ' (confirmed and unconfirmed)' : ' (confirmed, or with a verified payment)'),
        ];

        if ($collapsed > 0) {
            $lines[] = $collapsed.' duplicate '.str('registration')->plural($collapsed)
                .' for the same email will be counted once';
        }

        if ($alreadyIssued > 0) {
            $lines[] = $alreadyIssued.' already hold a certificate and will be skipped';
        }

        $lines[] = $toIssue > 0
            ? 'Creates '.$toIssue.' '.str('certificate')->plural($toIssue).', each with its own credential ID and QR'
            : 'Nothing left to issue';

        $lines[] = 'No registration or session record is modified';

        return $lines;
    }

    /**
     * The attendees a batch would cover: the session's registrations, minus
     * repeat sign-ups from the same person.
     *
     * @return EloquentCollection<int, Registration>
     */
    protected function bulkRecipients(int $eventId, bool $includeUnconfirmed): EloquentCollection
    {
        return static::deduplicate(static::sessionRegistrations($eventId, $includeUnconfirmed));
    }

    /**
     * @return EloquentCollection<int, Registration>
     */
    protected static function sessionRegistrations(int $eventId, bool $includeUnconfirmed): EloquentCollection
    {
        return Registration::query()
            ->with('payments')
            ->where('event_id', $eventId)
            // A verified payment is the stronger signal, so it carries a
            // registration into the batch on its own. PaymentService::confirm()
            // normally moves both together, but where they have drifted apart
            // the money is what settles it.
            ->unless($includeUnconfirmed, fn ($query) => $query->where(fn ($inner) => $inner
                ->where('status', RegistrationStatus::Confirmed->value)
                ->orWhereHas('payments', fn ($payments) => $payments->where('status', PaymentStatus::Verified->value))))
            ->orderBy('name')
            ->get();
    }

    /**
     * People sign up twice. Issuing the same person two certificates for one
     * session is worse than missing an edge case, so repeat sign-ups on the
     * same email collapse to a single recipient.
     *
     * A verified payment decides it: that is the row an admin actually checked
     * against the GCash portal, so the certificate hangs off the registration
     * the money is attached to. Registration status breaks the tie for free
     * sessions, where there is no payment either way, and the earliest sign-up
     * breaks it after that.
     *
     * @param  EloquentCollection<int, Registration>  $registrations
     * @return EloquentCollection<int, Registration>
     */
    protected static function deduplicate(EloquentCollection $registrations): EloquentCollection
    {
        $kept = $registrations
            ->groupBy(fn (Registration $registration): string => mb_strtolower(trim((string) $registration->email)))
            // One composite key rather than sortBy's multi-comparison array:
            // that form treats callables as comparators, not value extractors.
            ->map(fn ($group) => $group
                ->sortBy(fn (Registration $registration): array => [
                    static::hasVerifiedPayment($registration) ? 0 : 1,
                    $registration->status === RegistrationStatus::Confirmed ? 0 : 1,
                    $registration->getKey(),
                ])
                ->first())
            ->values()
            ->all();

        return new EloquentCollection($kept);
    }

    protected static function hasVerifiedPayment(Registration $registration): bool
    {
        return $registration->payments
            ->contains(fn ($payment): bool => $payment->status === PaymentStatus::Verified);
    }

    /**
     * @param  array<int, int|string>  $registrationIds
     * @return array<int, int>
     */
    protected static function registrationsWithCertificates(array $registrationIds): array
    {
        if ($registrationIds === []) {
            return [];
        }

        return Certificate::query()
            ->whereIn('registration_id', $registrationIds)
            ->pluck('registration_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
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
            'signatory_one_role' => 'Founder / Resource Speaker',
            // Kept in state so an older two-signatory certificate loaded for
            // reprint keeps its second signature; the form no longer sets one.
            'signatory_two_name' => '',
            'signatory_two_role' => '',
            'credential_id' => static::newCredentialId(),
            'issued_on' => now()->toDateString(),
            'duration' => '2 Hours',
            'footer_note' => 'Attendance or recognition only. Not a professional certification, license, or academic credential. Misuse is prohibited.',
            'organization_name' => 'ABBADEV',
            'tagline' => 'IT SOLUTIONS',
            'accent' => 'blue',
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
