<?php

namespace Database\Factories;

use App\Models\StationResult;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RawSegment>
 */
class RawSegmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    // public function definition(): array
    // {
    //     $start = $this->faker->randomFloat(2, 0, 100);
    //     $end = $this->faker->randomFloat(2, 1, 100);
    //     $duration = $end - $start;

    //     return [
    //         'station_id' => StationResult::inRandomOrder()->first()->id,
    //         'activity' => $this->faker->randomElement([
    //             'Menjahit',
    //             'Memotong',
    //             'Merapikan',
    //             'Memasang kancing',
    //             'Pengecekan Barang',
    //             'Mengambil Produk',
    //             'Meletakkan Barang'
    //         ]),
    //         'start_time' => $start,
    //         'end_time' => $end,
    //         'duration' => $duration,
    //     ];

    // }
    public function definition(): array
    {
        $station = StationResult::inRandomOrder()->first();

        $meanCt = $station->mean_ct;
        $nCycles = $station->n_cycles;

        // estimasi durasi video
        $videoDuration = $meanCt * $nCycles;

        // durasi segmen realistis (lebih kecil dari cycle time)
        $duration = $this->faker->randomFloat(3, 0.2, $meanCt * 0.25);
        $start = $this->faker->randomFloat(3, 0, $videoDuration - $duration);
        $end = $start + $duration;

        return [

            'station_id' => $station->id,

            'activity' => $this->faker->randomElement([
                'Ambil Komponen',
                'Posisi Material',
                'Jahit Sisi',
                'Potong Benang',
                'Rapikan Jahitan',
                'Inspeksi Visual',
                'Ambil Alat',
                'Setel Material'
            ]),

            'start_time' => $start,

            'end_time' => $end,

            'duration' => $duration,
        ];
    }
}
