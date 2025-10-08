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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->index();
            $table->json('value'); // Store complex values as JSON
            $table->string('type')->default('string'); // string, number, boolean, array, object
            $table->text('description')->nullable();
            $table->string('group')->default('general')->index(); // Group settings
            $table->boolean('is_public')->default(false)->index(); // Can be accessed by frontend
            $table->boolean('is_autoload')->default(false)->index(); // Load automatically
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Add indexes for better performance
            $table->index(['group', 'is_autoload']);
            $table->index(['is_public', 'is_autoload']);
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};