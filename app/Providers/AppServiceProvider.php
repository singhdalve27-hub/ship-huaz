<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
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

        // Clear stale cached routes if present on shared hosting
        try {
            $cachedRoutes = base_path('bootstrap/cache/routes-v7.php');
            if (file_exists($cachedRoutes)) {
                @unlink($cachedRoutes);
            }
        } catch (\Throwable $e) {
            // Graceful
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

            $this->syncChatBotData();
        } catch (\Throwable $e) {
            // Continue gracefully
        }
    }

    /**
     * Updates chatbot nodes with official venue location and contact hotlines.
     */
    protected function syncChatBotData(): void
    {
        try {
            if (!Schema::hasTable('chat_bot_nodes')) {
                return;
            }

            // Update dummy phones in messages
            $nodes = DB::table('chat_bot_nodes')
                ->where('message', 'LIKE', '%912 345 6789%')
                ->get();

            foreach ($nodes as $node) {
                $newMessage = str_replace(
                    ['+63 (0) 912 345 6789', '+63 912 345 6789'],
                    '0920 713 9299 / 0930 903 6834',
                    $node->message
                );
                DB::table('chat_bot_nodes')->where('id', $node->id)->update(['message' => $newMessage]);
            }

            // Update specific location & contact nodes
            DB::table('chat_bot_nodes')
                ->where('node_key', 'Location & Hours')
                ->update([
                    'message' => "Butal Ship Hauz\nCapawan, Talibon, Bohol, Philippines\nOpen daily for Ocular/Visitors: 8:00 AM to 6:00 PM\nReservations Hotline: 0920 713 9299 / 0930 903 6834"
                ]);

            DB::table('chat_bot_nodes')
                ->where('node_key', 'Directions')
                ->update([
                    'message' => "Located in Sitio Capawan, Poblacion, Talibon, Bohol. From Tagbilaran City or Tubigon/Ubay Port, ride a bus or van bound for Talibon (approx. 2 hours). Ask the driver to drop you off near Butal Ship Hauz in Capawan!"
                ]);

            DB::table('chat_bot_nodes')
                ->where('node_key', 'Contact Us')
                ->update([
                    'message' => "Our crew is ready to assist you!\nHotlines: 0920 713 9299 / 0930 903 6834\nEmail: reservations@butalshiphauz.com.ph\nAddress: Capawan, Talibon, Bohol"
                ]);
        } catch (\Throwable $e) {
            // Continue gracefully
        }
    }
}