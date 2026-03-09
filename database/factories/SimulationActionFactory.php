<?php

namespace Database\Factories;

use App\Models\SimulationResult;
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
    //     return [
    //         'simulation_id' => SimulationResult::inRandomOrder()->first()->id,
    //         'action_type' => $this->faker->randomElement(['kaizen', 'redistribution']),
    //         'station_from' => StationResult::inRandomOrder()->first()->station_name,
    //         'station_to' => StationResult::inRandomOrder()->first()->station_name,
    //         'elemen_kerja' => $this->faker->randomElement(['Menjahit lengan', 'Memotong kain', 'Memasang kancing', 'Merapikan jahitan']),
    //         'durasi_before' => $this->faker->randomFloat(2, 2, max: 200),
    //         'durasi_after' => $this->faker->randomFloat(2, 2, max: 200),
    //         'saving' => $this->faker->randomFloat(2, 2, max: 200),
    //         'metode' => $this->faker->sentence(),
    //         'risk_stasiun' => $this->faker->randomElement(['Low Risk', 'Medium Risk', 'High Risk']),
    //     ];
    // }

    public function definition(): array
    {
        $sim = SimulationResult::inRandomOrder()->first();

        $type = $this->faker->randomElement([
            'kaizen',
            'kaizen',
            'kaizen',
            'redistribution'
        ]);

        $stationFrom = $this->faker->randomElement([
            'Jahit Lengan',
            'Pasang Furing',
            'Obras Sisi',
            'Jahit Kerah',
            'Pressing',
            'QC',
            'Pasang Kancing',
            'Finishing'
        ]);

        $stationTo = $this->faker->randomElement([
            'Jahit Lengan',
            'Pasang Furing',
            'Obras Sisi',
            'Jahit Kerah',
            'Pressing',
            'QC',
            'Pasang Kancing',
            'Finishing'
        ]);

        $durasiBefore = $this->faker->randomFloat(2, 2, 20);

        if ($type === 'kaizen') {

            $durasiAfter = $durasiBefore - $this->faker->randomFloat(2, 0.5, $durasiBefore * 0.6);

            $saving = $durasiBefore - $durasiAfter;

        } else {

            $stationTo = $this->faker->randomElement([
                'Jahit Lengan',
                'Pasang Furing',
                'Obras Sisi',
                'Jahit Kerah',
                'Pressing',
                'QC'
            ]);

            if ($stationTo === $stationFrom) {
                $stationTo = 'Finishing';
            }

            $durasiAfter = 0;

            $saving = $durasiBefore;
        }

        $risk = $this->faker->randomElement([
            'Low Risk',
            'Medium Risk',
            'High Risk'
        ]);

        return [

            'simulation_id' => $sim->id,

            'action_type' => $type,

            'station_from' => $stationFrom,

            'station_to' => $stationTo,

            'elemen_kerja' => $this->faker->randomElement([
                'Ambil Komponen',
                'Posisi Material',
                'Jahit Sisi',
                'Potong Benang',
                'Rapikan Jahitan',
                'Inspeksi Visual'
            ]),

            'durasi_before' => $durasiBefore,

            'durasi_after' => $durasiAfter,

            'saving' => $saving,

            'metode' => $this->faker->randomElement([
                'Eliminasi gerakan tidak perlu',
                'Perbaikan posisi alat',
                'Standarisasi metode kerja',
                'Redistribusi elemen ke stasiun idle'
            ]),

            'risk_stasiun' => $risk
        ];
    }
}
