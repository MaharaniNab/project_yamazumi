<?php

namespace Database\Factories;

use App\Models\SimulationResult;
use App\Models\StationResult;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SimulationStation>
 */
class SimulationStationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    // public function definition(): array
    // {
    //     $station_name = StationResult::inRandomOrder()->value('station_name');        // hasil: "Line A, Line B, Line C"
    //     return [
    //         'simulation_id' =>SimulationResult::inRandomOrder()->first()->id,
    //         'station_name' => $station_name,
    //         'mean_ct_after' => $this->faker->randomFloat(2, 40, 80),
    //         'robust_ct_after' => $this->faker->randomFloat(2, 1, 10),
    //         'sigma_after' => $this->faker->randomFloat(2, 0, 10),
    //         'cv_after' => $this->faker->randomFloat(2, 0, 10),
    //         'status_after' => $this->faker->randomElement(['Bottleneck', 'At-Risk', 'Balanced', 'Underloaded']),
    //         'risk_after' => $this->faker->randomElement(['Low Risk', 'Medium Risk', 'High Risk']),
    //     ];
    // }

    public function definition(): array
    {
        $sim = SimulationResult::inRandomOrder()->first();

        $job = $sim->job;
        $takt = $job->takt_time ?? 120;

        $z = $sim->z_score_used ?? 2;

        $status = $this->faker->randomElement([
            'Bottleneck',
            'At-Risk',
            'Balanced',
            'Underloaded'
        ]);

        switch ($status) {

            case 'Bottleneck':
                $mean = $this->faker->randomFloat(2, $takt * 1.02, $takt * 1.25);
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

        // sigma setelah optimasi biasanya lebih kecil
        $sigma = $this->faker->randomFloat(2, 0.5, 6);

        $robust = $mean + ($z * $sigma);

        $cv = ($sigma / $mean) * 100;

        // klasifikasi risk
        if ($cv < 10) {
            $risk = 'Low Risk';
        } elseif ($cv < 20) {
            $risk = 'Medium Risk';
        } else {
            $risk = 'High Risk';
        }

        return [

            'simulation_id' => $sim->id,

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

            'mean_ct_after' => $mean,

            'sigma_after' => $sigma,

            'robust_ct_after' => $robust,

            'cv_after' => $cv,

            'status_after' => $status,

            'risk_after' => $risk,
        ];
    }
}
