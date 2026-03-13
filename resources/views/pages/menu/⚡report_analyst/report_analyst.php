<?php

use App\Exports\LineAnalysisExport;
use App\Models\AnalysisJob;
use App\Models\SimulationResult;
use App\Models\SimulationStation;
use App\Models\StationResult;
use App\Models\WorkElement;
use Livewire\Attributes\Title;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

new
    #[Title('Report Analyst')] class extends Component {
    public $taktTime;
    public $n_stations;
    public $operators;
    public $target;

    public $stations = [];
    public $meanCT = [];
    public $robustCT = [];
    public $cvData = [];

    public $kpis = [];
    public $elements = [];
    public $metrics = [];
    public $elementsData = [];
    // Bottleneck + CV
    public $bottleneckCount = 0;
    public $maxCV = 0;
    public $selectedStation = null;

    public function mount()
    {
        // Ambil job terakhir
        $job = AnalysisJob::latest()->first();
        if (!$job)
            return;

        $this->taktTime = $job->takt_time;
        $this->operators = $job->n_stations;
        $this->target = $job->output_harian;
        $this->n_stations = $job->n_stations;

        // Ambil data stasiun langsung dari DB
        $stations = StationResult::where('job_id', $job->id)
            ->orderBy('station_order')
            ->get();

        $this->stations = $stations->pluck('station_name')->toArray();
        $this->meanCT = $stations->pluck('mean_ct')->toArray();
        $this->robustCT = $stations->pluck('robust_ct')->toArray();
        $this->cvData = $stations->pluck('cv_persen')->toArray();

        // ================= KPI =================
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

        // ================= BOTTLENECK & MAX CV =================
        $this->bottleneckCount = $stations->where('status_station', 'Bottleneck')->count();
        $this->maxCV = $stations->max('cv_persen');

        // Ambil semua work elements per stasiun
        $this->elementsData = [];
        foreach ($stations as $station) {
            $elements = WorkElement::where('station_id', $station->id)
                ->get();
            $this->elementsData[$station->station_name] = $elements;
        }
    }

    public function export()
    {
        $data = [
            'kpis' => $this->kpis,
            'stations' => $this->stations,
            'meanCT' => $this->meanCT,
            'robustCT' => $this->robustCT,
            'cvData' => $this->cvData,
            'elements' => WorkElement::all()
        ];

        return Excel::download(
            new LineAnalysisExport($data),
            'line_analysis.xlsx'
        );
    }

    public function startSimulation()
    {
        return redirect()->route('menu.simulation');
    }
};