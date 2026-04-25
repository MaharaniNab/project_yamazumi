<?php

use App\Services\PythonApiService;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\AnalysisJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

new
    #[Title('Setup Analyst')]
    class extends Component {

    use WithFileUploads;

    public $step = 1;

    public $line_name;
    public $part_name;
    public $output_harian;
    public $brand;
    public $style;
    public $mp_aktual;

    public $file_list = [];
    public $station_name = [];

    public $effectiveHours = 7.2;

    protected $rules = [
        'line_name'       => 'required|string|max:100',
        'part_name'       => 'nullable|string|max:150',
        'style'           => 'nullable|string|max:100',
        'brand'           => 'nullable|string|max:100',
        'output_harian'   => 'required|integer|min:1',
        'mp_aktual'       => 'required|integer|min:1',
        'file_list.*'     => 'required|file|mimes:mp4,avi,mov,mkv,mts|max:204800',
        'station_name.*'  => 'required|string|max:100',
    ];

    public function updated($field)
    {
        $this->updateStep();
    }

    private function updateStep()
    {
        if ($this->line_name && $this->output_harian) {
            $this->step = 2;
        }

        if (!empty($this->file_list)) {
            $this->step = 3;
        }
    }

    public function updatedFileList()
    {
        foreach ($this->file_list as $i => $file) {
            if (!isset($this->station_name[$i])) {
                $this->station_name[$i] = pathinfo(
                    $file->getClientOriginalName(),
                    PATHINFO_FILENAME
                );
            }
        }
    }

    public function removeVideo($i)
    {
        unset($this->file_list[$i]);
        unset($this->station_name[$i]);

        $this->file_list   = array_values($this->file_list);
        $this->station_name = array_values($this->station_name);
    }

    public function getTaktTimeProperty()
    {
        if (!$this->output_harian) return null;

        return round(($this->effectiveHours * 3600) / $this->output_harian, 2);
    }

    public function resetForm()
    {
        $this->reset();
        $this->step = 1;
    }

    public function save()
    {
        $this->validate();

        $jamKerjaDetik = $this->effectiveHours * 3600;
        $taktTime      = round($jamKerjaDetik / $this->output_harian, 2);

        // 1. Simpan job ke DB (sama seperti sebelumnya)
        $job = AnalysisJob::create([
            'user_id'        => Auth::id(),
            'line_name'      => $this->line_name,
            'part_name'      => $this->part_name,
            'style'          => $this->style,
            'brand'          => $this->brand,
            'output_harian'  => $this->output_harian,
            'mp_aktual'      => (int) $this->mp_aktual,
            'jam_kerja_detik'=> $jamKerjaDetik,
            'takt_time'      => $taktTime,
            'n_stations'     => count($this->file_list),
            'status'         => 'processing',
        ]);

        // 2. Simpan video ke storage lokal (sama seperti sebelumnya)
        $videoMap = [];
        foreach ($this->file_list as $i => $file) {
            $name     = $this->station_name[$i];
            $ext      = $file->getClientOriginalExtension();
            $path     = "analisis_videos/{$job->id}/{$name}.{$ext}";

            $file->storeAs(
                "analisis_videos/{$job->id}",
                "{$name}.{$ext}",
                'public'
            );

            $videoMap[$name] = $path;
        }

        // 3. Kirim ke Flask API
        try {
            $api = new PythonApiService();

            $metadata = [
                'output_harian' => $this->output_harian,
                'mp_aktual'     => $this->mp_aktual,
                'nama_line'     => $this->line_name,
                'nama_bagian'   => $this->part_name ?? $this->line_name,
            ];

            $result = $api->uploadVideos($videoMap, $metadata);

            // 4. Simpan python_job_id ke AnalysisJob
            $job->update([
                'python_job_id' => $result['job_id'],
                'status'        => 'processing',
            ]);

        } catch (\Exception $e) {
            // Kalau Flask gagal, tandai error tapi jangan stop
            $job->update(['status' => 'failed']);

            $this->dispatch(
                'swal-toast',
                icon: 'error',
                title: 'Flask API Error',
                text: 'Gagal menghubungi API: ' . $e->getMessage()
            );
            return;
        }

        $this->dispatch(
            'swal-toast',
            icon: 'success',
            title: 'Berhasil',
            text: 'Video berhasil diupload dan sedang diproses.'
        );

        $this->redirectRoute('menu.report', navigate: true);
    }
};