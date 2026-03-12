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
        Schema::create('simulation_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('analysis_jobs')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->integer('mp_aktual_input')->default(0)->comment('MP aktual yang diinput pengguna saat analisa');
            $table->double('nva_elimination_pct')->default(1.00)->comment('Persen eliminasi NVA (1.00 = 100%)');

            // Metrik Sebelum Kaizen
            $table->double('le_before')->nullable()->comment('Line Efficiency sebelum (%)');
            $table->double('bd_before')->nullable()->comment('Balance Delay sebelum (%)');
            $table->double('si_before')->nullable()->comment('Smoothness Index sebelum');
            $table->double('neck_before')->nullable()->comment('Neck Time mean sebelum (detik)');
            $table->double('neck_robust_before')->nullable()->comment('Neck Time robust sebelum (detik)');

            // Metrik Sesudah Kaizen
            $table->double('le_after')->nullable()->comment('Line Efficiency sesudah (%)');
            $table->double('bd_after')->nullable()->comment('Balance Delay sesudah (%)');
            $table->double('si_after')->nullable()->comment('Smoothness Index sesudah');
            $table->double('neck_after')->nullable()->comment('Neck Time mean sesudah (detik)');
            $table->double('neck_robust_after')->nullable()->comment('Neck Time robust sesudah (detik)');

            // Ringkasan Aksi Kaizen
            $table->double('total_saving_nva')->default(0.0)->comment('Total penghematan NVA+N-NVA (detik/siklus)');
            $table->integer('n_kaizen_actions')->default(0)->comment('Jumlah aksi kaizen (NVA elim + N-NVA reduksi)');
            $table->string('chart_path', 200)->nullable()->comment('Path Yamazumi Chart sesudah kaizen');

            // Man Power Balancing (Phase 3)
            $table->double('op_teoritis_after')->nullable()->comment('Operator teoritis = Σ CT_after / Takt');
            $table->integer('total_mp')->nullable()->comment('Total operator aktual = Σ ceil(CT_i/Takt)');
            $table->double('overall_mp_balance')->nullable()->comment('Overall MP Balance % = Σ CT_after / (Σ MP × Takt) × 100');
            $table->timestamps();
            // $table->integer('mp_aktual_input')->default(0)->comment('MP aktual yang diinput pengguna saat analisa');
            // $table->double('nva_elimination_pct')->default(1.00)->comment('Persen eliminasi NVA (1.00 = 100%)');

            // $table->double('lambda_risk')->default(0.5)->comment('Bobot risiko λ dalam fungsi objektif Z');
            // $table->double('z_score_used')->default(2.0)->comment('Z untuk perhitungan Robust CT = μ + Z×σ');

            // $table->double('z_before')->nullable()->comment('Nilai Z sebelum optimasi');
            // $table->double('z_after')->nullable()->comment('Nilai Z sesudah optimasi');
            // $table->double('z_improvement')->nullable()->comment('Perbaikan Z dalam persen [(Z_before−Z_after)/Z_before×100]');

            // $table->double('le_before')->nullable()->comment('Line Efficiency sebelum (%)');
            // $table->double('bd_before')->nullable()->comment('Balance Delay sebelum (%)');
            // $table->double('si_before')->nullable()->comment('Smoothness Index sebelum');
            // $table->double('neck_before')->nullable()->comment('Neck Time mean sebelum (detik)');
            // $table->double('neck_robust_before')->nullable()->comment('Neck Time robust μ+2σ sebelum (detik)');

            // $table->double('le_after')->nullable()->comment('Line Efficiency sesudah (%)');
            // $table->double('bd_after')->nullable()->comment('Balance Delay sesudah (%)');
            // $table->double('si_after')->nullable()->comment('Smoothness Index sesudah');
            // $table->double('neck_after')->nullable()->comment('Neck Time mean sesudah (detik)');
            // $table->double('neck_robust_after')->nullable()->comment('Neck Time robust μ+2σ sesudah (detik)');

            // $table->double('total_saving_nva')->default(0.0)->comment('Total penghematan NVA dalam detik/siklus');
            // $table->integer('n_kaizen_actions')->default(0)->comment('Jumlah aksi reduksi NVA (Fase 2)');
            // $table->integer('n_redist_actions')->default(0)->comment('Jumlah aksi redistribusi beban (Fase 4)');
            // $table->string('chart_path', 200)->nullable()->comment('Path Yamazumi Chart sesudah optimasi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('simulation_results');
    }
};
