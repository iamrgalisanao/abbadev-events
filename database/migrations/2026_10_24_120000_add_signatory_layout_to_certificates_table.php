<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            // 'standard' (or null, for certificates issued before this
            // migration) keeps the original single-signatory template.
            // 'signatories' adds guest signatories beside the founder.
            $table->string('layout', 20)->nullable()->after('certificate_title');

            // Up to two guests, each {name, role, signature_path}. The founder
            // stays in the signatory_one_* columns, so a certificate never
            // carries more than three signatures.
            $table->json('guest_signatories')->nullable()->after('signatory_two_role');
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn(['layout', 'guest_signatories']);
        });
    }
};
