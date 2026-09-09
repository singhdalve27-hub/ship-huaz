<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_bot_nodes', function (Blueprint $table) {
            // Idinagdag ang dynamic_content column
            $table->string('dynamic_content')->nullable()->after('message')->comment('null, event_types, or venue_packages');
        });
    }

    public function down(): void
    {
        Schema::table('chat_bot_nodes', function (Blueprint $table) {
            $table->dropColumn('dynamic_content');
        });
    }
};