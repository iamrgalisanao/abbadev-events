<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\Registration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * One-off cleanup: wipe every registration + payment + uploaded receipt file,
 * for clearing test data before real registrations come in. Leaves events,
 * users, and the schema untouched.
 */
class ClearRegistrations extends Command
{
    protected $signature = 'registrations:clear {--force : Skip the confirmation prompt}';

    protected $description = 'Permanently delete ALL registrations, their payments, and uploaded receipt files (clears test data). Events and users are kept.';

    public function handle(): int
    {
        $registrations = Registration::count();
        $payments = Payment::count();

        if ($registrations === 0 && $payments === 0) {
            $this->info('Nothing to clear - there are no registrations or payments.');

            return self::SUCCESS;
        }

        $this->warn("This permanently deletes {$registrations} registration(s), {$payments} payment(s), and their uploaded receipt files. It cannot be undone.");
        $this->line('Events and admin users are NOT affected.');

        if (! $this->option('force') && ! $this->confirm('Proceed?')) {
            $this->info('Aborted. Nothing was deleted.');

            return self::SUCCESS;
        }

        // Remove receipt files first - the DB cascade never touches the filesystem.
        $files = 0;
        Payment::query()
            ->whereNotNull('receipt_path')
            ->pluck('receipt_path')
            ->each(function (string $path) use (&$files): void {
                if (Storage::disk('local')->exists($path)) {
                    Storage::disk('local')->delete($path);
                    $files++;
                }
            });

        DB::transaction(function (): void {
            Payment::query()->delete();
            Registration::query()->delete();
        });

        $this->info("Deleted {$registrations} registration(s), {$payments} payment(s), and {$files} receipt file(s). Ready for real registrations.");

        return self::SUCCESS;
    }
}
