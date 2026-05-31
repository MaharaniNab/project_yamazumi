<?php

namespace App\Services;

use App\Models\{AnalysisJob, StationResult, WorkElement, RawSegment};
use Illuminate\Support\Facades\{Http, Log, Storage, DB};

/**
 * CvAnalysisService v2.0
 * ──────────────────────
 * Menghubungkan Laravel dengan Flask API di HuggingFace Spaces via HTTP.
 *
 * Alur:
 *   1. Livewire Upload memanggil startAnalysis()
 *   2. Service buat record AnalysisJob di DB (status='pending')
 *   3. Service kirim video ke Flask API → dapat python_job_id
 *   4. Update status='processing', simpan python_job_id
 *   5. Livewire polling checkAndSync() setiap 3 detik
 *   6. Saat Flask selesai → parse response → simpan ke DB → status='completed'
 */
class CvAnalysisService
{
    private string $apiUrl;
    private ?string $apiKey;
    private int    $timeout;

    public function __construct()
    {
        $this->apiUrl  = rtrim(config('services.flask_api.url', env('FLASK_API_URL', '')), '/');
        $this->apiKey  = config('services.flask_api.key',  env('FLASK_API_KEY'));
        $this->timeout = (int) config('services.flask_api.timeout', env('FLASK_API_TIMEOUT', 120));
    }

    // =========================================================================
    // 1. HTTP CLIENT
    // =========================================================================

    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        $http = Http::timeout($this->timeout)->acceptJson();
        if ($this->apiKey) {
            $http = $http->withHeaders(['X-API-Key' => $this->apiKey]);
        }
        return $http;
    }

    // =========================================================================
    // 2. UPLOAD TO FLASK — dipanggil dari Queue Job (tidak ada time limit)
    //    $videoMap: ['Nama Stasiun' => 'analisis_videos/{job_id}/{name}.ext']
    //    $metadata: ['output_harian', 'mp_aktual', 'nama_line', 'nama_bagian']
    // =========================================================================

    public function uploadToFlask(array $videoMap, array $metadata): array
    {
        // Timeout 0 = unlimited — upload video besar bisa makan waktu lama
        $http = Http::timeout(0)->acceptJson();
        if ($this->apiKey) {
            $http = $http->withHeaders(['X-API-Key' => $this->apiKey]);
        }

        // Form fields HARUS diattach sebagai multipart part, bukan lewat post($url, $array).
        // Jika digabung dengan array di post(), Guzzle akan abaikan form_params dan
        // Flask akan menerima request tanpa field apapun → 400 "tidak ada file".
        $http = $http->attach('output_harian', (string) ($metadata['output_harian'] ?? ''));
        $http = $http->attach('mp_aktual',     (string) ($metadata['mp_aktual']     ?? '0'));
        $http = $http->attach('nama_line',     (string) ($metadata['nama_line']     ?? ''));
        $http = $http->attach('nama_bagian',   (string) ($metadata['nama_bagian']   ?? ''));

        // Attach video files via stream (bukan file_get_contents agar tidak OOM untuk file besar)
        foreach ($videoMap as $stationName => $storagePath) {
            $absPath  = Storage::disk('public')->path($storagePath);
            $filename = $stationName . '.' . pathinfo($absPath, PATHINFO_EXTENSION);
            $stream   = fopen($absPath, 'r');
            $http     = $http->attach('file_list', $stream, $filename);
        }

        // Panggil post() TANPA array kedua — semua data sudah dalam multipart parts
        $response = $http->post("{$this->apiUrl}/api/upload");

        if ($response->failed()) {
            throw new \RuntimeException(
                'Flask API error [' . $response->status() . ']: ' . $response->body()
            );
        }

        return $response->json();
    }

    // =========================================================================
    // 3. START ANALYSIS — dipanggil dari Livewire Upload
    // =========================================================================

    /**
     * Buat job di DB, kirim video ke Flask API, simpan python_job_id.
     *
     * @param  array  $metadata   ['nama_line', 'part_name', 'style', 'brand',
     *                             'output_harian', 'mp_aktual']
     * @param  array  $videoFiles ['Nama Stasiun' => UploadedFile|path_string, ...]
     * @param  int    $picId      User ID yang upload
     * @return AnalysisJob
     */
    public function startAnalysis(array $metadata, array $videoFiles, int $picId): AnalysisJob
    {
        $jamKerja = 25920.0;
        $takt     = round($jamKerja / ($metadata['output_harian'] ?? 1), 4);

        // ── Buat job di DB dulu (dapat auto-increment ID) ─────────────────
        $job = AnalysisJob::create([
            'user_id'         => $picId,
            'status'          => 'pending',
            'line_name'       => $metadata['nama_line']    ?? '',
            'part_name'       => $metadata['part_name']    ?? null,
            'style'           => $metadata['style']        ?? null,
            'brand'           => $metadata['brand']        ?? null,
            'output_harian'   => $metadata['output_harian'],
            'mp_aktual'       => $metadata['mp_aktual']    ?? 0,
            'jam_kerja_detik' => $jamKerja,
            'takt_time'       => $takt,
            'n_stations'      => count($videoFiles),
        ]);

        try {
            // ── Kirim video ke Flask API ──────────────────────────────────
            $http = $this->client();

            // Attach setiap video file
            foreach ($videoFiles as $stationName => $file) {
                // $file bisa berupa UploadedFile (Livewire) atau path string
                if (is_string($file)) {
                    $filename  = basename($file);
                    $contents  = file_get_contents($file);
                } else {
                    $filename  = $stationName . '.' . $file->getClientOriginalExtension();
                    $contents  = file_get_contents($file->getRealPath());
                }
                $http = $http->attach('file_list', $contents, $filename);
            }

            $response = $http->post("{$this->apiUrl}/api/upload", [
                'output_harian' => $metadata['output_harian'],
                'mp_aktual'     => $metadata['mp_aktual'] ?? 0,
                'nama_line'     => $metadata['nama_line'] ?? '',
                'nama_bagian'   => $metadata['part_name'] ?? '',
                'pic_name'      => $picId,
            ]);

            if ($response->failed()) {
                $errBody = $response->json();
                throw new \RuntimeException(
                    'Flask API error [' . $response->status() . ']: ' .
                    ($errBody['error'] ?? $errBody['message'] ?? $response->body())
                );
            }

            $data = $response->json();

            // ── Update job dengan python_job_id dari Flask ────────────────
            $job->update([
                'python_job_id' => $data['job_id'],
                'status'        => 'processing',
            ]);

            Log::info("[CvAnalysis] Job {$job->id} dikirim ke Flask, python_job_id={$data['job_id']}");

        } catch (\Throwable $e) {
            Log::error("[CvAnalysis] startAnalysis gagal untuk job {$job->id}: " . $e->getMessage());
            $job->update([
                'status'    => 'failed',
                'error_msg' => $e->getMessage(),
            ]);
        }

        return $job->fresh();
    }

    // =========================================================================
    // 3. POLLING — dipanggil dari Livewire checkJobStatus() setiap 3 detik
    // =========================================================================

    /**
     * Cek status Flask, jika selesai langsung simpan hasilnya ke DB.
     * Return status terkini job.
     */
    public function checkAndSync(int|string $laravelJobId): string
    {
        $job = AnalysisJob::find($laravelJobId);

        if (! $job || in_array($job->status, ['completed', 'failed'])) {
            return $job?->status ?? 'not_found';
        }

        if (! $job->python_job_id) {
            // Flask belum dipanggil (masih pending)
            return $job->status;
        }

        try {
            $response = $this->client()
                ->get("{$this->apiUrl}/api/results/{$job->python_job_id}");

            if ($response->failed()) return $job->status;

            $data       = $response->json();
            $flaskStatus = $data['status'] ?? 'processing';

            // ── Progress update ───────────────────────────────────────────
            if (isset($data['progress_current'], $data['progress_total'])) {
                $job->update([
                    'progress_current' => $data['progress_current'],
                    'progress_total'   => $data['progress_total'],
                    'progress_message' => $data['progress_message'] ?? '',
                ]);
            }

            if ($flaskStatus === 'completed') {
                $this->saveResultsToDb($job, $data);
                return 'completed';
            }

            if ($flaskStatus === 'failed') {
                $job->update([
                    'status'    => 'failed',
                    'error_msg' => $data['error_message'] ?? 'Flask processing failed',
                ]);
                return 'failed';
            }

        } catch (\Throwable $e) {
            Log::warning("[CvAnalysis] checkAndSync job {$laravelJobId}: " . $e->getMessage());
        }

        return $job->status;
    }

    // =========================================================================
    // 4. SIMULATION — dipanggil dari Livewire Results/Simulation
    // =========================================================================

    public function runSimulation(int|string $laravelJobId): ?array
    {
        $job = AnalysisJob::find($laravelJobId);
        if (! $job?->python_job_id) return null;

        try {
            $response = $this->client()
                ->get("{$this->apiUrl}/api/simulate/{$job->python_job_id}");

            if ($response->failed()) {
                Log::error("[Simulation] Job {$laravelJobId} Flask error: " . $response->body());
                return null;
            }

            return $response->json();

        } catch (\Throwable $e) {
            Log::error("[Simulation] Job {$laravelJobId}: " . $e->getMessage());
            return null;
        }
    }

    // =========================================================================
    // 5. SAVE RESULTS TO DB — mapper Flask response → Eloquent
    // =========================================================================

    private function saveResultsToDb(AnalysisJob $job, array $flaskData): void
    {
        // ── Map Flask summary → DB fields ─────────────────────────────────
        $summary  = $flaskData['summary'] ?? [];
        $profiles = $flaskData['station_profiles'] ?? [];
        $detailed = $flaskData['detailed_results'] ?? [];
        $durations = $flaskData['video_durations'] ?? [];
        $performance = $flaskData['station_performance'] ?? [];

        // Helper: parse nilai dari string seperti "248.50s" atau "72.40%"
        $parse = fn($val) => $val ? (float) preg_replace('/[^0-9.]/', '', $val) : null;

        // Semua penulisan dalam satu transaksi: kalau ada satu baris gagal,
        // seluruh hasil di-rollback sehingga job tidak pernah ter-mark "completed"
        // dengan data parsial (mis. hanya 1 stasiun yang tersimpan).
        DB::transaction(function () use ($job, $summary, $profiles, $detailed, $durations, $performance, $parse) {

        // Idempotent: hapus hasil lama dulu (work_elements ikut terhapus via cascade)
        StationResult::where('job_id', $job->id)->delete();

        $job->update([
            'status'            => 'completed',
            'total_cycle_time'  => $parse($summary['Total Cycle Time']         ?? null),
            'neck_time_mean'    => $parse($summary['Neck Time']                 ?? null),
            'line_efficiency'   => $parse($summary['Presentase Line Balance']   ?? null),
            'balance_delay'     => $parse($summary['Balance Delay']             ?? null),
            'smoothness_index'  => $parse($summary['Smoothness Index']          ?? null),
            'line_output_hari'  => $summary['Line Output Hari']                 ?? null,
            'n_stations'        => $summary['Total Proses (Operator)']          ?? count($detailed),
            'chart_base64'      => $flaskData['chart_base64']                   ?? null,
            'error_msg'         => null,
        ]);

        // ── Simpan setiap stasiun ─────────────────────────────────────────
        $order = 1;
        foreach ($detailed as $stationName => $elements) {
            $profile = $profiles[$stationName] ?? [];
            $perf    = $performance[$stationName] ?? [];
            $ct      = $durations[$stationName] ?? 0;
            $sigma   = $profile['sigma'] ?? 0;
            $takt    = $job->takt_time;

            $station = StationResult::create([
                'job_id'          => $job->id,
                'station_name'    => $stationName,
                'station_order'   => $order++,
                'mean_ct'         => $profile['mean_ct']       ?? $ct,
                'station_sigma'   => $sigma,
                'robust_ct'       => $profile['robust_ct']     ?? ($ct + 2 * $sigma),
                'total_variance'  => pow($sigma, 2),
                'cv_persen'       => $profile['cv_persen']     ?? 0,
                'idle_time'       => max(0, $takt - $ct),
                'overflow_robust' => $profile['overflow_robust'] ?? 0,
                'risk_category'   => $profile['risk_category']  ?? 'Low Risk',
                'status_station'  => $profile['status']          ?? 'Balanced',
                'n_cycles'        => 1,
                'output_jam'      => $perf['output_jam']         ?? ($ct > 0 ? round(3600 / $ct, 1) : null),
                'output_hari'     => $perf['output_hari']        ?? ($ct > 0 ? intval(25920 / $ct) : null),
            ]);

            // ── Work elements ─────────────────────────────────────────────
            foreach ($elements as $el) {
                $nama    = $el['elemen_kerja'] ?? '';
                $klasifikasi = \App\Services\CvAnalysisService::getKategoriVa($nama);

                WorkElement::create([
                    'station_id'        => $station->id,
                    'elemen_kerja'      => $nama,
                    'durasi_detik'      => $el['durasi_detik']   ?? 0,
                    'std_dev'           => $el['std_dev']         ?? 0,
                    'cv_persen'         => 0,
                    'frekuensi'         => $el['frekuensi']       ?? 1,
                    'total_durasi'      => $el['total_durasi']    ?? $el['durasi_detik'] ?? 0,
                    'mean_per_kejadian' => $el['durasi_detik']    ?? 0,
                    'kategori_va'       => $klasifikasi['kategori'],
                    'warna_hex'         => $klasifikasi['warna'],
                ]);
            }
        }

        }); // end DB::transaction

        Log::info("[CvAnalysis] Job {$job->id} completed.");
    }

    // =========================================================================
    // 6. HELPER — Kategori VA sesuai ELEMENT_CLASSIFICATION di Flask
    // =========================================================================

    public static function getKategoriVa(string $namaElemen): array
    {
        // Kolom work_elements.kategori_va adalah enum('VA','N-NVA','NVA').
        // Kembalikan kode pendek agar tidak kena "Data truncated for column".
        // VA    = Value-Added
        // N-NVA = Necessary but Non-Value-Added
        // NVA   = Non-Value-Added
        $map = [
            'Proses Jahit'       => ['kategori' => 'VA',    'warna' => '#1a9850'],
            'Proses Menggosok'   => ['kategori' => 'VA',    'warna' => '#1a9850'],
            'Loading Mesin'      => ['kategori' => 'VA',    'warna' => '#1a9850'],
            'Proses Menggunting' => ['kategori' => 'VA',    'warna' => '#1a9850'],
            'Mesin Pressing'     => ['kategori' => 'VA',    'warna' => '#1a9850'],
            'Pengecekan Barang'  => ['kategori' => 'N-NVA', 'warna' => '#fee08b'],
            'Menata Kain'        => ['kategori' => 'N-NVA', 'warna' => '#fee08b'],
            'Marking/Menandai'   => ['kategori' => 'N-NVA', 'warna' => '#fee08b'],
            'Mengambil Produk'   => ['kategori' => 'NVA',   'warna' => '#d73027'],
            'Meletakkan Barang'  => ['kategori' => 'NVA',   'warna' => '#d73027'],
            'Mengganti Benang'   => ['kategori' => 'NVA',   'warna' => '#f46d43'],
            'Unloading Mesin'    => ['kategori' => 'NVA',   'warna' => '#d73027'],
            'Persiapan'          => ['kategori' => 'NVA',   'warna' => '#d73027'],
        ];

        return $map[$namaElemen] ?? ['kategori' => 'NVA', 'warna' => '#4575b4'];
    }
}