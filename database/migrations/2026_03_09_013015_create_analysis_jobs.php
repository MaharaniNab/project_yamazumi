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
        Schema::create('analysis_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['processing', 'completed', 'failed'])->default('processing')->comment('Status pemrosesan job');
            $table->string('error_msg')->nullable()->comment('Pesan error jika status=failed');

            // Metadata Lini
            $table->string('line_name', 100)->comment('Nama lini produksi, e.g. Line Jas B');
            $table->string('part_name', 150)->nullable()->comment('Nama bagian/departemen');
            $table->string('style', 100)->nullable()->comment('Style produk');
            $table->string('brand', 100)->nullable()->comment('Nama brand/klien');
            $table->integer('output_harian')->comment('Target output per hari (pcs)');
            $table->double('jam_kerja_detik')->default(25920.0)->comment('Jam kerja efektif dalam detik (default: 7.2 jam)');
            $table->double('takt_time')->nullable()->comment('Takt Time = jam_kerja_detik / output_harian (detik/pcs)');

            // Metrik Deterministik
            $table->double('total_cycle_time')->nullable()->comment('Σ CT semua stasiun (detik)');
            $table->double('neck_time_mean')->nullable()->comment('CT stasiun bottleneck berbasis mean (detik)');
            $table->double('line_efficiency')->nullable()->comment('LE% = Σ CT / (n × CT_maks) × 100');
            $table->double('balance_delay')->nullable()->comment('BD% = 100 - LE%');
            $table->double('smoothness_index')->nullable()->comment('SI = √[Σ(CT_maks − CT_i)²]');

            // Metrik Robust
            $table->double('neck_time_robust')->nullable()->comment('CT bottleneck robust = μ + 2σ (detik)');
            $table->double('line_risk_score')->nullable()->comment('Line Risk = λ × Σσ²_stasiun');

            // Output & Operator
            $table->integer('line_output_hari')->nullable()->comment('Output aktual/hari = jam_kerja_detik / neck_time_mean');
            $table->double('op_teoritis')->nullable()->comment('Operator teoritis = Σ CT_i / Takt Time');
            $table->integer('n_stations')->nullable()->comment('Jumlah stasiun (operator) dalam lini');
            $table->string('chart_path', 200)->nullable()->comment('Relative path file Yamazumi Chart PNG');
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('analysis_jobs');
    }
};
