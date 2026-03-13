<?php

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

        'line_name' => 'required|string|max:100',
        'part_name' => 'nullable|string|max:150',
        'style' => 'nullable|string|max:100',
        'brand' => 'nullable|string|max:100',
        'output_harian' => 'required|integer|min:1',
        'mp_aktual' => 'required|integer|min:1',

        'file_list.*' => 'required|file|mimes:mp4,avi,mov,mkv,mts|max:204800',
        'station_name.*' => 'required|string|max:100',
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

        $this->file_list = array_values($this->file_list);
        $this->station_name = array_values($this->station_name);
    }

    public function getTaktTimeProperty()
    {
        if (!$this->output_harian)
            return null;

        $seconds = $this->effectiveHours * 3600;

        return round($seconds / $this->output_harian, 2);
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

        $taktTime = $this->output_harian
            ? round($jamKerjaDetik / $this->output_harian, 2)
            : null;

        $job = AnalysisJob::create([

            'user_id' => Auth::id(),

            'line_name' => $this->line_name,
            'part_name' => $this->part_name,
            'style' => $this->style,
            'brand' => $this->brand,

            'output_harian' => $this->output_harian,
            'mp_aktual' => (int) $this->mp_aktual,

            'jam_kerja_detik' => $jamKerjaDetik,
            'takt_time' => $taktTime,

            'n_stations' => count($this->file_list),

            'status' => 'processing',
        ]);

        foreach ($this->file_list as $i => $file) {

            $name = $this->station_name[$i];

            $file->storeAs(
                "analisis_videos/$job->id",
                $name . '.' . $file->getClientOriginalExtension(),
                'public'
            );
        }

        $this->dispatch(
            'swal-toast',
            icon: 'success',
            title: 'Berhasil',
            text: 'Video berhasil diupload.'
        );

        $this->redirectRoute('menu.report', navigate: true);
    }
};