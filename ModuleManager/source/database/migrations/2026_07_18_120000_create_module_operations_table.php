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
        Schema::create('module_operations', function (Blueprint $table) {
            $table->id();
            $table->string('module_key', 60)->index();
            $table->string('action', 20);
            $table->string('status', 20)->default('pending')->index();
            $table->string('from_version')->nullable();
            $table->string('to_version')->nullable();
            $table->longText('output')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('causer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_operations');
    }
};
