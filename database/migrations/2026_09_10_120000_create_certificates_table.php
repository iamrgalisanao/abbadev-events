<?php

use App\Enums\CertificateStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->string('credential_id')->unique();
            $table->string('recipient_name');

            // Every printed line is stored with the record so a certificate
            // verified years from now renders exactly as it was issued, even
            // if the default copy on the generator has moved on since.
            $table->string('certificate_title');
            $table->string('presented_label')->nullable();
            $table->string('activity_label')->nullable();
            $table->string('activity_title')->nullable();
            $table->string('conducted_by')->nullable();
            $table->text('body_text')->nullable();
            $table->string('signatory_one_name')->nullable();
            $table->string('signatory_one_role')->nullable();
            $table->string('signatory_two_name')->nullable();
            $table->string('signatory_two_role')->nullable();
            $table->date('issued_on')->nullable();
            $table->string('duration')->nullable();
            $table->text('footer_note')->nullable();
            $table->string('organization_name')->nullable();
            $table->string('accent')->default('gold');
            $table->string('seal_label')->nullable();
            $table->boolean('show_logo')->default(true);
            $table->boolean('show_seal')->default(true);
            $table->boolean('show_qr')->default(true);

            $table->string('status')->default(CertificateStatus::Active->value);
            $table->string('revocation_reason')->nullable();
            $table->foreignId('registration_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('recipient_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
