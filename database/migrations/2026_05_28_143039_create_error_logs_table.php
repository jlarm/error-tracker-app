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
        Schema::create('error_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('exception_class');
            $table->text('message');
            $table->string('file');
            $table->unsignedInteger('line');
            $table->string('url')->nullable();
            $table->json('stack_trace');
            $table->json('request_payload')->nullable();
            $table->string('fingerprint')->index();
            $table->unsignedInteger('occurrences')->default(1);
            $table->timestamp('last_seen_at');
            $table->timestamps();

            $table->unique(['project_id', 'fingerprint']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('error_logs');
    }
};
