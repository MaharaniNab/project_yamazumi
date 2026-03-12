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
            $table->string('station_name', 150)->comment('Nama stasiun');
            $table->integer('priority_order')->default(0)->comment('Urutan prioritas kaizen (1=tertinggi CV, 0=tidak dikaizen)');
            $table->boolean('is_nva_dominant')->default(false)->comment('1 jika NVA% > 20% dari CT_before (dominan NVA)');
            $table->double('nva_pct_before')->nullable()->comment('NVA% dari CT_before (0–1)');

            // CT sebelum dan sesudah kaizen
            $table->double('mean_ct_before')->nullable()->comment('CT mean sebelum kaizen (detik)');
            $table->double('mean_ct_after')->nullable()->comment('CT mean sesudah kaizen — NVA dieliminasi (detik)');
            $table->double('saving_total')->default(0.0)->comment('Total NVA saving di stasiun ini (detik)');

            // Profil variabilitas sesudah kaizen
            $table->double('sigma_after')->nullable()->comment('sigma stasiun sesudah kaizen');
            $table->double('robust_ct_after')->nullable()->comment('Robust CT sesudah (detik)');
            $table->double('cv_after')->nullable()->comment('CV sesudah (%)');

            // Status sesudah kaizen
            $table->enum('status_before', ['Bottleneck', 'At-Risk', 'Balanced', 'Underloaded'])->nullable();
            $table->enum('status_after', ['Bottleneck', 'At-Risk', 'Balanced', 'Underloaded'])->nullable();
            $table->enum('kaizen_result', ['Resolved', 'Still Bottleneck', 'No Action'])->nullable()->comment('Resolved=CT_efektif<Takt, Still Bottleneck=butuh lebih banyak op');

            // Man Power Rebalancing (Phase 3)
            $table->integer('mp_assigned')->default(1)->comment('Operator assigned ke stasiun ini (hasil alokasi Phase 3)');
            $table->double('ct_efektif')->nullable()->comment('CT efektif per operator = CT_after / mp_assigned (detik)');
            $table->double('mp_balance_pct')->nullable()->comment('MP Balance % = CT_efektif / Takt × 100');
            $table->enum('mp_utilized', ['Optimal', 'Baik', 'Underutilized'])->nullable();

            // $table->string('station_name', length: 150)->comment('Nama stasiun');
            // $table->double('mean_ct_after')->nullable()->comment('CT mean sesudah optimasi (detik)');
            // $table->double('robust_ct_after')->nullable()->comment('CT robust sesudah (detik)');
            // $table->double('sigma_after')->nullable()->comment('σ stasiun sesudah');
            // $table->double('cv_after')->nullable()->comment('CV sesudah (%)');

            // $table->enum('status_after', [
            //     'Bottleneck',
            //     'At-Risk',
            //     'Balanced',
            //     'Underloaded'
            // ])->nullable()->comment('Status stasiun sesudah optimasi');

            // $table->enum('risk_after', [
            //     'Low Risk',
            //     'Medium Risk',
            //     'High Risk'
            // ])->nullable()->comment('Kategorisasi risiko sesudah');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulation_stations');
    }
};
