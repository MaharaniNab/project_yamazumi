<?php

namespace App\Jobs;

use App\Models\AnalysisJob;
use App\Services\CvAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessVideoAnalysis implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Batas waktu job (detik). Sesuaikan dengan estimasi proses video terlama.
     * 900 detik = 15 menit.
     */
    public int $timeout = 900;

    /**
     * Jumlah retry jika job gagal.
     */
    public int $tries = 2;

    /**
     * Jeda antar retry (detik).
     */
    public int $backoff = 10;

    public function __construct(
        public readonly int   $jobId,
        public readonly array $videoMap,
        public readonly array $metadata,
    ) {}

    public function handle(): void
    {
        $job = AnalysisJob::findOrFail($this->jobId);

        Log::info("[ProcessVideoAnalysis] Mulai job #{$this->jobId}");

        try {
            $service = new CvAnalysisService();
            $result  = $service->uploadToFlask($this->videoMap, $this->metadata);

            if (empty($result['job_id'])) {
                throw new \RuntimeException('Flask tidak mengembalikan job_id. Response: ' . json_encode($result));
            }

            $job->update([
                'python_job_id' => $result['job_id'],
                'status'        => 'processing',
            ]);

            Log::info("[ProcessVideoAnalysis] Selesai job #{$this->jobId}, python_job_id={$result['job_id']}");

        } catch (\Throwable $e) {
            Log::error("[ProcessVideoAnalysis] Gagal job #{$this->jobId}: " . $e->getMessage());

            // Tandai failed hanya di attempt terakhir agar retry bisa berjalan
            if ($this->attempts() >= $this->tries) {
                $job->update(['status' => 'failed', 'error_msg' => $e->getMessage()]);
            }

            // Lempar ulang agar Laravel Queue mencatat sebagai failed & trigger retry
            throw $e;
        }
    }

    /**
     * Dipanggil Laravel setelah semua retry habis dan job tetap gagal.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("[ProcessVideoAnalysis] Job #{$this->jobId} FINAL GAGAL: " . $exception->getMessage());

        AnalysisJob::where('id', $this->jobId)
            ->update(['status' => 'failed']);
    }
}
