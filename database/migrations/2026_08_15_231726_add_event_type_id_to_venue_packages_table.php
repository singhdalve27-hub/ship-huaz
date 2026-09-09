<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venue_packages', function (Blueprint $table) {
            // Gumawa ng column na event_type_id (nullable muna para hindi mag-error ang mga existing data mo)
            $table->foreignId('event_type_id')->nullable()->after('id')->constrained('event_types')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('venue_packages', function (Blueprint $table) {
            $table->dropForeign(['event_type_id']);
            $table->dropColumn('event_type_id');
        });
    }
};