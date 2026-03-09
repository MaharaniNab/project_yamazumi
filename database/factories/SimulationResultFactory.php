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
    //     return [
    //         'job_id' => AnalysisJob::inRandomOrder()->first()->id,
    //         'user_id' => User::inRandomOrder()->first()->id,
    //         'created_at' => now(),
    //         'lambda_risk' => $this->faker->randomFloat(2, 0, max: 5),
    //         'z_score_used' => $this->faker->randomFloat(2, 0, 5),
    //         'z_before' => $this->faker->randomFloat(2, 0, 5),
    //         'z_after' => $this->faker->randomFloat(2, min: 0, max: 5),
    //         'z_improvement' => $this->faker->randomFloat(2, 0, 5),
    //         'le_before' => $this->faker->randomFloat(2, 0, 5),
    //         'bd_before' => $this->faker->randomFloat(2, 0, 5),
    //         'si_before' => $this->faker->randomFloat(2, 0, 5),
    //         'neck_before' => $this->faker->randomFloat(2, 0, 5),
    //         'neck_robust_before' => $this->faker->randomFloat(2, 0, 5),

    //         'le_after' => $this->faker->randomFloat(2, 0, 5),
    //         'bd_after' => $this->faker->randomFloat(2, 0, 5),
    //         'si_after' => $this->faker->randomFloat(2, 0, 5),
    //         'neck_after' => $this->faker->randomFloat(2, 0, 5),
    //         'neck_robust_after' => $this->faker->randomFloat(2, 0, 5),

    //         'total_saving_nva'=> $this->faker->randomFloat(2, 0, 5),
    //         'n_kaizen_actions' => $this->faker->numberBetween(0,10),
    //         'n_redist_actions' => $this->faker->numberBetween(0,10),
    //         'chart_path'=> $this->faker->sentence()
    //     ];
    // }

    public function definition(): array
    {
        $job = AnalysisJob::inRandomOrder()->first();
        $user = User::inRandomOrder()->first();

        $lambda = 0.5;
        $zScore = 2.0;

        // ambil baseline dari job
        $leBefore = $job->line_efficiency ?? $this->faker->randomFloat(2, 60, 80);
        $bdBefore = 100 - $leBefore;
        $siBefore = $job->smoothness_index ?? $this->faker->randomFloat(2, 20, 60);
        $neckBefore = $job->neck_time_mean ?? $this->faker->randomFloat(2, 40, 80);

        // estimasi sigma
        $sigma = $this->faker->randomFloat(2, 2, 8);

        $neckRobustBefore = $neckBefore + ($zScore * $sigma);

        // efek optimasi
        $leAfter = min(95, $leBefore + $this->faker->randomFloat(2, 3, 10));
        $bdAfter = 100 - $leAfter;

        $siAfter = max(1, $siBefore - $this->faker->randomFloat(2, 5, 20));

        $neckAfter = $neckBefore - $this->faker->randomFloat(2, 2, 10);
        $neckRobustAfter = $neckAfter + ($zScore * $sigma);

        // objective function sederhana
        $zBefore = ($lambda * $siBefore) + $neckRobustBefore;
        $zAfter = ($lambda * $siAfter) + $neckRobustAfter;

        $zImprovement = (($zBefore - $zAfter) / $zBefore) * 100;

        // aksi kaizen
        $nKaizen = $this->faker->numberBetween(1, 5);
        $nRedist = $this->faker->numberBetween(1, 4);

        $savingNva = $this->faker->randomFloat(2, 1, 10);

        return [

            'job_id' => $job->id,
            'user_id' => $user->id,

            'lambda_risk' => $lambda,
            'z_score_used' => $zScore,

            'z_before' => $zBefore,
            'z_after' => $zAfter,
            'z_improvement' => $zImprovement,

            'le_before' => $leBefore,
            'bd_before' => $bdBefore,
            'si_before' => $siBefore,
            'neck_before' => $neckBefore,
            'neck_robust_before' => $neckRobustBefore,

            'le_after' => $leAfter,
            'bd_after' => $bdAfter,
            'si_after' => $siAfter,
            'neck_after' => $neckAfter,
            'neck_robust_after' => $neckRobustAfter,

            'total_saving_nva' => $savingNva,
            'n_kaizen_actions' => $nKaizen,
            'n_redist_actions' => $nRedist,

            'chart_path' => $this->faker->filePath(),
        ];
    }
}
