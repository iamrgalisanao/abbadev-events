<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('type')->default('Seminar')->after('title');       // Seminar | Workshop | Webinar
            $table->string('mode')->default('Online')->after('type');         // Online | In-person
            $table->json('audience')->nullable()->after('location');          // ["Students","SME owners"]
            $table->string('duration')->nullable()->after('starts_at');
            $table->string('level')->nullable()->after('duration');
            $table->text('blurb')->nullable()->after('level');
            $table->string('price_label')->nullable()->after('expected_amount'); // "Free" | "₱399"
            $table->boolean('is_featured')->default(false)->after('is_active');
            $table->unsignedInteger('sort_order')->default(0)->after('is_featured');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'type', 'mode', 'audience', 'duration', 'level',
                'blurb', 'price_label', 'is_featured', 'sort_order',
            ]);
        });
    }
};
