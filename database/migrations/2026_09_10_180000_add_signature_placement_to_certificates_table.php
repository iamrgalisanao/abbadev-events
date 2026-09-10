<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            // How the signature sits on its line: width as a percentage of the
            // signature block, then a nudge either way as a percentage of the
            // image's own size.
            //
            // Nullable rather than defaulted, because the placement controls
            // are hidden until a signature is uploaded and an unfilled field
            // dehydrates to null. The template reads null as 80/0/0, which is
            // the placement certificates issued before this migration were
            // printed with, so existing records render unchanged.
            $table->unsignedSmallInteger('signature_scale')->nullable()->after('signature_path');
            $table->smallInteger('signature_offset_x')->nullable()->after('signature_scale');
            $table->smallInteger('signature_offset_y')->nullable()->after('signature_offset_x');
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn(['signature_scale', 'signature_offset_x', 'signature_offset_y']);
        });
    }
};
