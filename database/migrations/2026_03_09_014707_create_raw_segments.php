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
        Schema::create('raw_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained('station_results')->cascadeOnDelete();
            $table->string('activity', 100)->comment('Nama aktivitas yang terdeteksi CV');
            $table->double('start_time')->comment('Waktu mulai segmen dari awal video (detik)');
            $table->double('end_time')->comment('Waktu akhir segmen (detik)');
            $table->double('duration')->comment('end_time - start_time (detik)');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_segments');
    }
};
