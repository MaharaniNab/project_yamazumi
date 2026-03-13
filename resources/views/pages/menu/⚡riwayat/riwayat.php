<?php

use App\Models\AnalysisJob;
use Flux\DateRange;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new
    #[Title('Riwayat')]
    class extends Component {

    public $lines = [];
    #[Url]
    public $selectedLine = null;
    #[Url]
    public $searchHeader = '';
    public $perPage = 10;
    public DateRange $range;

    public function mount()
    {
        $this->range = new DateRange(now()->subMonth(), now());
        // ambil semua line unik
        $this->lines = AnalysisJob::select('line_name')
            ->distinct()
            ->pluck('line_name')
            ->toArray();
    }

    #[Computed]
    public function jobs()
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
            ->when($this->searchHeader, function ($q) {
                $q->where('line_name', 'like', '%' . $this->searchHeader . '%');
            })
            ->when($this->range, function ($q) {
                $q->whereBetween('created_at', [
                    $this->range->start(),
                    $this->range->end()
                ]);
            })
            ->latest()
            ->paginate($this->perPage);
    }
};