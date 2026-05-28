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
        Schema::table('error_logs', function (Blueprint $table) {
            $table->timestamp('resolved_at')->nullable()->after('last_seen_at');
            $table->index('resolved_at');
        });
    }

    public function down(): void
    {
        Schema::table('error_logs', function (Blueprint $table) {
            $table->dropIndex(['resolved_at']);
            $table->dropColumn('resolved_at');
        });
    }
};
