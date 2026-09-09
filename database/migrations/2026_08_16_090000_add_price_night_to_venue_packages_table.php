<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('venue_packages') && !Schema::hasColumn('venue_packages', 'price_night')) {
            Schema::table('venue_packages', function (Blueprint $table) {
                $table->decimal('price_night', 10, 2)->nullable()->after('price_afternoon');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('venue_packages') && Schema::hasColumn('venue_packages', 'price_night')) {
            Schema::table('venue_packages', function (Blueprint $table) {
                $table->dropColumn('price_night');
            });
        }
    }
};
