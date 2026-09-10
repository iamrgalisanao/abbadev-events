<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            // Path on the private disk to the signatory's e-signature. Stored
            // per certificate rather than per user so a certificate keeps the
            // signature it was issued with.
            $table->string('signature_path')->nullable()->after('signatory_one_role');
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn('signature_path');
        });
    }
};
