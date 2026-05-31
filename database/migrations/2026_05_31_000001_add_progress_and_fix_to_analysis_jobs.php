<?php
// FILE: database/migrations/xxxx_xx_xx_add_progress_and_fix_to_analysis_jobs.php
// Jalankan: php artisan migrate

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema, DB};

return new class extends Migration
{
    public function up(): void
    {
        // 1. Fix ENUM: tambah 'pending' dan 'queued'
        DB::statement("
            ALTER TABLE analysis_jobs
            MODIFY COLUMN status
            ENUM('pending','queued','processing','completed','failed')
            NOT NULL DEFAULT 'pending'
        ");

        Schema::table('analysis_jobs', function (Blueprint $table) {
            // 2. Tambah kolom progress (jika belum ada)
            if (! Schema::hasColumn('analysis_jobs', 'progress_current')) {
                $table->unsignedInteger('progress_current')->default(0)->after('python_job_id');
            }
            if (! Schema::hasColumn('analysis_jobs', 'progress_total')) {
                $table->unsignedInteger('progress_total')->default(0)->after('progress_current');
            }
            if (! Schema::hasColumn('analysis_jobs', 'progress_message')) {
                $table->string('progress_message', 255)->default('')->after('progress_total');
            }
            // 3. Tambah chart_base64 jika belum ada (Flask v5.0 kirim base64)
            if (! Schema::hasColumn('analysis_jobs', 'chart_base64')) {
                $table->longText('chart_base64')->nullable()->after('chart_path');
            }
        });
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE analysis_jobs
            MODIFY COLUMN status
            ENUM('processing','completed','failed')
            NOT NULL DEFAULT 'processing'
        ");

        Schema::table('analysis_jobs', function (Blueprint $table) {
            $table->dropColumn([
                'progress_current',
                'progress_total',
                'progress_message',
                'chart_base64',
            ]);
        });
    }
};
