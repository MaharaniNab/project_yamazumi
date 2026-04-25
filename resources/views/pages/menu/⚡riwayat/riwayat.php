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
    #[Url]
    public $startDate = null;
    #[Url]

    public $endDate = null;

    public function openDetail($id)
    {
        return redirect()->route('menu.report', ['job_id' => $id]);
    }

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
            ->when($this->startDate && $this->endDate, function ($q) {
                $q->whereBetween('created_at', [
                    $this->startDate . ' 00:00:00',
                    $this->endDate . ' 23:59:59',
                ]);
            })
            ->latest()
            ->paginate($this->perPage);
    }
};