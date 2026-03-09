<?php

namespace Database\Factories;

use App\Models\AnalysisJob;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StationResult>
 */
class StationResultFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    // public function definition(): array
    // {
    //     $takt = AnalysisJob::inRandomOrder()->first()->takt_time ?? 120;

    //     $status = $this->faker->randomElement([
    //         'Bottleneck',
    //         'At-Risk',
    //         'Balanced',
    //         'Underloaded'
    //     ]);

    //     switch ($status) {

    //         case 'Bottleneck':
    //             $mean = $this->faker->randomFloat(2, $takt * 1.05, $takt * 1.35);
    //             break;

    //         case 'At-Risk':
    //             $mean = $this->faker->randomFloat(2, $takt * 0.91, $takt * 0.99);
    //             break;

    //         case 'Balanced':
    //             $mean = $this->faker->randomFloat(2, $takt * 0.75, $takt * 0.90);
    //             break;

    //         default: // Underloaded
    //             $mean = $this->faker->randomFloat(2, $takt * 0.40, $takt * 0.74);
    //     }

    //     $sigma = $this->faker->randomFloat(2, 1, 10);

    //     $robust = $mean + (2 * $sigma);

    //     $cv = ($sigma / $mean) * 100;

    //     $idle = max($takt - $mean, 0);
    //     $overflow = max($robust - $takt, 0);

    //     // risk category dari CV
    //     if ($cv < 10) {
    //         $risk = 'Low Risk';
    //     } elseif ($cv < 20) {
    //         $risk = 'Medium Risk';
    //     } else {
    //         $risk = 'High Risk';
    //     }

    //     return [
    //         'job_id' => AnalysisJob::inRandomOrder()->first()->id,

    //         'station_name' => $this->faker->randomElement([
    //             'Jahit Lengan',
    //             'Pasang Furing',
    //             'Obras Sisi',
    //             'Jahit Kerah',
    //             'Pressing',
    //             'QC'
    //         ]),

    //         'station_order' => $this->faker->numberBetween(0, 5),

    //         'mean_ct' => $mean,
    //         'station_sigma' => $sigma,
    //         'robust_ct' => $robust,
    //         'total_variance' => pow($sigma, 2),
    //         'cv_persen' => $cv,

    //         'idle_time' => $idle,
    //         'overflow_robust' => $overflow,

    //         'risk_category' => $risk,
    //         'status_station' => $status,

    //         'n_cycles' => $this->faker->numberBetween(5, 20),

    //         'output_jam' => 3600 / $mean,
    //         'output_hari' => intval(25920 / $mean),
    //     ];
    // }

    public function definition(): array
    {
        $job = AnalysisJob::inRandomOrder()->first();

        $takt = $job?->takt_time ?? 120;

        $status = $this->faker->randomElement([
            'Bottleneck',
            'At-Risk',
            'Balanced',
            'Underloaded'
        ]);

        // Mean CT berdasarkan status vs takt
        switch ($status) {

            case 'Bottleneck':
                $mean = $this->faker->randomFloat(2, $takt * 1.05, $takt * 1.35);
                break;

            case 'At-Risk':
                $mean = $this->faker->randomFloat(2, $takt * 0.91, $takt * 0.99);
                break;

            case 'Balanced':
                $mean = $this->faker->randomFloat(2, $takt * 0.75, $takt * 0.90);
                break;

            default: // Underloaded
                $mean = $this->faker->randomFloat(2, $takt * 0.40, $takt * 0.74);
        }

        // Variasi stasiun (dibatasi agar realistis)
        $sigma = $this->faker->randomFloat(2, 1, $mean * 0.25);

        // Robust CT
        $robust = $mean + (2 * $sigma);

        // Variance
        $variance = pow($sigma, 2);

        // Coefficient of variation
        $cv = ($sigma / $mean) * 100;

        // Idle dan overflow
        $idle = max($takt - $mean, 0);
        $overflow = max($robust - $takt, 0);

        // Risk category dari CV
        if ($cv < 10) {
            $risk = 'Low Risk';
        } elseif ($cv < 20) {
            $risk = 'Medium Risk';
        } else {
            $risk = 'High Risk';
        }

        return [

            'job_id' => $job?->id,

            'station_name' => $this->faker->randomElement([
                'Jahit Lengan',
                'Pasang Furing',
                'Obras Sisi',
                'Jahit Kerah',
                'Pressing',
                'QC',
                'Pasang Kancing',
                'Finishing'
            ]),

            'station_order' => $this->faker->numberBetween(0, 5),

            'mean_ct' => $mean,
            'station_sigma' => $sigma,
            'robust_ct' => $robust,
            'total_variance' => $variance,
            'cv_persen' => $cv,

            'idle_time' => $idle,
            'overflow_robust' => $overflow,

            'risk_category' => $risk,
            'status_station' => $status,

            'n_cycles' => $this->faker->numberBetween(5, 20),

            'output_jam' => 3600 / $mean,
            'output_hari' => intval(25920 / $mean),
        ];
    }
}
