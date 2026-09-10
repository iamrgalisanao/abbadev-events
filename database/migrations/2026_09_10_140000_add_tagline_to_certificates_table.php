<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            // Stored with the record like every other printed line, so a
            // certificate verified later renders the tagline it was issued
            // with rather than whatever the generator defaults to by then.
            $table->string('tagline')->nullable()->after('organization_name');
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn('tagline');
        });
    }
};
