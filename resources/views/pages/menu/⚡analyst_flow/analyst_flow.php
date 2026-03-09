<?php

use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new
    #[Title('Setup Analyst')]
    class extends Component {

    use WithFileUploads;

    public $step = 1;

    public $nama_line;
    public $nama_bagian;
    public $output_harian;
    public $brand;
    public $style_product;

    public $station_names = [];
    public $file_list = [];

    public $effectiveHours = 7.2;

    public function mount()
    {
        $this->detectStep();
    }

    private function detectStep()
    {
        if (
            $this->nama_line &&
            $this->nama_bagian &&
            $this->output_harian &&
            $this->brand &&
            $this->style_product
        ) {
            $this->step = 2;
        }

        if (!empty($this->file_list)) {
            $this->step = 3;
        }
    }

    public function updated($property)
    {
        $this->detectStep();
    }

    public function removeVideo($index)
    {
        unset($this->file_list[$index]);
        unset($this->station_names[$index]);

        // reset index supaya sinkron
        $this->file_list = array_values($this->file_list);
        $this->station_names = array_values($this->station_names);

        $this->detectStep();
    }

    public static function taktTime(float $effectiveHours, int $output_harian): ?float
    {
        $effectiveSeconds = $effectiveHours * 3600;

        if ($output_harian > 0) {
            return round($effectiveSeconds / $output_harian, 2);
        }

        return null;
    }

    public function getTaktTimeProperty()
    {
        return self::taktTime($this->effectiveHours, (int) $this->output_harian);
    }

    public function updatedFileList()
    {
        try {

            $this->validateOnly('file_list.*', [
                'file_list.*' => 'required|file|mimes:mp4,avi,mov,mkv|max:204800',
            ]);

            foreach ($this->file_list as $index => $file) {

                if (!isset($this->station_names[$index])) {

                    $this->station_names[$index] = pathinfo(
                        $file->getClientOriginalName(),
                        PATHINFO_FILENAME
                    );
                }
            }

            // rapikan index
            $this->station_names = array_values($this->station_names);

            $this->step = 3;

        } catch (\Throwable $e) {

            $this->file_list = [];
            $this->station_names = [];

            $this->dispatch(
                'swal-toast',
                icon: 'error',
                title: 'Format Tidak Didukung',
                text: 'Hanya file video maksimal 200MB yang diperbolehkan.'
            );

            throw $e;
        }
    }

    public function resetForm()
    {
        $this->reset([
            'nama_line',
            'nama_bagian',
            'output_harian',
            'brand',
            'style_product',
            'file_list',
            'station_names'
        ]);
        $this->step = 1;
    }

    public function save()
    {
        foreach ($this->file_list as $index => $file) {

            $name = $this->station_names[$index] ?? pathinfo(
                $file->getClientOriginalName(),
                PATHINFO_FILENAME
            );

            $extension = $file->getClientOriginalExtension();

            $file->storeAs(
                'analisis_videos',
                $name . '.' . $extension,
                'public'
            );
        }

        $this->dispatch(
            'swal-toast',
            icon: 'success',
            title: 'Berhasil',
            text: 'Video berhasil diupload.'
        );

        $this->reset([
            'nama_line',
            'nama_bagian',
            'output_harian',
            'brand',
            'style_product',
            'file_list',
            'station_names'
        ]);

        $this->step = 1;
    }
};