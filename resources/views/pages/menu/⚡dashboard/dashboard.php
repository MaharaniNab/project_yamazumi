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
    public $taktTime;
    public $operators;
    public $target;

    public $stations = [];
    public $meanCT = [];
    public $robustCT = [];
    public $cvData = [];

    public $kpis = [];
    public $elements  = [];
    public $metrics = [];

    // chart comparison
    public $beforeData = [];
    public $afterData = [];


    public function mount()
    {
        $this->authorize('read_dashboard');
        if (!auth()->check() || !auth()->user()->isActive()) {
            abort(403, 'Akun Anda tidak aktif.');
        }

        $job = AnalysisJob::with([
            'stations.workElements',
            'simulations.stations'
        ])->latest()->first();

        if (!$job)
            return;

        $this->initJobData($job);
        $stations = $this->loadStations($job);
        $this->prepareKpis($job);
        $this->loadBottleneck($stations);
        $this->loadMetrics($job);
        $this->loadChartData($job, $stations);
    }

    private function initJobData($job)
    {
        $this->taktTime = $job->takt_time;
        $this->operators = $job->n_stations;
        $this->target = $job->output_harian;
    }

    private function loadStations($job)
    {
        $stations = $job->stations->sortBy('station_order');

        $this->stations = $stations->pluck('station_name')->toArray();
        $this->meanCT = $stations->pluck('mean_ct')->toArray();
        $this->robustCT = $stations->pluck('robust_ct')->toArray();
        $this->cvData = $stations->pluck('cv_persen')->toArray();

        return $stations;
    }

    private function prepareKpis($job)
    {
        $this->kpis = [
            [
                'label' => 'Line Efficiency',
                'value' => $job->line_efficiency,
                'target' => 75,
                'unit' => '%',
                'direction' => 'higher',
                'accent' => '#3D7A99'
            ],
            [
                'label' => 'Balance Delay',
                'value' => $job->balance_delay,
                'target' => 15,
                'unit' => '%',
                'direction' => 'lower',
                'accent' => '#2C8C83'
            ],
            [
                'label' => 'Smoothness Index',
                'value' => $job->smoothness_index,
                'target' => 40,
                'unit' => '',
                'direction' => 'lower',
                'accent' => '#FA6868'
            ],
            [
                'label' => 'Output Aktual/Hari',
                'value' => $job->line_output_hari,
                'target' => $this->target,
                'unit' => ' pcs',
                'direction' => 'higher',
                'accent' => '#312E81'
            ]
        ];
    }

    private function loadBottleneck($stations)
    {
        $bottleneck = $stations->sortByDesc('mean_ct')->first();

        if ($bottleneck) {
            $this->elements  = $bottleneck->workElements;
        }
    }

    private function loadMetrics($job)
    {
        $simulation = $job->simulations->first();

        if (!$simulation) {
            $this->metrics = [];
            return;
        }

        $lineEfficiencyDiff = $simulation->le_after - $simulation->le_before;
        $balanceDelayDiff = $simulation->bd_after - $simulation->bd_before;
        $outputDiff = $job->line_output_hari - $job->output_harian;

        $metrics = [
            [
                'label' => 'Line Efficiency',
                'before' => $simulation->le_before,
                'after' => $simulation->le_after,
                'delta' => $lineEfficiencyDiff,
            ],
            [
                'label' => 'Balance Delay',
                'before' => $simulation->bd_before,
                'after' => $simulation->bd_after,
                'delta' => $balanceDelayDiff,
            ],
            [
                'label' => 'Output / Hari',
                'before' => $job->output_harian,
                'after' => $job->line_output_hari,
                'delta' => $outputDiff,
            ],
        ];

        $this->metrics = collect($metrics)->map(function ($m) {

            $diff = $m['delta'];

            if ($m['label'] === 'Output / Hari') {
                $m['before'] .= ' pcs';
                $m['after'] .= ' pcs';
                $m['delta'] .= ' pcs';
            } else {
                $m['before'] = number_format($m['before'], 2) . '%';
                $m['after'] = number_format($m['after'], 2) . '%';
                $m['delta'] = number_format($diff, 2) . '%';
            }

            if ($m['label'] === 'Balance Delay') {
                $m['icon'] = $diff <= 0 ? 'arrow-down' : 'arrow-up';
                $m['color'] = $diff <= 0 ? 'green' : 'red';
            } else {
                $m['icon'] = $diff >= 0 ? 'arrow-up' : 'arrow-down';
                $m['color'] = $diff >= 0 ? 'green' : 'red';
            }

            return $m;

        })->toArray();
    }

    private function loadChartData($job, $stations)
    {
        $simulation = $job->simulations->first();
        if (!$simulation) {
            return;
        }

        $stationsAfter = $simulation?->stations ?? collect();
        $this->beforeData = $stations
            ->pluck('mean_ct')
            ->map(fn($v) => round($v, 2))
            ->toArray();

        $this->afterData = $stationsAfter
            ->pluck('mean_ct_after')
            ->map(fn($v) => round($v, 2))
            ->toArray();
    }

};