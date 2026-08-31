<?php

use App\Enums\EmailStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $table->string('payment_method')->default('gcash');
            $table->decimal('expected_amount', 10, 2);
            $table->decimal('amount_submitted', 10, 2)->nullable();
            $table->string('reference_number')->nullable();
            $table->string('receipt_path')->nullable();
            $table->string('status')->default(PaymentStatus::ForVerification->value);
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            $table->string('email_status')->default(EmailStatus::NotSent->value);
            $table->timestamp('confirmation_email_sent_at')->nullable();
            $table->timestamps();

            $table->index('reference_number');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
