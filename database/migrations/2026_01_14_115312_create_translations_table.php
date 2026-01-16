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
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('locale'); // Redundant single index (covered by compound)
            $table->string('key')->index(); // Key like 'auth.login.title'
            $table->text('content');
            $table->timestamps();

            $table->index(['locale', 'key']);
            $table->index(['locale', 'updated_at']); // For efficient Last-Modified check
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
