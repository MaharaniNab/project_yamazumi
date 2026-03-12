<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AnalysisJob>
 */
class AnalysisJobFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */


    // public function definition(): array
    // {
    //     $jamKerjaDetik = 25920.0;
    //     $outputHarian = $this->faker->numberBetween(100, 400);
    //     $taktTime = $jamKerjaDetik / $outputHarian;
    //     $nStations = $this->faker->numberBetween(6, 16);
    //     $cts = [];

    //     for ($i = 0; $i < $nStations; $i++) {

    //         $type = $this->faker->randomElement([
    //             'under',
    //             'balanced',
    //             'atrisk',
    //             'bottleneck'
    //         ]);

    //         switch ($type) {

    //             case 'bottleneck':
    //                 $ct = $this->faker->randomFloat(2, $taktTime * 1.05, $taktTime * 1.30);
    //                 break;

    //             case 'atrisk':
    //                 $ct = $this->faker->randomFloat(2, $taktTime * 0.91, $taktTime * 0.99);
    //                 break;

    //             case 'balanced':
    //                 $ct = $this->faker->randomFloat(2, $taktTime * 0.75, $taktTime * 0.90);
    //                 break;

    //             default:
    //                 $ct = $this->faker->randomFloat(2, $taktTime * 0.40, $taktTime * 0.74);
    //         }

    //         $cts[] = $ct;
    //     }

    //     $totalCycleTime = array_sum($cts);

    //     $ctMax = max($cts);
    //     $neckTimeMean = $ctMax;
    //     $lineOutputHari = intval($jamKerjaDetik / $neckTimeMean);
    //     $opTeoritis = $totalCycleTime / $taktTime;
    //     $lineEfficiency = ($totalCycleTime / ($nStations * $ctMax)) * 100;
    //     $balanceDelay = 100 - $lineEfficiency;
    //     $sum = 0;

    //     foreach ($cts as $ct) {
    //         $sum += pow(($ctMax - $ct), 2);
    //     }

    //     $smoothnessIndex = sqrt($sum);

    //     $sigma = $this->faker->randomFloat(2, 2, 8);
    //     $neckTimeRobust = $neckTimeMean + (2 * $sigma);
    //     $lineRiskScore = $this->faker->randomFloat(2, 10, 60);

    //     return [

    //         'user_id' => User::inRandomOrder()->first()->id,

    //         'status' => $this->faker->randomElement(['processing', 'completed', 'failed']),

    //         'line_name' => $this->faker->randomElement([
    //             'Line Jas A',
    //             'Line Jas B',
    //             'Line Jas C',
    //             'Line Pant A',
    //             'Line Pant B',
    //             'Line Ladies Jas',
    //             'Line Ladies Pant'
    //         ]),

    //         'part_name' => $this->faker->randomElement([
    //             'Sewing',
    //             'Cutting',
    //             'Finishing',
    //             'Lining Body',
    //             'Fulling Skirt',
    //             'Pasang Lengan'
    //         ]),

    //         'style' => $this->faker->randomElement([
    //             'Slim Fit',
    //             'Regular Fit',
    //             'Classic Fit',
    //             '2PP',
    //             'Yoga Pants'
    //         ]),

    //         'brand' => $this->faker->randomElement([
    //             'Zara',
    //             'Hugo Boss',
    //             'Uniqlo',
    //             'H&M',
    //             'TSC Polo',
    //             'Regal 2PP'
    //         ]),

    //         'output_harian' => $outputHarian,
    //         'jam_kerja_detik' => $jamKerjaDetik,
    //         'takt_time' => $taktTime,

    //         'total_cycle_time' => $totalCycleTime,
    //         'neck_time_mean' => $neckTimeMean,

    //         'line_efficiency' => $lineEfficiency,
    //         'balance_delay' => $balanceDelay,
    //         'smoothness_index' => $smoothnessIndex,

    //         'neck_time_robust' => $neckTimeRobust,
    //         'line_risk_score' => $lineRiskScore,

    //         'line_output_hari' => $lineOutputHari,
    //         'op_teoritis' => $opTeoritis,

    //         'n_stations' => $nStations,

    //         'chart_path' => $this->faker->filePath()
    //     ];
    // }

    public function definition(): array
    {
        $outputHarian = $this->faker->numberBetween(100, 500);
        $jamKerjaDetik = 25920.0;
        $taktTime = $jamKerjaDetik / $outputHarian;

        // Simulasi jumlah stasiun dan cycle time
        $nStations = $this->faker->numberBetween(5, 20);
        $ctValues = collect(range(1, $nStations))
            ->map(fn() => $this->faker->randomFloat(2, 200, 800));
        $totalCycleTime = $ctValues->sum();
        $ctMax = $ctValues->max();
        $neckTimeMean = $ctMax; // bottleneck = CT max

        // Metrik deterministik
        $lineEfficiency = ($totalCycleTime / ($nStations * $ctMax)) * 100;
        $balanceDelay = 100 - $lineEfficiency;
        $smoothnessIndex = sqrt(
            $ctValues->map(fn($ct) => pow($ctMax - $ct, 2))->sum()
        );

        // Metrik robust (μ + 2σ)
        $meanCT = $ctValues->avg();
        $sigmaCT = sqrt($ctValues->map(fn($ct) => pow($ct - $meanCT, 2))->sum() / $nStations);
        $neckTimeRobust = $meanCT + 2 * $sigmaCT;
        $lambda = $this->faker->randomFloat(2, 0.5, 2.0); // faktor risiko λ
        $lineRiskScore = $lambda * ($sigmaCT ** 2 * $nStations); // λ × Σσ²_stasiun

        // Output & operator
        $lineOutputHari = intval($jamKerjaDetik / $neckTimeMean);
        $mpAktual = $this->faker->numberBetween(10, 50);
        $opTeoritis = $totalCycleTime / $taktTime;

        return [
            'user_id' => User::inRandomOrder()->first()->id,
            'status' => $this->faker->randomElement(['processing', 'completed', 'failed']),
            'error_msg' => $this->faker->optional()->sentence(),

            // Metadata Lini
            'line_name' => $this->faker->randomElement(['Line Jas B', 'Line Celana A', 'Line Kemeja C']),
            'part_name' => $this->faker->optional()->word(),
            'style' => $this->faker->optional()->word(),
            'brand' => $this->faker->optional()->company(),
            'output_harian' => $outputHarian,
            'jam_kerja_detik' => $jamKerjaDetik,
            'takt_time' => $taktTime,

            // Metrik Deterministik
            'total_cycle_time' => $totalCycleTime,
            'neck_time_mean' => $neckTimeMean,
            'line_efficiency' => $lineEfficiency,
            'balance_delay' => $balanceDelay,
            'smoothness_index' => $smoothnessIndex,

            // Metrik Robust
            'neck_time_robust' => $neckTimeRobust,
            'line_risk_score' => $lineRiskScore,

            // Output & Operator
            'line_output_hari' => $lineOutputHari,
            'mp_aktual' => $mpAktual,
            'op_teoritis' => $opTeoritis,
            'n_stations' => $nStations,
            'chart_path' => $this->faker->optional()->filePath(),

            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
