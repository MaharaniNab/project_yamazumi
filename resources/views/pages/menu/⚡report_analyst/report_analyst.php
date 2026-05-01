<?php

use App\Exports\LineAnalysisExport;
use App\Models\AnalysisJob;
use App\Models\StationResult;
use App\Models\WorkElement;
use App\Services\PythonApiService;
use Livewire\Attributes\Title;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

new
    #[Title('Report Analyst')] class extends Component {

    public $taktTime;
    public $n_stations;
    public $operators;
    public $target;

    public $stations     = [];
    public $meanCT       = [];
    public $robustCT     = [];
    public $cvData       = [];
    public $kpis         = [];
    public $elements     = [];
    public $metrics      = [];
    public $elementsData = [];

    public $bottleneckCount   = 0;
    public $maxCV             = 0;
    public $selectedStation   = null;

    public $isProcessing      = false;
    public $processingMessage = 'Sedang menganalisis video...';

    public function mount()
    {
        $job = AnalysisJob::latest()->first();
        if (!$job) return;

        if ($job->status === 'processing') {
            $this->isProcessing = true;
            return;
        }

        if ($job->status === 'completed') {
            $this->loadFromDb($job);
        }
    }

    /**
     * Polling otomatis setiap 3 detik via wire:poll.
     */
    public function checkFlaskStatus()
    {
        if (!$this->isProcessing) return;

        $job = AnalysisJob::latest()->first();
        if (!$job || !$job->python_job_id) return;

        try {
            $api    = new PythonApiService();
            $result = $api->getResults($job->python_job_id);

            $status = $result['status'] ?? 'processing';

            if ($status === 'processing') {
                $this->processingMessage = 'Sedang menganalisis video... mohon tunggu.';
                return;
            }

            if ($status === 'failed') {
                $this->isProcessing = false;
                $job->update(['status' => 'failed']);
                $this->dispatch('swal-toast', icon: 'error', title: 'Gagal', text: 'Analisis gagal diproses oleh Flask.');
                return;
            }

            if ($status === 'completed') {
                $this->saveResultsToDb($job, $result);
                $this->isProcessing = false;
                $this->loadFromDb($job->fresh());
                $this->dispatch('swal-toast', icon: 'success', title: 'Selesai', text: 'Analisis berhasil diselesaikan!');
                $this->dispatch('results-ready');
            }

        } catch (\Exception $e) {
            // Tampilkan error ke user agar mudah debug
            Log::error('checkFlaskStatus error: ' . $e->getMessage());
            $this->isProcessing = false;
            $this->dispatch('swal-toast', icon: 'error', title: 'Error', text: substr($e->getMessage(), 0, 200));
        }
    }

    /**
     * Tombol manual — klik untuk cek status sekarang (fallback jika wire:poll belum dipasang).
     */
    public function manualCheck()
    {
        $this->isProcessing = true;
        $this->checkFlaskStatus();
    }

    /**
     * Simpan hasil dari Flask ke DB Laravel.
     */
    private function saveResultsToDb(AnalysisJob $job, array $result): void
    {
        $summary         = $result['summary']          ?? [];
        $detailedResults = $result['detailed_results'] ?? [];
        $videoDurations  = $result['video_durations']  ?? [];
        $stationProfiles = $result['station_profiles'] ?? [];

        // Helper untuk parse nilai string dari Flask: "45.6s" → 45.6
        $parse = function (string $key, string $suffix = '') use ($summary): float {
            $raw = $summary[$key] ?? '0';
            return floatval(str_replace($suffix, '', (string) $raw));
        };

        // Hapus data stasiun lama
        StationResult::where('job_id', $job->id)->delete();

        $validStatuses   = ['Bottleneck', 'At-Risk', 'Balanced', 'Underloaded'];
        $validRiskCats   = ['Low Risk', 'Medium Risk', 'High Risk'];
        $kategoriMap     = [
            'Value-Added'                   => 'VA',
            'Necessary but Non-Value-Added' => 'N-NVA',
            'Non-Value-Added'               => 'NVA',
        ];

        $order = 1;
        foreach ($videoDurations as $stationName => $meanCt) {
            $profile  = $stationProfiles[$stationName] ?? [];
            $elements = $detailedResults[$stationName] ?? [];

            $flaskStatus = $profile['status'] ?? 'Balanced';
            $dbStatus    = in_array($flaskStatus, $validStatuses) ? $flaskStatus : 'Balanced';

            $flaskRisk = $profile['risk_category'] ?? 'Low Risk';
            $dbRisk    = in_array($flaskRisk, $validRiskCats) ? $flaskRisk : 'Low Risk';

            // Simpan StationResult — nama kolom sesuai migration create_station_results
            $station = StationResult::create([
                'job_id'          => $job->id,
                'station_name'    => $stationName,
                'station_order'   => $order++,
                'mean_ct'         => floatval($meanCt),
                'robust_ct'       => floatval($profile['robust_ct']  ?? $meanCt),
                'cv_persen'       => floatval($profile['cv_persen']  ?? 0),
                'station_sigma'   => floatval($profile['sigma']      ?? 0),
                'idle_time'       => floatval($profile['idle_time']  ?? 0),
                'overflow_robust' => floatval($profile['overflow_robust'] ?? 0),
                'status_station'  => $dbStatus,
                'risk_category'   => $dbRisk,
            ]);

            // Simpan WorkElement
            foreach ($elements as $el) {
                $elKat = $el['kategori'] ?? 'Non-Value-Added';
                WorkElement::create([
                    'station_id'   => $station->id,
                    'elemen_kerja' => $el['elemen_kerja']  ?? '',
                    'durasi_detik' => floatval($el['durasi_detik']  ?? 0),
                    'std_dev'      => floatval($el['std_dev']       ?? 0),
                    'frekuensi'    => intval($el['frekuensi']       ?? 1),
                    'total_durasi' => floatval($el['total_durasi']  ?? $el['durasi_detik'] ?? 0),
                    'kategori_va'  => $kategoriMap[$elKat] ?? 'NVA',
                ]);
            }
        }

        // Update AnalysisJob — nama kolom sesuai migration create_analysis_jobs
        $totalCt  = $parse('Total Cycle Time', 's');
        $neckTime = $parse('Neck Time', 's');
        $le       = $parse('Presentase Line Balance', '%');
        $bd       = $parse('Balance Delay', '%');
        $si       = floatval($summary['Smoothness Index'] ?? 0); // tidak ada suffix
        $lineOut  = intval($summary['Line Output Hari'] ?? 0);

        $job->update([
            'status'           => 'completed',
            'total_cycle_time' => $totalCt,
            'neck_time_mean'   => $neckTime,
            'neck_time_robust' => $neckTime,
            'line_efficiency'  => $le,
            'balance_delay'    => $bd,
            'smoothness_index' => $si,
            'line_output_hari' => $lineOut,
            'chart_base64'     => $result['chart_base64'] ?? null,
        ]);
    }

    /**
     * Load data dari DB ke properti Livewire.
     */
    private function loadFromDb(AnalysisJob $job): void
    {
        $this->taktTime   = $job->takt_time;
        $this->operators  = $job->n_stations;
        $this->target     = $job->output_harian;
        $this->n_stations = $job->n_stations;

        $stations = StationResult::where('job_id', $job->id)
            ->orderBy('station_order')
            ->get();

        $this->stations  = $stations->pluck('station_name')->toArray();
        $this->meanCT    = $stations->pluck('mean_ct')->toArray();
        $this->robustCT  = $stations->pluck('robust_ct')->toArray();
        $this->cvData    = $stations->pluck('cv_persen')->toArray();

        $this->kpis = [
            ['label' => 'Line Efficiency',    'value' => $job->line_efficiency  ?? 0, 'target' => 75,           'unit' => '%',    'direction' => 'higher', 'accent' => '#3D7A99'],
            ['label' => 'Balance Delay',      'value' => $job->balance_delay    ?? 0, 'target' => 15,           'unit' => '%',    'direction' => 'lower',  'accent' => '#2C8C83'],
            ['label' => 'Smoothness Index',   'value' => $job->smoothness_index ?? 0, 'target' => 40,           'unit' => '',     'direction' => 'lower',  'accent' => '#FA6868'],
            ['label' => 'Output Aktual/Hari', 'value' => $job->line_output_hari ?? 0, 'target' => $this->target,'unit' => ' pcs', 'direction' => 'higher', 'accent' => '#312E81'],
        ];

        $this->bottleneckCount = $stations->where('status_station', 'Bottleneck')->count();
        $this->maxCV           = $stations->max('cv_persen') ?? 0;

        $this->elementsData = [];
        foreach ($stations as $station) {
            $this->elementsData[$station->station_name] = WorkElement::where('station_id', $station->id)->get();
        }
    }

    public function export()
    {
        $data = [
            'kpis'     => $this->kpis,
            'stations' => $this->stations,
            'meanCT'   => $this->meanCT,
            'robustCT' => $this->robustCT,
            'cvData'   => $this->cvData,
            'elements' => WorkElement::all(),
        ];

        return Excel::download(new LineAnalysisExport($data), 'line_analysis.xlsx');
    }

    public function startSimulation()
    {
        return redirect()->route('menu.simulation');
    }
};
