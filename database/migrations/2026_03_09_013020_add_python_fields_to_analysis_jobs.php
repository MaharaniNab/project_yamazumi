<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analysis_jobs', function (Blueprint $table) {
            // Hanya kolom yang BELUM ADA di create_analysis_jobs migration
            $table->string('python_job_id')->nullable()->after('status');
            $table->text('chart_base64')->nullable()->after('chart_path');
        });
    }

    public function down(): void
    {
        Schema::table('analysis_jobs', function (Blueprint $table) {
            $table->dropColumn(['python_job_id', 'chart_base64']);
        });
    }
};
