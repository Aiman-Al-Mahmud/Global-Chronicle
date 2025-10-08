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
        Schema::create('likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_id')->constrained('news')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade'); // For registered users
            $table->ipAddress('visitor_ip')->nullable(); // For anonymous users
            $table->string('user_agent')->nullable();
            $table->enum('type', ['like', 'dislike'])->default('like')->index();
            $table->timestamps();

            // Ensure unique likes per user/IP per news article
            $table->unique(['news_id', 'user_id']);
            $table->unique(['news_id', 'visitor_ip']);
            
            // Add indexes for better performance
            $table->index(['news_id', 'type']);
            $table->index('visitor_ip');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('likes');
    }
};