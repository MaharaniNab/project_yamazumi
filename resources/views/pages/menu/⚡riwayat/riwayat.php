<?php

use App\Models\AnalysisJob;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Riwayat')] class extends Component {

    use WithPagination;

    public $lines = [];
    #[Url] public $selectedLine = null;
    #[Url] public $search = '';
    #[Url] public array $range;
    public $perPage = 10;

    public function updated($key)
    {
        if (in_array($key, ['search', 'perPage', 'selectedLine', 'range'])) {
            $this->resetPage();
        }
    }

    public function mount()
    {
        $this->lines = AnalysisJob::distinct()
            ->pluck('line_name')
            ->toArray();
    }

    public function getJobsProperty()
    {
        $query = AnalysisJob::with('user')->withCount(['stations as bottleneck_count' => fn($q) => $q->where('status_station', 'Bottleneck')]);
        if ($this->selectedLine) {
            $query->where('line_name', $this->selectedLine);
        }
        if ($this->search) {
            $search = "%{$this->search}%";
            $query->where(function ($q) use ($search) {
                $q->where('line_name', 'like', $search)->orWhereHas('user', fn($u) => $u->where('name', 'like', $search));
            });
        }
        if (!empty($this->range['start']) && !empty($this->range['end'])) {
            $query->whereBetween('created_at', [Carbon::parse($this->range['start'])->startOfDay(), Carbon::parse($this->range['end'])->endOfDay()]);
        }
        return $query->latest()
            
        ->paginate($this->perPage);
    }
};