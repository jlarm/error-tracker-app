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
            $table->string('environment')->nullable()->after('url');
            $table->string('level', 32)->default('error')->after('environment');
            $table->string('release')->nullable()->after('level');
            $table->json('breadcrumbs')->nullable()->after('request_payload');
            $table->json('tags')->nullable()->after('breadcrumbs');
            $table->json('context')->nullable()->after('tags');

            $table->index('environment');
            $table->index('level');
        });
    }

    public function down(): void
    {
        Schema::table('error_logs', function (Blueprint $table) {
            $table->dropIndex(['environment']);
            $table->dropIndex(['level']);
            $table->dropColumn([
                'environment',
                'level',
                'release',
                'breadcrumbs',
                'tags',
                'context',
            ]);
        });
    }
};
