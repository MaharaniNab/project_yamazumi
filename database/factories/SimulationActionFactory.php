<?php

namespace Database\Factories;

use App\Models\SimulationResult;
use App\Models\SimulationStation;
use App\Models\StationResult;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SimulationAction>
 */
class SimulationActionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    // public function definition(): array
    // {
    //     $sim = SimulationResult::inRandomOrder()->first();

    //     $type = $this->faker->randomElement([
    //         'kaizen',
    //         'kaizen',
    //         'kaizen',
    //         'redistribution'
    //     ]);

    //     $stationFrom = $this->faker->randomElement([
    //         'Jahit Lengan',
    //         'Pasang Furing',
    //         'Obras Sisi',
    //         'Jahit Kerah',
    //         'Pressing',
    //         'QC',
    //         'Pasang Kancing',
    //         'Finishing'
    //     ]);

    //     $stationTo = $this->faker->randomElement([
    //         'Jahit Lengan',
    //         'Pasang Furing',
    //         'Obras Sisi',
    //         'Jahit Kerah',
    //         'Pressing',
    //         'QC',
    //         'Pasang Kancing',
    //         'Finishing'
    //     ]);

    //     $durasiBefore = $this->faker->randomFloat(2, 2, 20);

    //     if ($type === 'kaizen') {

    //         $durasiAfter = $durasiBefore - $this->faker->randomFloat(2, 0.5, $durasiBefore * 0.6);

    //         $saving = $durasiBefore - $durasiAfter;

    //     } else {

    //         $stationTo = $this->faker->randomElement([
    //             'Jahit Lengan',
    //             'Pasang Furing',
    //             'Obras Sisi',
    //             'Jahit Kerah',
    //             'Pressing',
    //             'QC'
    //         ]);

    //         if ($stationTo === $stationFrom) {
    //             $stationTo = 'Finishing';
    //         }

    //         $durasiAfter = 0;

    //         $saving = $durasiBefore;
    //     }

    //     $risk = $this->faker->randomElement([
    //         'Low Risk',
    //         'Medium Risk',
    //         'High Risk'
    //     ]);

    //     return [

    //         'simulation_id' => $sim->id,

    //         'action_type' => $type,

    //         'station_from' => $stationFrom,

    //         'station_to' => $stationTo,

    //         'elemen_kerja' => $this->faker->randomElement([
    //             'Ambil Komponen',
    //             'Posisi Material',
    //             'Jahit Sisi',
    //             'Potong Benang',
    //             'Rapikan Jahitan',
    //             'Inspeksi Visual'
    //         ]),

    //         'durasi_before' => $durasiBefore,

    //         'durasi_after' => $durasiAfter,

    //         'saving' => $saving,

    //         'metode' => $this->faker->randomElement([
    //             'Eliminasi gerakan tidak perlu',
    //             'Perbaikan posisi alat',
    //             'Standarisasi metode kerja',
    //             'Redistribusi elemen ke stasiun idle'
    //         ]),

    //         'risk_stasiun' => $risk
    //     ];
    // }

    public function definition(): array
    {
        // Ambil satu SimulationResult
        $simulation = SimulationResult::inRandomOrder()->first();

        // Ambil satu station acak, kalau belum ada buat baru
        $station = $simulation->stations()->inRandomOrder()->first()
            ?? SimulationStation::factory()->create([
                'simulation_id' => $simulation->id,
            ]);

        $statusStasiun = $this->faker->randomElement(['Bottleneck', 'At-Risk', 'Balanced', 'Underloaded']);
        $cvStasiun = $station->cv_after ?? $this->faker->randomFloat(2, 10, 40);
        $elemenKerja = $this->faker->randomElement([
            'Menjahit Lengan',
            'Pasang Kancing',
            'Pressing',
            'Quality Check'
        ]);

        $kategoriVa = $this->faker->randomElement([
            'NVA',
            'N-NVA',
            'VA'
        ]);

        $durasiBefore = $this->faker->randomFloat(2, 20, 120);
        $pctReduksi = $this->faker->randomElement(['-100%', '-20%']);
        $reduksiFactor = $pctReduksi === '-100%' ? 1.0 : 0.2;
        $durasiAfter = max(0, $durasiBefore * (1 - $reduksiFactor));
        $saving = $durasiBefore - $durasiAfter;

        $metode = $pctReduksi === '-100%'
            ? 'Eliminasi penuh aktivitas NVA'
            : 'Reduksi aktivitas N-NVA sebesar 20%';

        return [
            'simulation_id' => $simulation->id,
            'priority_order' => $station->priority_order ?? 1,
            'action_type' => 'nva_elimination',

            // Detail Elemen
            'station_from' => $station->station_name,
            'status_stasiun' => $statusStasiun,
            'cv_stasiun' => $cvStasiun,
            'elemen_kerja' => $elemenKerja,
            'kategori_va' => $kategoriVa,

            // Nilai Sebelum / Sesudah / Saving
            'durasi_before' => $durasiBefore,
            'durasi_after' => $durasiAfter,
            'saving' => $saving,
            'pct_reduksi' => $pctReduksi,
            'metode' => $metode,
        ];
    }
}
