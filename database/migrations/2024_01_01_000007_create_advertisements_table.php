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
        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('rate', 10, 2)->nullable(); // Cost per impression/click
            $table->enum('rate_type', ['cpm', 'cpc', 'fixed'])->default('fixed'); // Cost model
            
            // Media relationship
            $table->foreignId('media_id')->nullable()->constrained('media')->onDelete('set null');
            
            // Advertisement content
            $table->text('html_content')->nullable(); // For custom HTML ads
            $table->string('click_url')->nullable();
            $table->string('tracking_code')->nullable();
            
            // Positioning and display
            $table->enum('position', [
                'header', 'sidebar', 'footer', 'content_top', 'content_middle', 
                'content_bottom', 'popup', 'banner'
            ])->index();
            $table->integer('sort_order')->default(0);
            $table->json('display_rules')->nullable(); // Rules for when to show
            
            // Status and scheduling
            $table->enum('status', ['active', 'inactive', 'expired'])->default('active')->index();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            
            // Analytics
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->decimal('click_rate', 5, 2)->default(0); // CTR percentage
            
            $table->timestamps();
            $table->softDeletes();

            // Add indexes for better performance
            $table->index(['status', 'position', 'starts_at', 'expires_at']);
            $table->index(['starts_at', 'expires_at']);
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advertisements');
    }
};