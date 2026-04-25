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

    public $stations  = [];
    public $meanCT    = [];
    public $robustCT  = [];
    public $cvData    = [];

    public $kpis      = [];
    public $elements  = [];
    public $metrics   = [];

    public $beforeData = [];
    public $afterData  = [];

    public function mount()
    {
        // Ganti authorize dengan pengecekan manual agar tidak crash kalau permission belum di-seed
        if (!auth()->check()) {
            abort(403);
        }
        if (!auth()->user()->is_active) {
            abort(403, 'Akun Anda tidak aktif.');
        }

        $job = AnalysisJob::with([
            'stations.workElements',
            'simulations.stations'
        ])->where('status', 'completed')->latest()->first();

        if (!$job) return;

        $this->initJobData($job);
        $stations = $this->loadStations($job);
        $this->prepareKpis($job);
        $this->loadBottleneck($stations);
        $this->loadMetrics($job);
        $this->loadChartData($job, $stations);
    }

    private function initJobData($job)
    {
        $this->taktTime  = $job->takt_time;
        $this->operators = $job->n_stations;
        $this->target    = $job->output_harian;
    }

    private function loadStations($job)
    {
        $stations = $job->stations->sortBy('station_order');

        $this->stations  = $stations->pluck('station_name')->toArray();
        $this->meanCT    = $stations->pluck('mean_ct')->toArray();
        $this->robustCT  = $stations->pluck('robust_ct')->toArray();
        $this->cvData    = $stations->pluck('cv_persen')->toArray();

        return $stations;
    }

    private function prepareKpis($job)
    {
        $this->kpis = [
            [
                'label'     => 'Line Efficiency',
                'value'     => $job->line_efficiency ?? 0,
                'target'    => 75,
                'unit'      => '%',
                'direction' => 'higher',
                'accent'    => '#3D7A99'
            ],
            [
                'label'     => 'Balance Delay',
                'value'     => $job->balance_delay ?? 0,
                'target'    => 15,
                'unit'      => '%',
                'direction' => 'lower',
                'accent'    => '#2C8C83'
            ],
            [
                'label'     => 'Smoothness Index',
                'value'     => $job->smoothness_index ?? 0,
                'target'    => 40,
                'unit'      => '',
                'direction' => 'lower',
                'accent'    => '#FA6868'
            ],
            [
                'label'     => 'Output Aktual/Hari',
                'value'     => $job->line_output_hari ?? 0,
                'target'    => $this->target,
                'unit'      => ' pcs',
                'direction' => 'higher',
                'accent'    => '#312E81'
            ]
        ];
    }

    private function loadBottleneck($stations)
    {
        $bottleneck = $stations->sortByDesc('mean_ct')->first();
        if ($bottleneck) {
            $this->elements = $bottleneck->workElements ?? collect();
        }
    }

    private function loadMetrics($job)
    {
        $simulation = $job->simulations->first();

        if (!$simulation) {
            $this->metrics = [];
            return;
        }

        $lineEfficiencyDiff = ($simulation->le_after ?? 0) - ($simulation->le_before ?? 0);
        $balanceDelayDiff   = ($simulation->bd_after ?? 0) - ($simulation->bd_before ?? 0);
        $outputDiff         = ($job->line_output_hari ?? 0) - ($job->output_harian ?? 0);

        $metrics = [
            [
                'label'  => 'Line Efficiency',
                'before' => $simulation->le_before ?? 0,
                'after'  => $simulation->le_after  ?? 0,
                'delta'  => $lineEfficiencyDiff,
            ],
            [
                'label'  => 'Balance Delay',
                'before' => $simulation->bd_before ?? 0,
                'after'  => $simulation->bd_after  ?? 0,
                'delta'  => $balanceDelayDiff,
            ],
            [
                'label'  => 'Output / Hari',
                'before' => $job->output_harian    ?? 0,
                'after'  => $job->line_output_hari ?? 0,
                'delta'  => $outputDiff,
            ],
        ];

        $this->metrics = collect($metrics)->map(function ($m) {
            $diff = $m['delta'];

            if ($m['label'] === 'Output / Hari') {
                $m['before'] .= ' pcs';
                $m['after']  .= ' pcs';
                $m['delta']  .= ' pcs';
            } else {
                $m['before'] = number_format($m['before'], 2) . '%';
                $m['after']  = number_format($m['after'],  2) . '%';
                $m['delta']  = number_format($diff,        2) . '%';
            }

            if ($m['label'] === 'Balance Delay') {
                $m['icon']  = $diff <= 0 ? 'arrow-down' : 'arrow-up';
                $m['color'] = $diff <= 0 ? 'green' : 'red';
            } else {
                $m['icon']  = $diff >= 0 ? 'arrow-up' : 'arrow-down';
                $m['color'] = $diff >= 0 ? 'green' : 'red';
            }

            return $m;
        })->toArray();
    }

    private function loadChartData($job, $stations)
    {
        $simulation = $job->simulations->first();
        if (!$simulation) return;

        $stationsAfter = $simulation->stations ?? collect();

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
