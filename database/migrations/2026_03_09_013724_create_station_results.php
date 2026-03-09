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
        Schema::create('station_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('analysis_jobs')->cascadeOnDelete();
            $table->string('station_name', 150)->comment('Nama stasiun kerja / operator');
            $table->integer('station_order')->default(0)->comment('Urutan posisi dalam lini (0-based)');

            // Metrik Siklus
            $table->double('mean_ct')->comment('μ Cycle Time per siklus (detik)');
            $table->double('station_sigma')->default(0.0)->comment('σ stasiun = √(Σ σ²_elemen)');
            $table->double('robust_ct')->nullable()->comment('Robust CT = μ + Z×σ (default Z=2)');
            $table->double('total_variance')->default(0.0)->comment('Σ σ²_elemen (sebelum di-sqrt)');
            $table->double('cv_persen')->default(0.0)->comment('CV = σ/μ × 100% — indikator variabilitas');
            $table->double('idle_time')->default(0.0)->comment('Takt Time - mean_ct jika > 0');
            $table->double('overflow_robust')->default(0.0)->comment('robust_ct - Takt Time jika > 0');

            // Kategorisasi Risiko
            $table->enum('risk_category', ['Low Risk', 'Medium Risk', 'High Risk'])
                ->default('Low Risk')
                ->comment('Kategori risiko variabilitas berdasarkan CV');

            // Status Stasiun
            $table->enum('status_station', ['Bottleneck', 'At-Risk', 'Balanced', 'Underloaded'])
                ->default('Balanced')
                ->comment('Status stasiun vs Takt Time');

            // Performa Produksi
            $table->integer('n_cycles')->default(1)->comment('Jumlah siklus terdeteksi dalam video');
            $table->double('output_jam')->nullable()->comment('3600 / mean_ct (pcs/jam)');
            $table->integer('output_hari')->nullable()->comment('25920 / mean_ct (pcs/hari)');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('station_results');
    }
};
