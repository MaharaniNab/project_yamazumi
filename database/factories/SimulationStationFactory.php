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
    //     $sim = SimulationResult::inRandomOrder()->first();

    //     $job = $sim->job;
    //     $takt = $job->takt_time ?? 120;

    //     $z = $sim->z_score_used ?? 2;

    //     $status = $this->faker->randomElement([
    //         'Bottleneck',
    //         'At-Risk',
    //         'Balanced',
    //         'Underloaded'
    //     ]);

    //     switch ($status) {

    //         case 'Bottleneck':
    //             $mean = $this->faker->randomFloat(2, $takt * 1.02, $takt * 1.25);
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

    //     // sigma setelah optimasi biasanya lebih kecil
    //     $sigma = $this->faker->randomFloat(2, 0.5, 6);

    //     $robust = $mean + ($z * $sigma);

    //     $cv = ($sigma / $mean) * 100;

    //     // klasifikasi risk
    //     if ($cv < 10) {
    //         $risk = 'Low Risk';
    //     } elseif ($cv < 20) {
    //         $risk = 'Medium Risk';
    //     } else {
    //         $risk = 'High Risk';
    //     }

    //     return [

    //         'simulation_id' => $sim->id,

    //         'station_name' => $this->faker->randomElement([
    //             'Jahit Lengan',
    //             'Pasang Furing',
    //             'Obras Sisi',
    //             'Jahit Kerah',
    //             'Pressing',
    //             'QC',
    //             'Pasang Kancing',
    //             'Finishing'
    //         ]),

    //         'mean_ct_after' => $mean,

    //         'sigma_after' => $sigma,

    //         'robust_ct_after' => $robust,

    //         'cv_after' => $cv,

    //         'status_after' => $status,

    //         'risk_after' => $risk,
    //     ];
    // }

    public function definition(): array
    {
        // Relasi ke simulation_results
        $simulation = SimulationResult::inRandomOrder()->first();

        // Nama stasiun
        $stationName = $this->faker->randomElement([
            'Cutting',
            'Sewing',
            'Pressing',
            'Finishing',
            'QC'
        ]);

        // CT sebelum kaizen
        $meanCtBefore = $this->faker->randomFloat(2, 200, 800);
        $nvaPctBefore = $this->faker->randomFloat(2, 0.1, 0.5); // 10–50% dari CT
        $isNvaDominant = $nvaPctBefore > 0.2; // sesuai definisi dominan NVA

        // Saving NVA
        $savingTotal = $meanCtBefore * $nvaPctBefore * $simulation->nva_elimination_pct;
        $meanCtAfter = max(1, $meanCtBefore - $savingTotal);

        // Profil variabilitas sesudah kaizen
        $sigmaAfter = $this->faker->randomFloat(2, 5, 50);
        $robustCtAfter = $meanCtAfter + 2 * $sigmaAfter;
        $cvAfter = ($sigmaAfter / $meanCtAfter) * 100;

        // Status sebelum/ sesudah
        $statusBefore = $this->faker->randomElement(['Bottleneck', 'At-Risk', 'Balanced', 'Underloaded']);
        $statusAfter = $statusBefore === 'Bottleneck' && $meanCtAfter < $simulation->neck_after
            ? 'Balanced'
            : $statusBefore;

        $kaizenResult = $statusBefore === 'Bottleneck' && $statusAfter === 'Balanced'
            ? 'Resolved'
            : ($statusBefore === 'Bottleneck' ? 'Still Bottleneck' : 'No Action');

        // Man Power Balancing (Phase 3)
        $mpAssigned = $this->faker->numberBetween(1, 3);
        $ctEfektif = $meanCtAfter / $mpAssigned;
        $mpBalancePct = ($ctEfektif / $simulation->job->takt_time) * 100;
        $mpUtilized = $mpBalancePct <= 100
            ? 'Optimal'
            : ($mpBalancePct <= 120 ? 'Baik' : 'Underutilized');

        return [
            'simulation_id' => $simulation->id,
            'station_name' => $stationName,
            'priority_order' => $this->faker->numberBetween(0, 5),
            'is_nva_dominant' => $isNvaDominant,
            'nva_pct_before' => $nvaPctBefore,

            // CT sebelum/ sesudah
            'mean_ct_before' => $meanCtBefore,
            'mean_ct_after' => $meanCtAfter,
            'saving_total' => $savingTotal,

            // Variabilitas sesudah
            'sigma_after' => $sigmaAfter,
            'robust_ct_after' => $robustCtAfter,
            'cv_after' => $cvAfter,

            // Status
            'status_before' => $statusBefore,
            'status_after' => $statusAfter,
            'kaizen_result' => $kaizenResult,

            // MP Balancing
            'mp_assigned' => $mpAssigned,
            'ct_efektif' => $ctEfektif,
            'mp_balance_pct' => $mpBalancePct,
            'mp_utilized' => $mpUtilized,
        ];
    }


}
