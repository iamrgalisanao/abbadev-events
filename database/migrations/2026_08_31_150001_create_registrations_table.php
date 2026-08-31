<?php

use App\Enums\RegistrationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->string('registration_number')->unique();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('organization')->nullable();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default(RegistrationStatus::Pending->value);
            $table->string('audience')->nullable();
            $table->string('source')->nullable();
            $table->string('lead_source')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('email');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
