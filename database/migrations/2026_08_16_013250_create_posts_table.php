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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('category')->nullable(); // Hal: "Destination", "Events"
            $table->string('title');
            $table->text('excerpt')->nullable(); // Ang maikling caption
            $table->string('image')->nullable(); // Dito i-se-save ang link ng picture
            $table->date('post_date')->nullable(); // Petsa ng post (Hal: June 2025)
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
