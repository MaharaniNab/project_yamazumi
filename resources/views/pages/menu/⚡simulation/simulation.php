<?php

use App\Models\AnalysisJob;
use App\Models\SimulationResult;
use App\Models\SimulationStation;
use App\Models\SimulationAction;
use App\Models\StationResult;
use App\Models\WorkElement;
use Livewire\Attributes\Computed;
use Livewire\Component;

new
    #[Title('Simulation')]
    class extends Component {

    public $simulation;
    public $job;

    // Properti dasar
    public $taktTime;
    public $station;
    public $target;
    public $operators;
    public $z_score;
    public $mpAktual;
    public $totalSavingNva;
    public $leSesudah;
    public $mpBalance;
    public $mpAssigned;


    public function mount()
    {
        $this->job = AnalysisJob::latest()->first();
        if (!$this->job)
            return;

        $this->taktTime = $this->job->takt_time;
        $this->station = $this->job->station;
        $this->target = $this->job->target_output ?? 0;
        $this->operators = $this->job->operators ?? 0;
        $this->z_score = $this->job->z_score_used ?? 0;

        $this->simulation = SimulationResult::where('job_id', $this->job->id)->first();
        $st = $this->simulation;
        $this->totalSavingNva = $st->total_saving_nva;
        $this->leSesudah = $st->le_after;
        $this->mpBalance = $st->overall_mp_balance;
        $this->mpAktual = $st->mp_aktual_input;
        $this->mpBalance = $st->overall_mp_balance;
        // ambil semua station untuk hitung total assigned
        $stations = SimulationStation::where('simulation_id', $st->id)->get();
        $this->mpAssigned = $stations->sum('mp_assigned');


        // dd($this->elementsData());
    }

    #[Computed]
    public function kpis(): array
    {
        if (!$this->simulation)
            return [];

        return [
            [
                'label' => 'Total Saving NVA',
                'value' => number_format($this->simulation->total_saving_nva, 1),
                'unit' => 's',
                'color' => 'amber',
            ],
            [
                'label' => 'LE Sesudah',
                'value' => number_format($this->simulation->le_after, 1),
                'unit' => '%',
                'color' => 'emerald',
            ],
            [
                'label' => 'MP Balance',
                'value' => number_format($this->simulation->overall_mp_balance, 1),
                'unit' => '%',
                'color' => 'blue',
            ],
            [
                'label' => 'MP Aktual Input',
                'value' => $this->simulation->mp_aktual_input,
                'unit' => 'Op',
                'color' => 'slate',
            ],
        ];
    }

    #[Computed]
    public function balancing(): array
    {
        if (!$this->simulation) {
            return [];
        }

        // Ambil semua stasiun dari simulasi
        $stations = SimulationStation::where('simulation_id', $this->simulation->id)->get();
        $this->mpAssigned = $stations->sum('mp_assigned');


        return [
            [
                'label' => 'MP Aktual',
                'value' => $this->mpAktual,
                'unit' => 'Op',
                'color' => 'red',
                'note' => 'input pengguna',
            ],
            [
                'label' => 'Op. Teoritis',
                'value' => number_format($this->job->op_teoritis, 1),
                'unit' => '',
                'color' => 'green',
                'note' => 'min. ' . $this->mpAktual . ' operator',
            ],
            [
                'label' => 'Total Assigned',
                'value' => $this->mpAssigned,
                'unit' => 'Op',
                'color' => 'cyan',
                'note' => 'dari constraint',
            ],
            [
                'label' => 'Overall MP Bal.',
                'value' => number_format($this->simulation->overall_mp_balance, 1),
                'unit' => '%',
                'color' => 'slate',
                'note' => 'Σ CT / (Σ MP × Takt)',
            ],
        ];
    }

    #[Computed]
    public function metrics(): array
    {
        if (!$this->simulation || !$this->job)
            return [];

        $st = $this->simulation;
        $job = $this->job;

        return [
            ['label' => 'Line Efficiency', 'before' => round($st->le_before, 2) . '%', 'after' => round($st->le_after, 2) . '%'],
            ['label' => 'Balance Delay', 'before' => round($st->bd_before, 2) . '%', 'after' => round($st->bd_after, 2) . '%'],
            ['label' => 'Smoothness Index', 'before' => round($st->si_before, 2), 'after' => round($st->si_after, 2)],
            ['label' => 'Neck Time (Mean)', 'before' => round($st->neck_before, 1) . 's', 'after' => round($st->neck_after, 1) . 's'],
            ['label' => 'Total NVA Saving', 'before' => '', 'after' => $st->total_saving_nva . 's'],
            ['label' => 'Op. Teoritis', 'before' => '', 'after' => round($st->op_teoritis_after, 1)],
            ['label' => 'Overall MP Balance', 'before' => '', 'after' => round($st->overall_mp_balance, 1) . '%'],
            ['label' => 'Output / Hari', 'before' => round($job->output_harian, 1) . 'pcs', 'after' => round($job->line_output_hari, 1) . 'pcs'],
        ];
    }

    #[Computed]
    public function chartData(): array
    {
        if (!$this->simulation || !$this->job)
            return [];

        $stationsBefore = StationResult::where('job_id', $this->job->id)->orderBy('station_order')->get();
        $stationsAfter = SimulationStation::where('simulation_id', $this->simulation->id)->orderBy('station_name')->get();

        return [
            'stations' => $stationsBefore->pluck('station_name')->toArray(),
            'beforeData' => $stationsBefore->pluck('mean_ct')->map(fn($v) => round($v, 2))->toArray(),
            'afterData' => $stationsAfter->pluck('mean_ct_after')->map(fn($v) => round($v, 2))->toArray(),
        ];
    }
    #[Computed]
    public function kaizen()
    {
        if (!$this->simulation) {
            return collect();
        }

        return SimulationAction::where('simulation_id', $this->simulation->id)
            ->orderBy('priority_order')
            ->get()
            ->map(fn($a) => [
                'priority' => $a->priority_order,
                'station' => $a->station_from,
                'status' => $a->status_stasiun,
                'cv' => $a->cv_stasiun,
                'task' => $a->elemen_kerja,
                'kategori' => $a->kategori_va,
                'before' => round($a->durasi_before, 1),
                'after' => round($a->durasi_after, 1),
                'saving' => round($a->saving, 1),
                'pct' => $a->pct_reduksi,
                'metode' => $a->metode,
            ]);
    }

    #[Computed]
    public function elementsData(): array
    {
        if (!$this->simulation) {
            return [];
        }

        $stations = SimulationStation::where('simulation_id', $this->simulation->id)->get();

        return $stations->map(fn($s) => [
            'station_name' => $s->station_name,
            'ct_before' => $s->mean_ct_before,
            'ct_after' => $s->mean_ct_after,
            'mp_assigned' => $s->mp_assigned,
            'ct_efektif' => $s->ct_efektif,
            'vs_takt' => $s->ct_efektif - $this->taktTime,
            'status' => $s->kaizen_result,
            'mp_utilized' => $s->mp_utilized,
            'mp_balance_pct' => $s->mp_balance_pct,
            'nvaPctBefore' => $s->nva_pct_before,
            'nvaDOM' => $s->is_nva_dominant,
        ])->toArray();
    }
};