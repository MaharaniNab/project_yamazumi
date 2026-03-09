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
        Schema::create('simulation_stations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('simulation_id')->constrained('simulation_results')->cascadeOnDelete();
            $table->string('station_name', length: 150)->comment('Nama stasiun');
            $table->double('mean_ct_after')->nullable()->comment('CT mean sesudah optimasi (detik)');
            $table->double('robust_ct_after')->nullable()->comment('CT robust sesudah (detik)');
            $table->double('sigma_after')->nullable()->comment('σ stasiun sesudah');
            $table->double('cv_after')->nullable()->comment('CV sesudah (%)');

            $table->enum('status_after', [
                'Bottleneck',
                'At-Risk',
                'Balanced',
                'Underloaded'
            ])->nullable()->comment('Status stasiun sesudah optimasi');

            $table->enum('risk_after', [
                'Low Risk',
                'Medium Risk',
                'High Risk'
            ])->nullable()->comment('Kategorisasi risiko sesudah');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulation_stations');
    }
};
