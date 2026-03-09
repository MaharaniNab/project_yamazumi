<?php

namespace Database\Seeders;

use App\Models\AnalysisJob;
use App\Models\GroundTruth;
use App\Models\RawSegment;
use App\Models\SimulationAction;
use App\Models\SimulationResult;
use App\Models\SimulationStation;
use App\Models\StationResult;
use App\Models\TemporalIoUResult;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\WorkElement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{

    public function run(): void
    {
        $birth = '2004-11-09';
        $birthAditya = '2000-04-10';

        User::factory()->create([
            'name' => 'Maharani',
            'email' => 'maharani@fukuryo.co.id',
            'birth_date' => $birth,
            'password' => Hash::make(
                Carbon::parse($birth)->format('dmY')
            ),
        ]);

        User::factory()->create([
            'name' => 'Aditya',
            'email' => 'aditya@fukuryo.co.id',
            'birth_date' => $birthAditya,
            'password' => Hash::make(
                Carbon::parse($birth)->format('dmY')
            ),
        ]);

        $users = User::factory()
            ->count(10)
            ->state(fn() => [
                'name' => fake('id_ID')->name(),
            ])
            ->create();

        foreach ($users as $user) {

            // 1 user = 1 job
            $job = AnalysisJob::factory()->create([
                'user_id' => $user->id,
            ]);

            // jumlah station realistis
            $stationCount = fake()->numberBetween(4, 7);

            for ($i = 1; $i <= $stationCount; $i++) {

                $station = StationResult::factory()->create([
                    'job_id' => $job->id,
                    'station_order' => $i
                ]);

                // Work Element
                $elements = WorkElement::factory()
                    ->count(fake()->numberBetween(2, 4))
                    ->create([
                        'station_id' => $station->id
                    ]);

                // Raw Segment mengikuti element
                foreach ($elements as $element) {

                    $segment = RawSegment::factory()->create([
                        'station_id' => $station->id
                    ]);

                    GroundTruth::factory()
                        ->count(2)
                        ->create([
                            'station_id' => $station->id,
                            'raw_id' => $segment->id,
                            'user_id' => $user->id
                        ]);
                }

                TemporalIoUResult::factory()
                    ->count(2)
                    ->create([
                        'station_id' => $station->id
                    ]);
            }

            // SIMULATION
            $simulations = SimulationResult::factory()
                ->count(2)
                ->create([
                    'job_id' => $job->id
                ]);

            foreach ($simulations as $simulation) {

                $stations = StationResult::where('job_id', $job->id)->get();

                foreach ($stations as $station) {

                    SimulationStation::factory()->create([
                        'simulation_id' => $simulation->id,
                    ]);

                    SimulationAction::factory()
                        ->count(3)
                        ->create([
                            'simulation_id' => $simulation->id,
                        ]);
                }
            }
        }
        // $jobs = $users->map(fn($user) => AnalysisJob::factory()->create([
        //     'user_id' => $user->id,
        // ]));

        // // StationResult + anak-anaknya
        // $jobs->each(function ($job) {
        //     $stations = StationResult::factory()->count(5)->create([
        //         'job_id' => $job->id,
        //     ]);

        //     $stations->each(function ($station) {
        //         WorkElement::factory()->count(3)->create(['station_id' => $station->id]);
        //         RawSegment::factory()->count(3)->create(['station_id' => $station->id]);
        //         GroundTruth::factory()->count(2)->create(['station_id' => $station->id]);
        //         TemporalIoUResult::factory()->count(2)->create(['station_id' => $station->id]);
        //     });

        //     // SimulationResult + anak-anaknya
        //     $simulations = SimulationResult::factory()->count(2)->create([
        //         'job_id' => $job->id,
        //     ]);

        //     $simulations->each(function ($simulation) {
        //         $simStations = SimulationStation::factory()->count(3)->create([
        //             'simulation_id' => $simulation->id,
        //         ]);

        //         $simStations->each(function ($simStation) use ($simulation) {
        //             SimulationAction::factory()->count(4)->create([
        //                 'simulation_id' => $simulation->id,
        //             ]);
        //         });
        //     });
        // });

        $this->call([
            MenuSeeder::class,
            PermissionSeeder::class,
            MenuPermissionSeeder::class,
        ]);
    }
}
