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
        // Magdagdag ng specific prices sa venue_packages
        Schema::table('venue_packages', function (Blueprint $table) {
            $table->decimal('price_morning', 10, 2)->nullable()->after('price');
            $table->decimal('price_afternoon', 10, 2)->nullable()->after('price_morning');
            $table->decimal('price_night', 10, 2)->nullable()->after('price_afternoon');
            $table->decimal('price_fullday', 10, 2)->nullable()->after('price_night');
            $table->decimal('price_visitor', 10, 2)->nullable()->after('price_fullday');
        });

        // Magdagdag ng booking_mode sa bookings
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('booking_mode')->default('exclusive')->after('time_slot')->comment('exclusive or visitor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('venue_packages', function (Blueprint $table) {
            $table->dropColumn(['price_morning', 'price_afternoon', 'price_night', 'price_fullday', 'price_visitor']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('booking_mode');
        });
    }
};