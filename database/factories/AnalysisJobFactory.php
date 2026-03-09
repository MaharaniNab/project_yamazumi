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
    //     $outputHarian = $this->faker->numberBetween(100, 300);
    //     $jamKerjaDetik = 25920.0;

    //     $taktTime = $jamKerjaDetik / $outputHarian;
    //     $neckTimeMean = $this->faker->randomFloat(2, 40, 80);
    //     $lineOutputHari = intval($jamKerjaDetik / $neckTimeMean);
    //     $totalCycleTime = $this->faker->randomFloat(2, 200, 500);
    //     $opTeoritis = $totalCycleTime / $taktTime;
    //     $ctMax = $this->faker->randomFloat(2, 60, 100); // CT maksimum stasiun
    //     $nStations = $this->faker->numberBetween(5, 20);
    //     $lineEfficiency = ($totalCycleTime / ($nStations * $ctMax)) * 100;
    //     $balanceDelay = 100 - $lineEfficiency;
    //     $smoothnessIndex = $this->faker->randomFloat(2, 5, 20);
    //     $sigma = $this->faker->randomFloat(2, 2, 10);
    //     $neckTimeRobust = $neckTimeMean + 2 * $sigma;
    //     $lineRiskScore = $this->faker->randomFloat(2, 10, 50);

    //     return [
    //         'user_id' => $this->faker->randomElement(User::all())->id,
            // 'status' => $this->faker->randomElement(['processing', 'completed', 'failed']),
    //         'error_msg' => $this->faker->sentence(),

    //         // Metadata Lini
    //         'line_name' => $this->faker->randomElement(['Line Jas B', 'Line Celana A', 'Line Kaos C']),
    //         'part_name' => $this->faker->randomElement(['Jas', 'Celana', 'Kaos']),
    //         'style' => $this->faker->randomElement(['A', 'B', 'C']),
    //         'brand' => $this->faker->randomElement(['Brand A', 'Brand B', 'Brand C']),
    //         'output_harian' => $outputHarian,
    //         'jam_kerja_detik' => $jamKerjaDetik,
    //         'takt_time' => $taktTime,

    //         // Metrik Deterministik
    //         'total_cycle_time' => $totalCycleTime,
    //         'neck_time_mean' => $neckTimeMean,
    //         'line_efficiency' => $lineEfficiency,
    //         'balance_delay' => $balanceDelay,
    //         'smoothness_index' => $smoothnessIndex,

    //         // Metrik Robust
    //         'neck_time_robust' => $neckTimeRobust,
    //         'line_risk_score' => $lineRiskScore,

    //         // Output & Operator
    //         'line_output_hari' => $lineOutputHari,
    //         'op_teoritis' => $opTeoritis,
    //         'n_stations' => $nStations,

    //         // Chart
    //         'chart_path' => $this->faker->filePath(),
    //     ];
    // }

     public function definition(): array
    {
        $jamKerjaDetik = 25920.0;

        // target output realistis garmen
        $outputHarian = $this->faker->numberBetween(250, 900);

        // Takt Time
        $taktTime = $jamKerjaDetik / $outputHarian;

        // jumlah stasiun realistis
        $nStations = $this->faker->numberBetween(6, 16);

        // generate CT tiap stasiun sekitar takt
        $cts = [];

        for ($i = 0; $i < $nStations; $i++) {

            $type = $this->faker->randomElement([
                'under',
                'balanced',
                'atrisk',
                'bottleneck'
            ]);

            switch ($type) {

                case 'bottleneck':
                    $ct = $this->faker->randomFloat(2, $taktTime * 1.05, $taktTime * 1.30);
                    break;

                case 'atrisk':
                    $ct = $this->faker->randomFloat(2, $taktTime * 0.91, $taktTime * 0.99);
                    break;

                case 'balanced':
                    $ct = $this->faker->randomFloat(2, $taktTime * 0.75, $taktTime * 0.90);
                    break;

                default:
                    $ct = $this->faker->randomFloat(2, $taktTime * 0.40, $taktTime * 0.74);
            }

            $cts[] = $ct;
        }

        $totalCycleTime = array_sum($cts);

        $ctMax = max($cts);

        // bottleneck
        $neckTimeMean = $ctMax;

        // output aktual
        $lineOutputHari = intval($jamKerjaDetik / $neckTimeMean);

        // operator teoritis
        $opTeoritis = $totalCycleTime / $taktTime;

        // Line Efficiency
        $lineEfficiency = ($totalCycleTime / ($nStations * $ctMax)) * 100;

        // Balance Delay
        $balanceDelay = 100 - $lineEfficiency;

        // Smoothness Index
        $sum = 0;

        foreach ($cts as $ct) {
            $sum += pow(($ctMax - $ct), 2);
        }

        $smoothnessIndex = sqrt($sum);

        // Robust metric
        $sigma = $this->faker->randomFloat(2, 2, 8);

        $neckTimeRobust = $neckTimeMean + (2 * $sigma);

        $lineRiskScore = $this->faker->randomFloat(2, 10, 60);

        return [

            'user_id' => User::inRandomOrder()->first()->id,

            'status' => $this->faker->randomElement(['processing', 'completed', 'failed']),

            'line_name' => $this->faker->randomElement([
                'Line Jas A',
                'Line Jas B',
                'Line Jas C',
                'Line Pant A',
                'Line Pant B',
                'Line Ladies Jas',
                'Line Ladies Pant'
            ]),

            'part_name' => $this->faker->randomElement([
                'Sewing',
                'Cutting',
                'Finishing',
                'Lining Body',
                'Fulling Skirt',
                'Pasang Lengan'
            ]),

            'style' => $this->faker->randomElement([
                'Slim Fit',
                'Regular Fit',
                'Classic Fit',
                '2PP',
                'Yoga Pants'
            ]),

            'brand' => $this->faker->randomElement([
                'Zara',
                'Hugo Boss',
                'Uniqlo',
                'H&M',
                'TSC Polo',
                'Regal 2PP'
            ]),

            'output_harian' => $outputHarian,
            'jam_kerja_detik' => $jamKerjaDetik,
            'takt_time' => $taktTime,

            'total_cycle_time' => $totalCycleTime,
            'neck_time_mean' => $neckTimeMean,

            'line_efficiency' => $lineEfficiency,
            'balance_delay' => $balanceDelay,
            'smoothness_index' => $smoothnessIndex,

            'neck_time_robust' => $neckTimeRobust,
            'line_risk_score' => $lineRiskScore,

            'line_output_hari' => $lineOutputHari,
            'op_teoritis' => $opTeoritis,

            'n_stations' => $nStations,

            'chart_path' => $this->faker->filePath()
        ];
    }
}
