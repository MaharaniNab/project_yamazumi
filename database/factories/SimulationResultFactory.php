<?php

namespace Database\Factories;

use App\Models\AnalysisJob;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SimulationResult>
 */
class SimulationResultFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    // public function definition(): array
    // {
    //     $job = AnalysisJob::inRandomOrder()->first();
    //     $user = User::inRandomOrder()->first();

    //     $lambda = 0.5;
    //     $zScore = 2.0;

    //     // ambil baseline dari job
    //     $leBefore = $job->line_efficiency ?? $this->faker->randomFloat(2, 60, 80);
    //     $bdBefore = 100 - $leBefore;
    //     $siBefore = $job->smoothness_index ?? $this->faker->randomFloat(2, 20, 60);
    //     $neckBefore = $job->neck_time_mean ?? $this->faker->randomFloat(2, 40, 80);

    //     // estimasi sigma
    //     $sigma = $this->faker->randomFloat(2, 2, 8);

    //     $neckRobustBefore = $neckBefore + ($zScore * $sigma);

    //     // efek optimasi
    //     $leAfter = min(95, $leBefore + $this->faker->randomFloat(2, 3, 10));
    //     $bdAfter = 100 - $leAfter;

    //     $siAfter = max(1, $siBefore - $this->faker->randomFloat(2, 5, 20));

    //     $neckAfter = $neckBefore - $this->faker->randomFloat(2, 2, 10);
    //     $neckRobustAfter = $neckAfter + ($zScore * $sigma);

    //     // objective function sederhana
    //     $zBefore = ($lambda * $siBefore) + $neckRobustBefore;
    //     $zAfter = ($lambda * $siAfter) + $neckRobustAfter;

    //     $zImprovement = (($zBefore - $zAfter) / $zBefore) * 100;

    //     // aksi kaizen
    //     $nKaizen = $this->faker->numberBetween(1, 5);
    //     $nRedist = $this->faker->numberBetween(1, 4);

    //     $savingNva = $this->faker->randomFloat(2, 1, 10);

    //     return [

    //         'job_id' => $job->id,
    //         'user_id' => $user->id,

    //         'lambda_risk' => $lambda,
    //         'z_score_used' => $zScore,

    //         'z_before' => $zBefore,
    //         'z_after' => $zAfter,
    //         'z_improvement' => $zImprovement,

    //         'le_before' => $leBefore,
    //         'bd_before' => $bdBefore,
    //         'si_before' => $siBefore,
    //         'neck_before' => $neckBefore,
    //         'neck_robust_before' => $neckRobustBefore,

    //         'le_after' => $leAfter,
    //         'bd_after' => $bdAfter,
    //         'si_after' => $siAfter,
    //         'neck_after' => $neckAfter,
    //         'neck_robust_after' => $neckRobustAfter,

    //         'total_saving_nva' => $savingNva,
    //         'n_kaizen_actions' => $nKaizen,
    //         'n_redist_actions' => $nRedist,

    //         'chart_path' => $this->faker->filePath(),
    //     ];
    // }

    public function definition(): array
    {
        // Ambil job terkait (relasi ke analysis_jobs)
        $job = AnalysisJob::inRandomOrder()->first();
        $user = User::inRandomOrder()->first();

        // MP aktual input
        $mpAktualInput = $this->faker->numberBetween(10, 50);

        // Persen eliminasi NVA (default 100%)
        $nvaEliminationPct = 1.00;

        // --- Metrik sebelum Kaizen ---
        $leBefore = $job->line_efficiency ?? $this->faker->randomFloat(2, 60, 95);
        $bdBefore = 100 - $leBefore;
        $siBefore = $job->smoothness_index ?? $this->faker->randomFloat(2, 10, 200);
        $neckBefore = $job->neck_time_mean ?? $this->faker->randomFloat(2, 200, 800);
        $neckRobustBefore = $job->neck_time_robust ?? ($neckBefore + $this->faker->randomFloat(2, 20, 100));

        // --- Simulasi Kaizen: saving NVA ---
        $savingNVA = $neckBefore * $nvaEliminationPct * 0.2; // misal 20% saving dari bottleneck
        $neckAfter = max(1, $neckBefore - $savingNVA);
        $neckRobustAfter = max(1, $neckRobustBefore - $savingNVA);

        // Efisiensi sesudah Kaizen (naik karena bottleneck berkurang)
        $leAfter = min(100, $leBefore + $this->faker->randomFloat(2, 2, 10));
        $bdAfter = 100 - $leAfter;
        $siAfter = max(0, $siBefore - $this->faker->randomFloat(2, 5, 50));

        // Ringkasan aksi Kaizen
        $nKaizenActions = $this->faker->numberBetween(1, 5);

        // --- Man Power Balancing (Phase 3) ---
        $opTeoritisAfter = $job->total_cycle_time / $job->takt_time;
        $totalMp = $mpAktualInput;
        $overallMpBalance = ($job->total_cycle_time / ($totalMp * $job->takt_time)) * 100;

        return [
            'job_id' => $job->id,
            'user_id' => $user->id,
            'mp_aktual_input' => $mpAktualInput,
            'nva_elimination_pct' => $nvaEliminationPct,

            // Metrik Sebelum Kaizen
            'le_before' => $leBefore,
            'bd_before' => $bdBefore,
            'si_before' => $siBefore,
            'neck_before' => $neckBefore,
            'neck_robust_before' => $neckRobustBefore,

            // Metrik Sesudah Kaizen
            'le_after' => $leAfter,
            'bd_after' => $bdAfter,
            'si_after' => $siAfter,
            'neck_after' => $neckAfter,
            'neck_robust_after' => $neckRobustAfter,

            // Ringkasan Aksi Kaizen
            'total_saving_nva' => $savingNVA,
            'n_kaizen_actions' => $nKaizenActions,
            'chart_path' => $this->faker->optional()->filePath(),

            // Man Power Balancing
            'op_teoritis_after' => $opTeoritisAfter,
            'total_mp' => $totalMp,
            'overall_mp_balance' => $overallMpBalance,

            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

}
