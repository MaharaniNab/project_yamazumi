<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('temporal_iou_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained('station_results')->cascadeOnDelete();
            $table->foreignId('calculated_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('calculated_at')->nullable();
            $table->string('activity', 100)->comment('Nama aktivitas yang dievaluasi');
            $table->integer('n_samples_pred')->default(0)->comment('Jumlah segmen sistem CV');
            $table->integer('n_samples_gt')->default(0)->comment('Jumlah segmen ground truth');
            $table->double('total_intersection')->default(0.0)->comment('Σ durasi irisan (detik)');
            $table->double('total_union')->default(0.0)->comment('Σ durasi gabungan (detik)');
            $table->double('avg_iou')->default(0.0)->comment('Skor IoU akhir [0.0–1.0]');
            $table->enum('keterangan', ['Baik', 'Cukup', 'Perlu Perbaikan'])->default('Baik')->comment('Baik / Cukup / Perlu Perbaikan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temporal_iou_results');
    }
};
