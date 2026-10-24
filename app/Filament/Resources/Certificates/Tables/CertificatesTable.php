<?php

namespace App\Filament\Resources\Certificates\Tables;

use App\Enums\CertificateStatus;
use App\Filament\Certificates\CertificateActions;
use App\Services\CertificatePdfRenderer;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class CertificatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('recipient_name')
                    ->label('Recipient')
                    ->searchable()
                    ->description(fn ($record) => $record->credential_id)
                    ->wrap(),
                TextColumn::make('activity_title')
                    ->label('Session')
                    ->searchable()
                    ->placeholder('—')
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('issued_on')
                    ->label('Session date')
                    ->date('M j, Y')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->description(fn ($record) => $record->revocation_reason)
                    ->wrap(),
                TextColumn::make('issuer.name')
                    ->label('Issued by')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Issued')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(CertificateStatus::class)
                    ->label('Status'),
                SelectFilter::make('session')
                    ->relationship('registration.event', 'title')
                    ->label('Session'),
            ])
            ->recordActions([
                ViewAction::make(),
                CertificateActions::revoke(),
                CertificateActions::reinstate(),
                ActionGroup::make([
                    CertificateActions::verificationPage(),
                    CertificateActions::print(),
                    CertificateActions::amend(),
                    CertificateActions::delete(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('downloadPdfs')
                        ->label('Download as PDFs')
                        ->icon(Heroicon::OutlinedArrowDownTray)
                        ->modalHeading('Download selected certificates')
                        ->modalDescription('Each certificate is saved as its own PDF, named after the participant, and the lot arrives as a single ZIP. Revoked certificates are left out.')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records, CertificatePdfRenderer $renderer) {
                            $active = $records->filter(fn ($certificate): bool => $certificate->isActive())->values();

                            if ($active->isEmpty()) {
                                Notification::make()->title('Nothing to download')->body('Every selected certificate is revoked.')->warning()->send();

                                return null;
                            }

                            if ($active->count() > CertificatePdfRenderer::MAX_BATCH) {
                                Notification::make()
                                    ->title('Select '.CertificatePdfRenderer::MAX_BATCH.' or fewer')
                                    ->body('You selected '.$active->count().'. Download in smaller batches so the file finishes building.')
                                    ->danger()
                                    ->send();

                                return null;
                            }

                            set_time_limit(0);

                            try {
                                $path = $renderer->zip($active);
                            } catch (\Throwable $e) {
                                report($e);

                                Notification::make()
                                    ->title('Could not build the PDFs')
                                    ->body('Check that Node and Chrome are installed on the server (see DEPLOYMENT.md).')
                                    ->danger()
                                    ->send();

                                return null;
                            }

                            return response()
                                ->download($path, 'certificates-'.now()->format('Y-m-d-His').'.zip')
                                ->deleteFileAfterSend(true);
                        }),
                    DeleteBulkAction::make()
                        ->modalDescription('Any QR already printed for the selected credentials will stop resolving. To withdraw certificates that have been handed out, revoke them instead.'),
                ]),
            ]);
    }
}
