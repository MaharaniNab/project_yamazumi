<?php

namespace Database\Factories;

use App\Models\RawSegment;
use App\Models\StationResult;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GroundTruth>
 */
class GroundTruthFactory extends Factory
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
    //         'station_id' =>StationResult::inRandomOrder()->first()->id,
    //         'user_id' => User::inRandomOrder()->first()->id,
    //         'raw_id'=> $this->faker->randomElement(RawSegment::all())->id,
    //         'input_at' => now(),
    //         'start_time' => $start,
    //         'end_time' => $end,
    //         'duration' => $duration,
    //         'catatan' => $this->faker->sentence(),
    //     ];

    // }
    public function definition(): array
    {
        $raw = RawSegment::inRandomOrder()->first();
        $station = $raw->station;
        $user = User::inRandomOrder()->first();

        // deviasi anotasi manusia (±0.2 detik)
        $start = max(0, $raw->start_time + $this->faker->randomFloat(3, -0.2, 0.2));
        $end = $raw->end_time + $this->faker->randomFloat(3, -0.2, 0.2);

        // pastikan end > start
        if ($end <= $start) {
            $end = $start + 0.1;
        }

        $duration = $end - $start;

        return [

            'station_id' => $station->id,
            'user_id' => $user->id,
            'raw_id' => $raw->id,
            'input_at' => now()->subMinutes(rand(1, 120)),
            'start_time' => $start,
            'end_time' => $end,
            'duration' => $duration,
            'catatan' => $this->faker->optional()->randomElement([
                'Perlu dicek ulang frame',
                'Gerakan operator kurang jelas',
                'Overlap aktivitas',
                'Ok',
                'Frame blur'
            ]),
        ];
    }
}
