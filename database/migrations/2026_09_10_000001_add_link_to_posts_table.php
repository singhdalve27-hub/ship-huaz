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
        if (Schema::hasTable('posts') && !Schema::hasColumn('posts', 'link')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->string('link', 1000)->nullable()->after('excerpt');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('posts') && Schema::hasColumn('posts', 'link')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->dropColumn('link');
            });
        }
    }
};
