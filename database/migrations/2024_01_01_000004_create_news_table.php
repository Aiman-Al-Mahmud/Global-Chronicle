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
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->string('title')->index();
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->enum('language', ['en', 'ar', 'fr', 'es'])->default('en')->index();
            $table->string('provider')->nullable()->index();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft')->index();
            $table->unsignedBigInteger('views_count')->default(0)->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('allow_comments')->default(true);
            
            // Relationships
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->foreignId('featured_image_id')->nullable()->constrained('media')->onDelete('set null');
            
            // SEO and metadata
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable(); // Store as JSON array
            
            // RSS and external source info
            $table->text('rss_url')->nullable();
            $table->string('external_id')->nullable()->index(); // For RSS items
            $table->timestamp('external_published_at')->nullable();
            
            // Publishing timestamps
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            // Comprehensive indexes for better performance
            $table->index(['status', 'published_at', 'is_featured']);
            $table->index(['language', 'status', 'published_at']);
            $table->index(['category_id', 'status', 'published_at']);
            $table->index(['author_id', 'status']);
            $table->index(['provider', 'external_id']);
            // views_count index already exists from column definition
            
            // Full-text search index for title and content
            $table->fullText(['title', 'excerpt', 'content']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};