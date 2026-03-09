<?php

namespace Database\Factories;

use App\Models\StationResult;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkElement>
 */
class WorkElementFactory extends Factory
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
    //         'elemen_kerja' => $this->faker->randomElement([
    //             'Menjahit lengan',
    //             'Memotong kain',
    //             'Memasang kancing',
    //             'Merapikan jahitan'
    //         ]),
    //         'durasi_detik' => $this->faker->randomFloat(2, 2, max: 200),
    //         'std_dev' => $this->faker->randomFloat(2, 0.1, 1.0),
    //         'cv_persen' => $this->faker->randomFloat(2, 5, 100),
    //         'frekuensi' => $this->faker->numberBetween(1, 50),
    //         'total_durasi' => $this->faker->randomFloat(2, 50, 500),
    //         'mean_per_kejadian' => $this->faker->randomFloat(2, 2, 100),
    //         'kategori_va' => $this->faker->randomElement(['VA', 'N-NVA', 'NVA']),
    //         'warna_hex' => $this->faker->hexColor(),

    //     ];
    // }
    public function definition(): array
    {
        $station = StationResult::inRandomOrder()->first();
        $meanCt = $station->mean_ct;
        $nCycles = $station->n_cycles;
        $weight = $this->faker->randomFloat(3, 0.1, 0.4);
        $durasiPerCycle = $meanCt * $weight;
        $frekuensi = $this->faker->numberBetween(1, $nCycles);
        $totalDurasi = $durasiPerCycle * $nCycles;
        $meanPerKejadian = $totalDurasi / $frekuensi;
        $stdDev = $this->faker->randomFloat(2, 0.1, $durasiPerCycle * 0.25);
        $cv = ($stdDev / $durasiPerCycle) * 100;

        // kategori VA realistis (mayoritas VA)
        $kategori = $this->faker->randomElement([
            'VA',
            'VA',
            'VA',
            'N-NVA',
            'NVA'
        ]);

        $warna = match ($kategori) {
            'VA' => '#4575b4',
            'N-NVA' => '#fdae61',
            'NVA' => '#d73027',
        };

        return [

            'station_id' => $station->id,

            'elemen_kerja' => $this->faker->randomElement([
                'Ambil Komponen',
                'Posisi Material',
                'Jahit Sisi',
                'Potong Benang',
                'Rapikan Jahitan',
                'Inspeksi Visual',
                'Ambil Alat',
                'Setel Material'
            ]),

            'durasi_detik' => $durasiPerCycle,

            'std_dev' => $stdDev,

            'cv_persen' => $cv,

            'frekuensi' => $frekuensi,

            'total_durasi' => $totalDurasi,

            'mean_per_kejadian' => $meanPerKejadian,

            'kategori_va' => $kategori,

            'warna_hex' => $warna,
        ];
    }
}
