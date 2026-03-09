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
        Schema::create('work_elements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained('station_results')->cascadeOnDelete();
            $table->string('elemen_kerja', 100)->comment('Nama elemen kerja');

            // Cycle-normalized duration
            $table->double('durasi_detik')->comment('Durasi per siklus = total_durasi / n_cycles');
            $table->double('std_dev')->default(0.0)->comment('Standar deviasi durasi (detik)');
            $table->double('cv_persen')->default(0.0)->comment('CV = std_dev / mean × 100%');
            $table->integer('frekuensi')->default(1)->comment('Jumlah kemunculan dalam video');
            $table->double('total_durasi')->default(0.0)->comment('Σ durasi semua kemunculan (detik)');
            $table->double('mean_per_kejadian')->default(0.0)->comment('Rata-rata per kemunculan (bukan per siklus)');

            // Klasifikasi VA
            $table->enum('kategori_va', ['VA', 'N-NVA', 'NVA'])->default('NVA')->comment(
                    'Klasifikasi VA (Bab 2.8 tesis): VA=Value-Added, N-NVA=Necessary Non-Value-Added, NVA=Non-Value-Added'
                );
            $table->string('warna_hex', 10)->default('#4575b4')->comment('Warna hex untuk Yamazumi Chart');

        });
    }


    public function down(): void
    {
        Schema::dropIfExists('work_elements');
    }
};
