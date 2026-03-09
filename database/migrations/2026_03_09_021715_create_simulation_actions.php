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
        Schema::create('simulation_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('simulation_id')->constrained('simulation_results')->cascadeOnDelete();
            $table->enum('action_type', ['kaizen', 'redistribution'])->comment('Tipe aksi algoritma');
            $table->string('station_from', 150)->comment('Stasiun asal elemen');
            $table->string('station_to', 150)->nullable()->comment('Stasiun tujuan (hanya redistribusi)');
            $table->string('elemen_kerja', 100)->comment('Nama elemen yang dimodifikasi');

            $table->double('durasi_before')->comment('Durasi sebelum aksi (detik)');
            $table->double('durasi_after')->default(0.0)->comment('Durasi sesudah aksi (0 jika dipindah)');
            $table->double('saving')->default(0.0)->comment('Penghematan waktu (detik/siklus)');

            $table->string('metode', 200)->nullable()->comment('Deskripsi metode improvement');

            $table->enum('risk_stasiun', ['Low Risk', 'Medium Risk', 'High Risk'])
                ->nullable()
                ->comment('Risk level stasiun asal saat aksi diambil');

        });
    }


    public function down(): void
    {
        Schema::dropIfExists('simulation_actions');
    }
};
