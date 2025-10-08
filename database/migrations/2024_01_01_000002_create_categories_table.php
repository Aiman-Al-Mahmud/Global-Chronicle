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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->string('image')->nullable();
            $table->string('icon')->nullable();
            $table->string('color', 7)->nullable(); // For hex colors
            $table->integer('sort_order')->default(0)->index();
            $table->enum('language', ['en', 'ar', 'fr', 'es'])->default('en')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->json('meta')->nullable(); // For SEO and other metadata
            $table->timestamps();
            $table->softDeletes();

            // Add indexes for better performance
            $table->index(['is_active', 'language', 'sort_order']);
            $table->index(['parent_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};