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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['image', 'video', 'audio', 'document'])->index();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('original_name')->nullable();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('disk')->default('public');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable(); // in bytes
            $table->json('dimensions')->nullable(); // width, height for images/videos
            $table->string('alt_text')->nullable(); // for accessibility
            $table->boolean('is_visible')->default(true)->index();
            $table->timestamps();

            // Add indexes for better performance
            $table->index(['type', 'is_visible']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};