<?php

use App\Models\AnalysisJob;
use App\Models\SimulationResult;
use App\Models\SimulationStation;
use App\Models\StationResult;
use App\Models\WorkElement;
use Livewire\Attributes\Title;
use Livewire\Component;

new
    #[Title('Dashboard')] class extends Component {
    //  public $taktTime = 216;

    // public $stations = [
    //     'Jahit Pasang Lengan',
    //     'Jahit Panjang Facing',
    //     'Obras Tepi Kain',
    //     'Pasang Furing',
    //     'Setrika Pressing',
    //     'QC & Finishing',
    // ];

    // public $meanCT = [248.5, 235.0, 192.0, 228.0, 175.0, 140.0];

    // public $robustCT = [262.0, 249.5, 205.0, 241.0, 182.0, 150.0];
    public $taktTime;
    public $operators;
    public $target;

    public $stations = [];
    public $meanCT = [];
    public $robustCT = [];
    public $cvData = [];

    public $kpis = [];
    public $elements = [];
    public $metrics = [];

    // chart comparison
    public $beforeData = [];
    public $afterData = [];


    public function mount()
    {
        $job = AnalysisJob::latest()->first();

        if (!$job)
            return;

        $this->taktTime = $job->takt_time;
        $this->operators = $job->n_stations;
        $this->target = $job->output_harian;

        $stations = StationResult::where('job_id', $job->id)
            ->orderBy('station_order')
            ->get();

        $this->stations = $stations->pluck('station_name')->toArray();
        $this->meanCT = $stations->pluck('mean_ct')->toArray();
        $this->robustCT = $stations->pluck('robust_ct')->toArray();
        $this->cvData = $stations->pluck('cv_persen')->toArray();


        // ================= KPI =================

        $lineEfficiency = $job->line_efficiency;
        $balanceDelay = $job->balance_delay;
        $smoothness = $job->smoothness_index;
        $outputActual = $job->line_output_hari;

        $this->kpis = [

            [
                'label' => 'Line Efficiency',
                'value' => $lineEfficiency,
                'target' => 75,
                'unit' => '%',
                'direction' => 'higher',
                'accent' => '#3D7A99'
            ],

            [
                'label' => 'Balance Delay',
                'value' => $balanceDelay,
                'target' => 15,
                'unit' => '%',
                'direction' => 'lower',
                'accent' => '#2C8C83'
            ],

            [
                'label' => 'Smoothness Index',
                'value' => $smoothness,
                'target' => 40,
                'unit' => '',
                'direction' => 'lower',
                'accent' => '#FA6868'
            ],

            [
                'label' => 'Output Aktual/Hari',
                'value' => $outputActual,
                'target' => $this->target,
                'unit' => ' pcs',
                'direction' => 'higher',
                'accent' => '#312E81'
            ]

        ];


        // ================= BOTTLENECK =================

        $bottleneck = $stations->sortByDesc('mean_ct')->first();

        if ($bottleneck) {

            $this->elements = WorkElement::where('station_id', $bottleneck->id)
                ->get();

        }


        // ================= COMPARISON METRIC =================

        $this->metrics = [

            [
                'label' => 'Line Efficiency',
                'before' => $job->before_efficiency,
                'after' => $job->line_efficiency,
                'delta' => round($job->line_efficiency - $job->before_efficiency, 1) . '%',
                'icon' => 'arrow-up'
            ],

            [
                'label' => 'Balance Delay',
                'before' => $job->before_balance_delay,
                'after' => $job->balance_delay,
                'delta' => round($job->before_balance_delay - $job->balance_delay, 1) . '%',
                'icon' => 'arrow-down'
            ],

            [
                'label' => 'Output / Hari',
                'before' => $job->before_output,
                'after' => $job->line_output_hari,
                'delta' => ($job->line_output_hari - $job->before_output) . ' pcs',
                'icon' => 'arrow-up'
            ]

        ];


        // ================= DATA UNTUK CHART =================

        // $this->beforeData = [
        //     $job->before_efficiency,
        //     $job->before_balance_delay,
        //     $job->before_output
        // ];

        // $this->afterData = [
        //     $job->line_efficiency,
        //     $job->balance_delay,
        //     $job->line_output_hari
        // ];
        $simulation = SimulationResult::where('job_id', $job->id)->first();
        $stationsBefore = StationResult::where('job_id', $job->id)
            ->orderBy('station_order')
            ->get();

        $stationsAfter = SimulationStation::where('simulation_id', $simulation->id)
            ->get();

        $this->stations = $stationsBefore->pluck('station_name')->toArray();

        $this->beforeData = $stationsBefore
            ->pluck('mean_ct')
            ->map(fn($v) => round($v, 2))
            ->toArray();

        $this->afterData = $stationsAfter
            ->pluck('mean_ct_after')
            ->map(fn($v) => round($v, 2))
            ->toArray();

    }
};