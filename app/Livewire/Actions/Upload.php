<?php
// ─────────────────────────────────────────────────────────────────────────────
// FILE: app/Livewire/Upload.php
// UPDATED: checkJobStatus() sekarang memanggil CvAnalysisService::checkAndSync()
// ─────────────────────────────────────────────────────────────────────────────
namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use App\Services\CvAnalysisService;
use App\Models\AnalysisJob;

class Upload extends Component
{
    use WithFileUploads;

    // Form metadata lini
    public string $namaLine     = '';
    public string $partName     = '';
    public string $style        = '';
    public string $brand        = '';
    public int    $outputHarian = 120;
    public int    $mpAktual     = 0;
    public float  $taktTime     = 0;

    // File upload
    public array  $videos       = [];
    public array  $stationNames = [];

    // State
    public int    $step              = 1;
    public bool   $isProcessing      = false;
    public int    $laravelJobId      = 0;    // ID integer dari DB
    public string $error             = '';
    public int    $progressCurrent   = 0;
    public int    $progressTotal     = 0;
    public string $progressMessage   = '';

    protected $rules = [
        'namaLine'      => 'required|string|max:100',
        'outputHarian'  => 'required|integer|min:1',
        'mpAktual'      => 'required|integer|min:1',
        'videos'        => 'required|array|min:1',
        'videos.*'      => 'file|mimes:mp4,avi,mov,mkv|max:204800', // max 200MB per file
    ];

    public function updatedOutputHarian(): void
    {
        $this->taktTime = $this->outputHarian > 0
            ? round(25920 / $this->outputHarian, 2)
            : 0;
    }

    public function updatedVideos(): void
    {
        $this->stationNames = [];
        foreach ($this->videos as $i => $video) {
            $name = pathinfo($video->getClientOriginalName(), PATHINFO_FILENAME);
            $this->stationNames[$i] = ucwords(str_replace(['_', '-'], ' ', $name));
        }
    }

    public function removeVideo(int $index): void
    {
        unset($this->videos[$index], $this->stationNames[$index]);
        $this->videos       = array_values($this->videos);
        $this->stationNames = array_values($this->stationNames);
    }

    public function nextStep(): void
    {
        if ($this->step === 1) {
            $this->validateOnly('namaLine,outputHarian,mpAktual');
        }
        $this->step++;
    }

    public function startAnalysis(CvAnalysisService $service): void
    {
        $this->validate();
        $this->isProcessing   = true;
        $this->error          = '';
        $this->progressMessage = 'Mengupload video ke server analisa...';

        // Kumpulkan UploadedFile Livewire (bukan store dulu, langsung forward)
        $videoFiles = [];
        foreach ($this->videos as $i => $video) {
            $stName = $this->stationNames[$i] ?? 'Stasiun ' . ($i + 1);
            $videoFiles[$stName] = $video;
        }

        $job = $service->startAnalysis(
            metadata: [
                'nama_line'     => $this->namaLine,
                'part_name'     => $this->partName,
                'style'         => $this->style,
                'brand'         => $this->brand,
                'output_harian' => $this->outputHarian,
                'mp_aktual'     => $this->mpAktual,
            ],
            videoFiles: $videoFiles,
            picId: Auth::id(),
        );

        if ($job->status === 'failed') {
            $this->error        = $job->error_msg ?? 'Gagal menghubungi server analisa.';
            $this->isProcessing = false;
            return;
        }

        $this->laravelJobId = $job->id;
        $this->step         = 3;
    }

    /**
     * Dipanggil Livewire polling (wire:poll.3000ms).
     * Sync status dari Flask API dan update progress.
     */
    public function checkJobStatus(CvAnalysisService $service): array
    {
        if (! $this->laravelJobId) return ['status' => 'waiting'];

        // Panggil Flask API untuk sync status
        $status = $service->checkAndSync($this->laravelJobId);

        // Update progress di UI
        $job = AnalysisJob::find($this->laravelJobId);
        if ($job) {
            $this->progressCurrent = $job->progress_current ?? 0;
            $this->progressTotal   = $job->progress_total   ?? 0;
            $this->progressMessage = $job->progress_message ?? '';
        }

        return [
            'status'   => $status,
            'progress' => "{$this->progressCurrent}/{$this->progressTotal}",
            'message'  => $this->progressMessage,
            'redirect' => $status === 'completed'
                ? route('results', $this->laravelJobId)
                : null,
        ];
    }

    public function render()
    {
        return view('livewire.upload')->layout('layouts.app', ['title' => 'Upload Video']);
    }
}