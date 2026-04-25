<?php

use App\Exports\LineAnalysisExport;
use App\Models\AnalysisJob;
use App\Models\SimulationResult;
use App\Models\SimulationStation;
use App\Models\StationResult;
use App\Models\WorkElement;
use App\Services\PythonApiService;
use Livewire\Attributes\Title;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

new
    #[Title('Report Analyst')] class extends Component {

    public $taktTime;
    public $n_stations;
    public $operators;
    public $target;

    public $stations   = [];
    public $meanCT     = [];
    public $robustCT   = [];
    public $cvData     = [];
    public $kpis       = [];
    public $elements   = [];
    public $metrics    = [];
    public $elementsData = [];

    public $bottleneckCount = 0;
    public $maxCV           = 0;
    public $selectedStation = null;

    // Status polling
    public $isProcessing = false;
    public $processingMessage = 'Sedang menganalisis video...';

    public function mount()
    {
        $job = AnalysisJob::latest()->first();
        if (!$job) return;

        // Kalau masih processing, tampilkan loading state
        if ($job->status === 'processing') {
            $this->isProcessing = true;
            return;
        }

        // Kalau sudah completed, load dari DB seperti biasa
        if ($job->status === 'completed') {
            $this->loadFromDb($job);
        }
    }

    /**
     * Dipanggil otomatis oleh Livewire polling setiap 3 detik.
     * Hanya aktif kalau isProcessing = true.
     */
    public function checkFlaskStatus()
    {
        if (!$this->isProcessing) return;

        $job = AnalysisJob::latest()->first();
        if (!$job || !$job->python_job_id) return;

        try {
            $api    = new PythonApiService();
            $result = $api->getResults($job->python_job_id);

            if ($result['status'] === 'processing') {
                $this->processingMessage = 'Sedang menganalisis video... mohon tunggu.';
                return;
            }

            if ($result['status'] === 'failed') {
                $this->isProcessing = false;
                $job->update(['status' => 'failed']);
                $this->dispatch('swal-toast', icon: 'error', title: 'Gagal', text: 'Analisis gagal diproses.');
                return;
            }

            if ($result['status'] === 'completed') {
                // Simpan hasil ke DB
                $this->saveResultsToDb($job, $result);

                $this->isProcessing = false;
                $this->loadFromDb($job->fresh());

                $this->dispatch('swal-toast', icon: 'success', title: 'Selesai', text: 'Analisis berhasil diselesaikan!');
                $this->dispatch('results-ready'); // trigger chart refresh di blade
            }

        } catch (\Exception $e) {
            // Diam saja kalau gagal poll, coba lagi 3 detik kemudian
        }
    }

    /**
     * Simpan hasil Flask ke tabel StationResult + WorkElement + update AnalysisJob.
     */
    private function saveResultsToDb(AnalysisJob $job, array $result): void
    {
        $summary         = $result['summary']         ?? [];
        $detailedResults = $result['detailed_results'] ?? [];
        $videoDurations  = $result['video_durations']  ?? [];
        $stationProfiles = $result['station_profiles'] ?? [];

        // Hapus data lama kalau ada (untuk re-analysis)
        StationResult::where('job_id', $job->id)->delete();

        $order = 1;
        foreach ($videoDurations as $stationName => $meanCt) {
            $profile  = $stationProfiles[$stationName] ?? [];
            $elements = $detailedResults[$stationName] ?? [];

            // Map status dari Flask ke enum yang valid di DB
            $validStatuses = ['Bottleneck', 'At-Risk', 'Balanced', 'Underloaded'];
            $flaskStatus   = $profile['status'] ?? 'Balanced';
            $statusStation = in_array($flaskStatus, $validStatuses) ? $flaskStatus : 'Balanced';

            // Simpan StationResult
            // Kolom sesuai migration: station_sigma (bukan sigma), robust_ct (bukan robust_ct_mean)
            $station = StationResult::create([
                'job_id'         => $job->id,
                'station_name'   => $stationName,
                'station_order'  => $order++,
                'mean_ct'        => $meanCt,
                'robust_ct'      => $profile['robust_ct']  ?? $meanCt,
                'cv_persen'      => $profile['cv_persen']  ?? 0,
                'status_station' => $statusStation,
                'station_sigma'  => $profile['sigma']      ?? 0,   // ← nama kolom yang benar
                'idle_time'      => $profile['idle_time']  ?? 0,
            ]);

            // Simpan WorkElement per stasiun
            // Map kategori Flask → enum DB: VA, N-NVA, NVA
            $kategoriMap = [
                'Value-Added'                    => 'VA',
                'Necessary but Non-Value-Added'  => 'N-NVA',
                'Non-Value-Added'                => 'NVA',
            ];
            foreach ($elements as $el) {
                $katFlask = $el['elemen_kerja'] ?? '';
                // Ambil kategori dari ELEMENT_CLASSIFICATION Flask jika ada di response
                $katRaw   = 'NVA'; // default
                $dbKat    = $kategoriMap[$katRaw] ?? 'NVA';

                WorkElement::create([
                    'station_id'   => $station->id,
                    'elemen_kerja' => $el['elemen_kerja'],
                    'durasi_detik' => $el['durasi_detik'],
                    'std_dev'      => $el['std_dev']      ?? 0,
                    'frekuensi'    => $el['frekuensi']    ?? 1,
                    'total_durasi' => $el['total_durasi'] ?? $el['durasi_detik'],
                    'kategori_va'  => $dbKat,
                ]);
            }
        }

        // Hitung metrik dari summary Flask
        $neckTime = floatval(str_replace('s', '', $summary['Neck Time'] ?? '0'));
        $le       = floatval(str_replace('%', '', $summary['Presentase Line Balance'] ?? '0'));
        $bd       = floatval(str_replace('%', '', $summary['Balance Delay'] ?? '0'));
        $si       = floatval(str_replace('s', '', $summary['Smoothness Index'] ?? '0'));
        $lineOut  = intval($summary['Line Output Hari'] ?? 0);

        // Update AnalysisJob dengan hasil kalkulasi
        // (nama kolom disesuaikan dengan migration create_analysis_jobs)
        $totalCt = floatval(str_replace('s', '', $summary['Total Cycle Time'] ?? '0'));
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
     * Load data dari DB ke properti Livewire (sama seperti logic mount() lama).
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
            ['label' => 'Line Efficiency',    'value' => $job->line_efficiency,  'target' => 75,           'unit' => '%',    'direction' => 'higher', 'accent' => '#3D7A99'],
            ['label' => 'Balance Delay',      'value' => $job->balance_delay,    'target' => 15,           'unit' => '%',    'direction' => 'lower',  'accent' => '#2C8C83'],
            ['label' => 'Smoothness Index',   'value' => $job->smoothness_index, 'target' => 40,           'unit' => '',     'direction' => 'lower',  'accent' => '#FA6868'],
            ['label' => 'Output Aktual/Hari', 'value' => $job->line_output_hari, 'target' => $this->target,'unit' => ' pcs', 'direction' => 'higher', 'accent' => '#312E81'],
        ];

        $this->bottleneckCount = $stations->where('status_station', 'Bottleneck')->count();
        $this->maxCV           = $stations->max('cv_persen');

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
