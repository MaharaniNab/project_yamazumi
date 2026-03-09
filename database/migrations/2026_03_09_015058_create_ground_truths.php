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
        Schema::create('ground_truths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained('station_results')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('raw_id')->constrained('raw_segments')->cascadeOnDelete();
            $table->timestamp('input_at')->nullable();
            $table->double('start_time')->comment('Waktu mulai (detik dari awal video)');
            $table->double('end_time')->comment('Waktu akhir (detik)');
            $table->double('duration')->comment('end_time - start_time (detik)');
            $table->string('catatan', 200)->nullable()->comment('Catatan opsional peneliti');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ground_truths');
    }
};
