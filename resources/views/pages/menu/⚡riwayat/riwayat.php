<?php

use App\Models\AnalysisJob;
use Flux\DateRange;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new
    #[Title('Riwayat')]
    class extends Component {
    use WithPagination;

    public $lines = [];
    #[Url]
    public $selectedLine = null;
    #[Url]
    public $search = '';
    public $perPage = 10;
    public $range = [];

    public function updated($key)
    {
        if (in_array($key, ['search', 'perPage', 'selectedLine', 'range'])) {
            $this->resetPage();
        }
    }

    public function mount()
    {
        $this->range = [
            'start' => now()->subMonth()->toDateString(),
            'end' => now()->toDateString(),
        ];
        // ambil semua line unik
        $this->lines = AnalysisJob::select('line_name')
            ->distinct()
            ->pluck('line_name')
            ->toArray();
    }

    public function getJobsProperty()
    {
        return AnalysisJob::with('user')
            ->withCount([
                'stations as bottleneck_count' => function ($q) {
                    $q->where('status_station', 'Bottleneck');
                }
            ])
            ->when($this->selectedLine, function ($q) {
                $q->where('line_name', $this->selectedLine);
            })
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('line_name', 'like', '%' . trim($this->search) . '%')
                        ->orWhereHas('user', function ($user) {
                            $user->where('name', 'like', '%' . trim($this->search) . '%');
                        });
                });
            })
            ->when($this->range, function ($q) {

                $start = data_get($this->range, 'start');
                $end = data_get($this->range, 'end');

                if ($start && $end) {
                    $q->whereBetween('created_at', [
                        \Carbon\Carbon::parse($start)->startOfDay(),
                        \Carbon\Carbon::parse($end)->endOfDay(),
                    ]);
                }
            })
            ->latest()
            ->paginate($this->perPage);
    }
};