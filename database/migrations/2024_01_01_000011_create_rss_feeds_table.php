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
        Schema::create('rss_feeds', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url')->unique();
            $table->text('description')->nullable();
            $table->string('website_url')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->enum('status', ['active', 'inactive', 'error'])->default('active')->index();
            $table->enum('language', ['en', 'ar', 'fr', 'es'])->default('en')->index();
            
            // Feed processing settings
            $table->integer('fetch_frequency')->default(60); // minutes
            $table->integer('max_items')->default(10); // items per fetch
            $table->boolean('auto_publish')->default(false);
            $table->json('parsing_rules')->nullable(); // Custom parsing rules
            
            // Feed metadata
            $table->timestamp('last_fetched_at')->nullable();
            $table->timestamp('last_successful_fetch_at')->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedInteger('total_items_fetched')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            
            $table->timestamps();
            $table->softDeletes();

            // Add indexes for better performance
            $table->index(['status', 'last_fetched_at']);
            $table->index(['category_id', 'status']);
            $table->index(['language', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rss_feeds');
    }
};