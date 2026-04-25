<?php

use App\Exports\SimulationExport;
use App\Models\AnalysisJob;
use App\Models\SimulationResult;
use App\Models\SimulationStation;
use App\Models\SimulationAction;
use App\Models\StationResult;
use App\Models\WorkElement;
use App\Services\PythonApiService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

new
    #[Title('Simulation')]
    class extends Component {

    public $simulation;
    public $job;
    public $simulationStations;

    public $taktTime;
    public $mpAktual;
    public $totalSavingNva;
    public $leSesudah;
    public $mpBalance;
    public $mpAssigned;

    // Status simulasi
    public $isRunning       = false;
    public $runningMessage  = 'Menjalankan simulasi...';

    public function mount()
    {
        $this->job = AnalysisJob::where('status', 'completed')->latest()->first();
        if (!$this->job) return;

        $this->taktTime = $this->job->takt_time;

        $this->simulation = SimulationResult::where('job_id', $this->job->id)->first();

        // Kalau simulasi belum ada, jangan crash
        if (!$this->simulation) {
            $this->simulationStations = collect();
            return;
        }

        $st = $this->simulation;
        $this->totalSavingNva     = $st->total_saving_nva;
        $this->leSesudah          = $st->le_after;
        $this->mpBalance          = $st->overall_mp_balance;
        $this->mpAktual           = $st->mp_aktual_input;
        $this->simulationStations = SimulationStation::where('simulation_id', $st->id)->get();
        $this->mpAssigned         = $this->simulationStations->sum('mp_assigned');
    }

    /**
     * Jalankan simulasi via Flask API, lalu simpan hasilnya ke DB.
     */
    public function runSimulation()
    {
        if (!$this->job) return;

        $this->isRunning = true;

        try {
            $api    = new PythonApiService();
            $result = $api->runSimulation($this->job->python_job_id);

            $this->saveSimulationToDb($result);

            // Reload data
            $this->simulation         = SimulationResult::where('job_id', $this->job->id)->first();
            $this->simulationStations = SimulationStation::where('simulation_id', $this->simulation->id)->get();
            $this->mpAssigned         = $this->simulationStations->sum('mp_assigned');
            $this->isRunning          = false;

            $this->dispatch('swal-toast', icon: 'success', title: 'Selesai', text: 'Simulasi berhasil dijalankan!');

        } catch (\Exception $e) {
            $this->isRunning = false;
            $this->dispatch('swal-toast', icon: 'error', title: 'Error', text: $e->getMessage());
        }
    }

    private function saveSimulationToDb(array $result): void
    {
        // Hapus simulasi lama kalau ada
        SimulationResult::where('job_id', $this->job->id)->delete();

        $simSum    = $result['simulated_summary']    ?? [];
        $kaizenLog = $result['kaizen_log']            ?? [];
        $mpBal     = $result['mp_balancing']          ?? [];
        $comp      = $result['new_summary_comparison'] ?? [];

        // Helper parse
        $parse = fn($val, $suffix) => floatval(str_replace($suffix, '', $val ?? '0'));

        $simulation = SimulationResult::create([
            'job_id'             => $this->job->id,
            'user_id'            => Auth::id(),
            'mp_aktual_input'    => $simSum['MP Aktual Input']      ?? $this->job->mp_aktual,
            'nva_elimination_pct'=> 1.00,
            'le_before'          => $parse($comp['Line Efficiency'][0]  ?? '0%', '%'),
            'bd_before'          => $parse($comp['Balance Delay'][0]    ?? '0%', '%'),
            'si_before'          => $parse($comp['Total Cycle Time'][0] ?? '0s', 's'),
            'neck_before'        => $parse($comp['Neck Time'][0]        ?? '0s', 's'),
            'le_after'           => $parse($simSum['Presentase Line Balance'] ?? '0%', '%'),
            'bd_after'           => $parse($simSum['Balance Delay']           ?? '0%', '%'),
            'si_after'           => $parse($simSum['Smoothness Index']        ?? '0', ''),
            'neck_after'         => $parse($simSum['Neck Time']               ?? '0s', 's'),
            'total_saving_nva'   => $parse($simSum['Total Saving NVA']        ?? '0s', 's'),
            'n_kaizen_actions'   => count($kaizenLog),
            'op_teoritis_after'  => $simSum['Op. Teoritis']            ?? 0,
            'overall_mp_balance' => $parse($simSum['Overall MP Balance'] ?? '0%', '%'),
        ]);

        // Simpan SimulationStation
        $simResults = $result['simulated_results'] ?? [];
        $simPerf    = $result['simulated_station_performance'] ?? [];

        foreach ($mpBal as $stationName => $mpData) {
            $statusBefore = StationResult::where('job_id', $this->job->id)
                ->where('station_name', $stationName)
                ->value('status_station') ?? 'Balanced';

            $validStatuses = ['Bottleneck', 'At-Risk', 'Balanced', 'Underloaded'];
            $statusAfter   = $mpData['ct_after'] > $this->taktTime ? 'Bottleneck' : 'Balanced';

            SimulationStation::create([
                'simulation_id'   => $simulation->id,
                'station_name'    => $stationName,
                'mean_ct_before'  => StationResult::where('job_id', $this->job->id)->where('station_name', $stationName)->value('mean_ct') ?? 0,
                'mean_ct_after'   => $mpData['ct_after']    ?? 0,
                'mp_assigned'     => $mpData['mp_assigned'] ?? 1,
                'ct_efektif'      => $mpData['ct_efektif']  ?? 0,
                'mp_balance_pct'  => $mpData['mp_balance_pct'] ?? 0,
                'nva_pct_before'  => ($mpData['nva_pct'] ?? 0) / 100,
                'is_nva_dominant' => $mpData['is_nva_dominant'] ?? false,
                'status_before'   => in_array($statusBefore, $validStatuses) ? $statusBefore : 'Balanced',
                'status_after'    => $statusAfter,
                'kaizen_result'   => $mpData['ct_efektif'] <= $this->taktTime ? 'Resolved' : 'Still Bottleneck',
                'mp_utilized'     => $mpData['utilized'] ?? 'Baik',
            ]);
        }

        // Simpan SimulationAction (kaizen log)
        foreach ($kaizenLog as $k) {
            SimulationAction::create([
                'simulation_id'  => $simulation->id,
                'priority_order' => $k['priority']    ?? 0,
                'action_type'    => 'nva_elimination',
                'station_from'   => $k['station']     ?? '',
                'status_stasiun' => in_array($k['status'] ?? '', ['Bottleneck','At-Risk','Balanced','Underloaded'])
                    ? $k['status'] : 'Balanced',
                'cv_stasiun'     => $k['cv_persen']   ?? 0,
                'elemen_kerja'   => $k['elemen']      ?? '',
                'kategori_va'    => match($k['kategori'] ?? '') {
                    'Value-Added'                   => 'VA',
                    'Necessary but Non-Value-Added' => 'N-NVA',
                    default                         => 'NVA',
                },
                'durasi_before'  => $k['dur_before']  ?? 0,
                'durasi_after'   => $k['dur_after']   ?? 0,
                'saving'         => $k['saving']      ?? 0,
                'pct_reduksi'    => $k['pct']         ?? '',
                'metode'         => $k['metode']      ?? '',
            ]);
        }

        // Update AnalysisJob op_teoritis
        $this->job->update([
            'op_teoritis' => $simSum['Op. Teoritis'] ?? null,
        ]);
    }

    #[Computed]
    public function kpis(): array
    {
        if (!$this->simulation) return [];

        return [
            ['label' => 'Total Saving NVA', 'value' => number_format($this->simulation->total_saving_nva, 1), 'unit' => 's',   'color' => 'amber'],
            ['label' => 'LE Sesudah',        'value' => number_format($this->simulation->le_after, 1),          'unit' => '%',  'color' => 'emerald'],
            ['label' => 'MP Balance',         'value' => number_format($this->simulation->overall_mp_balance, 1),'unit' => '%',  'color' => 'blue'],
            ['label' => 'MP Aktual Input',    'value' => $this->simulation->mp_aktual_input,                     'unit' => 'Op', 'color' => 'slate'],
        ];
    }

    #[Computed]
    public function balancing(): array
    {
        if (!$this->simulation) return [];

        return [
            ['label' => 'MP Aktual',      'value' => $this->mpAktual,                                           'unit' => 'Op', 'color' => 'amber',  'note' => 'input pengguna'],
            ['label' => 'Op. Teoritis',   'value' => number_format($this->job->op_teoritis ?? 0, 1),            'unit' => '',   'color' => 'green',  'note' => 'min. ' . $this->mpAktual . ' operator'],
            ['label' => 'Total Assigned', 'value' => $this->mpAssigned,                                         'unit' => 'Op', 'color' => 'cyan',   'note' => 'dari constraint'],
            ['label' => 'Overall MP Bal.','value' => number_format($this->simulation->overall_mp_balance ?? 0, 1),'unit' => '%', 'color' => 'slate', 'note' => 'Σ CT / (Σ MP × Takt)'],
        ];
    }

    #[Computed]
    public function metrics(): array
    {
        if (!$this->simulation || !$this->job) return [];

        $st  = $this->simulation;
        $job = $this->job;

        return [
            ['label' => 'Line Efficiency',   'before' => round($st->le_before  ?? 0, 2) . '%',   'after' => round($st->le_after   ?? 0, 2) . '%'],
            ['label' => 'Balance Delay',     'before' => round($st->bd_before  ?? 0, 2) . '%',   'after' => round($st->bd_after   ?? 0, 2) . '%'],
            ['label' => 'Smoothness Index',  'before' => round($st->si_before  ?? 0, 2),          'after' => round($st->si_after   ?? 0, 2)],
            ['label' => 'Neck Time (Mean)',  'before' => round($st->neck_before ?? 0, 1) . 's',   'after' => round($st->neck_after ?? 0, 1) . 's'],
            ['label' => 'Total NVA Saving',  'before' => '—',                                     'after' => ($st->total_saving_nva ?? 0) . 's'],
            ['label' => 'Op. Teoritis',      'before' => '—',                                     'after' => round($st->op_teoritis_after ?? 0, 1)],
            ['label' => 'Overall MP Balance','before' => '—',                                     'after' => round($st->overall_mp_balance ?? 0, 1) . '%'],
            ['label' => 'Output / Hari',     'before' => round($job->output_harian ?? 0, 1) . ' pcs', 'after' => round($job->line_output_hari ?? 0, 1) . ' pcs'],
        ];
    }

    #[Computed]
    public function chartData(): array
    {
        if (!$this->simulation || !$this->job) return [];

        $stationsBefore = StationResult::where('job_id', $this->job->id)->orderBy('station_order')->get();
        $stationsAfter  = $this->simulationStations->sortBy('station_name');

        return [
            'stations'   => $stationsBefore->pluck('station_name')->toArray(),
            'beforeData' => $stationsBefore->pluck('mean_ct')->map(fn($v) => round($v, 2))->toArray(),
            'afterData'  => $stationsAfter->pluck('mean_ct_after')->map(fn($v) => round($v, 2))->toArray(),
        ];
    }

    #[Computed]
    public function kaizen()
    {
        if (!$this->simulation) return collect();

        return SimulationAction::where('simulation_id', $this->simulation->id)
            ->orderBy('priority_order')
            ->get()
            ->map(function ($a) {
                $station = $this->simulationStations->firstWhere('station_name', $a->station_from);
                return [
                    'priority'     => $a->priority_order,
                    'elemen'       => $a->station_from,
                    'status'       => $a->status_stasiun,
                    'cv'           => $a->cv_stasiun,
                    'task'         => $a->elemen_kerja,
                    'kategori'     => $a->kategori_va,
                    'before'       => round($a->durasi_before, 1),
                    'after'        => round($a->durasi_after,  1),
                    'saving'       => round($a->saving,        1),
                    'pct'          => $a->pct_reduksi,
                    'nvaPct'       => round(($station?->nva_pct_before ?? 0) * 100, 1) . '%',
                    'nva_dominant' => $station?->is_nva_dominant ?? false,
                    'metode'       => $a->metode,
                ];
            });
    }

    #[Computed]
    public function elementsData(): array
    {
        if (!$this->simulation) return [];

        return $this->simulationStations->map(fn($s) => [
            'station_name'  => $s->station_name,
            'ct_before'     => $s->mean_ct_before,
            'ct_after'      => $s->mean_ct_after,
            'mp_assigned'   => $s->mp_assigned,
            'ct_efektif'    => $s->ct_efektif,
            'vs_takt'       => ($s->ct_efektif ?? 0) - ($this->taktTime ?? 0),
            'status'        => $s->kaizen_result,
            'mp_utilized'   => $s->mp_utilized,
            'mp_balance_pct'=> $s->mp_balance_pct,
            'nvaPctBefore'  => $s->nva_pct_before,
            'nvaDOM'        => $s->is_nva_dominant,
        ])->toArray();
    }

    public function exportExcel()
    {
        $data = [
            'kpis'         => $this->kpis,
            'metrics'      => $this->metrics,
            'chartData'    => $this->chartData,
            'elementsData' => $this->elementsData,
            'balancing'    => $this->balancing,
            'mpAktual'     => $this->mpAktual,
            'mpAssigned'   => $this->mpAssigned,
            'mpBalance'    => $this->mpBalance,
        ];

        return Excel::download(new SimulationExport($data), 'kaizen-balancing-report.xlsx');
    }
};
