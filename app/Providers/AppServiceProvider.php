<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        if (env('APP_ENV') !== 'production') {
            URL::forceScheme('https');
        }

        $this->ensureDatabaseColumnsExist();
    }

    /**
     * Auto-heals the database schema on shared hosting without requiring manual migrations.
     */
    protected function ensureDatabaseColumnsExist(): void
    {
        try {
            // 1. Ensure bookings table has booking_mode
            if (Schema::hasTable('bookings') && !Schema::hasColumn('bookings', 'booking_mode')) {
                Schema::table('bookings', function (Blueprint $table) {
                    $table->string('booking_mode')->default('exclusive')->after('time_slot');
                });
            }

            // 2. Ensure venue_packages table has visitor and shift pricing columns
            if (Schema::hasTable('venue_packages')) {
                Schema::table('venue_packages', function (Blueprint $table) {
                    if (!Schema::hasColumn('venue_packages', 'price_night')) {
                        $table->decimal('price_night', 10, 2)->nullable()->after('price');
                    }
                    if (!Schema::hasColumn('venue_packages', 'price_visitor')) {
                        $table->decimal('price_visitor', 10, 2)->nullable()->after('price_night');
                    }
                    if (!Schema::hasColumn('venue_packages', 'price_morning')) {
                        $table->decimal('price_morning', 10, 2)->nullable()->after('price');
                    }
                    if (!Schema::hasColumn('venue_packages', 'price_afternoon')) {
                        $table->decimal('price_afternoon', 10, 2)->nullable()->after('price_morning');
                    }
                    if (!Schema::hasColumn('venue_packages', 'price_fullday')) {
                        $table->decimal('price_fullday', 10, 2)->nullable()->after('price_night');
                    }
                });
            }

            // 3. Ensure posts table has destination link
            if (Schema::hasTable('posts') && !Schema::hasColumn('posts', 'link')) {
                Schema::table('posts', function (Blueprint $table) {
                    $table->string('link', 1000)->nullable()->after('excerpt');
                });
            }
        } catch (\Throwable $e) {
            // Continue gracefully
        }
    }
}