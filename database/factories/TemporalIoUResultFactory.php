<?php

namespace Database\Factories;

use App\Models\StationResult;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TemporalIoUResult>
 */
class TemporalIoUResultFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    // public function definition(): array
    // {
    //     return [
    //         'station_id' => StationResult::inRandomOrder()->first()->id,
    //         'calculated_by' => User::inRandomOrder()->first()->id,
    //         'calculated_at' => now(),
    //         'activity' => $this->faker->randomElement([
    //             'Menjahit',
    //             'Memotong',
    //             'Merapikan',
    //             'Memasang kancing',
    //             'Pengecekan Barang',
    //             'Mengambil Produk',
    //             'Meletakkan Barang',
    //         ]),
    //         'n_samples_pred' => $this->faker->numberBetween(1, 100),
    //         'n_samples_gt' => $this->faker->numberBetween(1, 100),
    //         'total_intersection' => $this->faker->randomFloat(2, 0, 100),
    //         'total_union' => $this->faker->randomFloat(2, 0, 100),
    //         'avg_iou' => $this->faker->randomFloat(2, 0, 1),
    //         'keterangan' => $this->faker->randomElement(['Baik', 'Cukup', 'Perlu Perbaikan']),
    //     ];
    // }
    public function definition(): array
    {
        $station = StationResult::inRandomOrder()->first();
        $user = User::inRandomOrder()->first();
        $nPred = $this->faker->numberBetween(5, 25);
        $nGt = $this->faker->numberBetween(5, 25);
        $totalUnion = $this->faker->randomFloat(2, 20, 300);
        $iouRatio = $this->faker->randomFloat(3, 0.25, 0.90);
        $totalIntersection = $totalUnion * $iouRatio;
        $avgIou = $totalIntersection / $totalUnion;

        // klasifikasi
        if ($avgIou >= 0.7) {
            $keterangan = 'Baik';
        } elseif ($avgIou >= 0.4) {
            $keterangan = 'Cukup';
        } else {
            $keterangan = 'Perlu Perbaikan';
        }

        return [
            'station_id' => $station->id,
            'calculated_by' => $user->id,
            'calculated_at' => now()->subMinutes(rand(5, 300)),

            'activity' => $this->faker->randomElement([
                'Ambil Komponen',
                'Posisi Material',
                'Jahit Sisi',
                'Potong Benang',
                'Rapikan Jahitan',
                'Inspeksi Visual',
                'Pasang Kancing'
            ]),

            'n_samples_pred' => $nPred,
            'n_samples_gt' => $nGt,

            'total_intersection' => $totalIntersection,
            'total_union' => $totalUnion,

            'avg_iou' => $avgIou,

            'keterangan' => $keterangan,
        ];
    }
}
