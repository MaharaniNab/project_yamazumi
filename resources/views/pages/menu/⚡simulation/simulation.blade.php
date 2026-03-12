<div class="flex flex-col gap-6">
    <div class="flex items-start justify-between w-full">
        <div>
            <flux:heading size="lg" class="font-semibold">
                Simulasi Kaizen Balancing
                <flux:subheading class="text-sm text-neutral-500 mt-1">
                    <div class="flex space-x-1">
                        <span>3 - Phase Algorithm</span>
                        <span>·</span>
                        <span>NVA Eliminasi 100%</span>
                        <span>·</span>
                        <span>MP Rebalancing (Constraint: Σ MP = MP Aktual)</span>
                    </div>
                </flux:subheading>
            </flux:heading>
        </div>

        {{-- KPI Cards sejajar --}}
        <div class="flex flex-wrap gap-3">
            @foreach ($this->kpis as $kpi)
                @php
                    $color = match ($kpi['color']) {
                        'amber' => 'text-amber-500 bg-amber-50 !border-amber-500',
                        'emerald' => 'text-emerald-500 bg-emerald-50 !border-emerald-500',
                        'blue' => 'text-blue-500 bg-blue-50 !border-blue-500',
                        default => 'text-slate-500 bg-slate-50 !border-slate-500'
                    };
                @endphp

                <flux:card
                    class="px-3 py-2 min-w-[150px] flex flex-col items-center justify-center text-center 
                                                                                                                                                                                                       !border {{ $color }}">
                    <div class="text-[11px] font-medium uppercase">
                        {{ $kpi['label'] }}
                    </div>
                    @if ($kpi['unit'] !== 'Op')
                        <div class="flex items-center justify-center">
                            <span class="text-lg font-bold">
                                {{ $kpi['value'] }}
                            </span>
                            <span class="text-sm font-medium opacity-70">
                                {{ $kpi['unit'] }}
                            </span>
                        </div>
                    @else
                        <div class="flex items-center gap-1 justify-center">
                            <span class="text-lg font-bold">
                                {{ $kpi['value'] }}
                            </span>
                            <span class="text-sm font-medium opacity-70">
                                {{ $kpi['unit'] }}
                            </span>
                        </div>
                    @endif
                </flux:card>
            @endforeach
        </div>
    </div>

    {{-- MAIN GRID --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
        <flux:card class="bg-white dark:bg-neutral-900 shadow-sm xl:col-span-3 overflow-hidden">
            <flux:heading size="md" class="font-semibold">
                Yamazumi — Sebelum vs Sesudah Kaizen
            </flux:heading>

            <div wire:ignore class="mt-4 border-t flex justify-center items-center w-full">
                <div id="comparisonChart" class="h-70 w-full"></div>
            </div>
        </flux:card>


        {{-- METRICS --}}
        <flux:card class="bg-white dark:bg-neutral-900 shadow-sm">
            <flux:heading size="md" class="mb-4 font-semibold">
                Perbandingan Metrics
            </flux:heading>

            <div class="border-t pt-2"></div>
            <flux:table>
                <flux:table.rows>

                    @foreach($this->metrics as $metric)

                        @php
                            $label = $metric['label'];
                            $before = (float) preg_replace('/[^0-9.]/', '', $metric['before']);
                            $after = (float) preg_replace('/[^0-9.]/', '', $metric['after']);

                            $improved = false;

                            /* KPI Direction Rules */
                            if (in_array($label, ['Line Efficiency', 'Output / Hari', 'Op. Teoritis', 'Total NVA Saving', 'Overall MP Balance'])) {
                                $improved = $after > $before;
                            } else {
                                $improved = $after < $before;
                            }

                            $status = $improved ? 'Improved' : 'Decreased';
                            $color = $improved ? 'text-green-500' : 'text-red-500';
                            $icon = $improved ? 'up' : 'down';
                        @endphp

                        <flux:table.row class="hover:bg-slate-50 dark:hover:bg-neutral-800 transition">
                            <flux:table.cell class="py-3">
                                <div class="flex flex-col gap-1">
                                    <span class="text-xs uppercase font-semibold">
                                        {{ $metric['label'] }}
                                    </span>
                                    <span class="line-through text-red-400 text-sm">
                                        {{ $metric['before'] }}
                                    </span>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:icon.arrow-long-right variant="micro" />
                            </flux:table.cell>
                            <flux:table.cell class="py-3">
                                <div class="flex flex-col items-center">
                                    <span class="font-semibold text-lg">
                                        {{ $metric['after'] }}
                                    </span>
                                    <span class="text-xs flex items-center gap-1 {{ $color }}">
                                        @if($icon === 'up')
                                            <flux:icon.arrow-up variant="micro" />
                                        @else
                                            <flux:icon.arrow-down variant="micro" />
                                        @endif
                                        {{ $status }}
                                    </span>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        {{-- KAIZEN CARD --}}
        <flux:card class="bg-white dark:bg-neutral-900 shadow-sm overflow-hidden">
            <flux:heading size="md" class="font-semibold mb-4">
                <div class=" flex justify-between items-center">
                    <span>Rekomendasi Kaizen</span>
                    <flux:badge color="yellow" size="sm">{{ count($this->kaizen) }} Aksi</flux:badge>
                </div>
                <flux:subheading class="flex items-center text-xs text-neutral-500">
                    <div class="flex space-x-1">
                        <span>Diurutkan: Bottleneck (CV% ↓)</span>
                        <flux:icon.arrow-long-right variant="micro" />
                        <span>At-Risk</span>
                        <span>·</span>
                        <span>NVA: eliminasi</span>
                        <span>·</span>
                        <span>N-NVA: −20%</span>
                    </div>
                </flux:subheading>
            </flux:heading>

            <div class="border-t pt-4 space-y-6">
                @foreach($this->kaizen->groupBy('priority') as $priority => $actions)
                    @php
                        $first = $actions->first();
                    @endphp

                    <div class="flex items-center gap-2 text-xs font-mono text-neutral-500">
                        <span class="text-red-500">●</span>
                        <span class="tracking-wider">
                            PRIORITAS {{ $priority }} –
                            {{ strtoupper($first['station']) }}
                        </span>
                        <span class="text-red-500 font-semibold">
                            {{ strtoupper($first['status']) }}
                        </span>
                        <span>
                            CV {{ number_format($first['cv'], 1) }}%
                        </span>
                    </div>

                    {{-- Actions --}}
                    <div class="space-y-3">
                        @foreach($actions as $act)
                            <flux:card class="border-l-4 !border-amber-400 pl-4 py-3 bg-slate-50 dark:bg-neutral-900">
                                <div class="flex items-start gap-3">
                                    <flux:badge size="sm" color="{{ $act['kategori'] == 'NVA' ? 'red' : 'yellow' }}">
                                        {{ $act['kategori'] }}
                                    </flux:badge>
                                    <div class="flex-1">
                                        <div class="font-semibold text-sm">
                                            {{ $act['task'] }}
                                            <span class="text-xs text-neutral-400 ml-1">
                                                {{ $act['pct'] }}
                                            </span>
                                        </div>

                                        <div class="text-xs font-mono text-neutral-500 mt-1">
                                            {{ $act['before'] }}s
                                            <flux:icon.arrow-long-right variant="micro" />
                                            <span class="text-green-600 font-semibold">
                                                {{ $act['after'] }}s
                                            </span>
                                            <span class="text-green-600 ml-2">
                                                (-{{ $act['saving'] }}s saved)
                                            </span>
                                        </div>

                                        @if($act['metode'])
                                            <div class="text-xs text-neutral-400 mt-1">
                                                {{ $act['metode'] }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </flux:card>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </flux:card>

        {{-- REDISTRIBUSI CARD --}}
        <flux:card class="bg-white dark:bg-neutral-900 shadow-sm overflow-hidden">
            <flux:heading size="md" class="font-semibold mb-4">
                <div class="flex justify-between items-center">
                    <span>Man Power Balancing</span>
                    <flux:badge color="blue" size="sm">
                        Input: {{ $mpAktual }} op · Assigned: {{ $mpAssigned }} op ·
                        {{ number_format($mpBalance, 1) }}% Overall
                    </flux:badge>
                </div>
                <flux:subheading class="flex items-center text-xs text-neutral-500">
                    Utilisasi operator per stasiun pasca kaizen
                </flux:subheading>
            </flux:heading>
            <div class="border-t pt-4 space-y-3">

                @foreach($this->elementsData as $el)

                    @php
                        $pct = $el['mp_balance_pct'] ?? 0;

                        // warna progress bar
                        $color = match ($el['mp_utilized']) {
                            'Optimal' => 'green',
                            'Baik' => 'amber',
                            'Underutilized' => 'red',
                            default => 'slate'
                        };

                        // badge status kaizen
                        $clr = match ($el['status']) {
                            'Resolved' => 'green',
                            'Still Bottleneck' => 'yellow',
                            'No Action' => 'red',
                            default => 'slate'
                        };
                    @endphp

                    <flux:card class="px-4 py-3 bg-bg-white dark:bg-neutral-700 shadow-sm">
                        <div class="flex justify-between items-center mb-1">
                            <div class="text-sm font-semibold">
                                {{ $el['station_name'] }}
                                @if($el['nvaDOM'] === 1)
                                    <flux:badge size="sm" color="{{ $clr }}">nva-Dom {{ $el['nvaPctBefore'] }}%</flux:badge>
                                @endif
                            </div>

                            <div class="text-xs text-neutral-500 dark:text-neutral-200">
                                CT {{ number_format($el['ct_after'], 1) }}s /
                                {{ $el['mp_assigned'] }} op =
                                <span class="font-semibold">
                                    {{ number_format($el['ct_efektif'], 1) }}s
                                </span>
                                <span class="ml-2 text-{{ $color }}-600 font-semibold">
                                    {{ number_format($pct, 1) }}%
                                </span>
                            </div>

                        </div>
                        {{-- Progress bar --}}
                        <div class="w-full h-2 bg-gray-200 dark:bg-neutral-700 rounded-full">
                            <div class="h-2 rounded-full bg-{{ $color }}-500 transition-all duration-500"
                                style="width: {{ min($pct, 100) }}%">
                            </div>
                        </div>

                        <div class="flex justify-end items-center mt-2 text-xs">
                            <span class="text-neutral-500 dark:text-neutral-200">
                                {{ $el['mp_utilized'] }}
                            </span>
                        </div>
                    </flux:card>
                @endforeach

                <div class="flex flex-wrap items-stretch gap-3 w-full">
                    @foreach ($this->balancing as $kpi)
                        @php
                            $color = match ($kpi['color']) {
                                'red' => 'text-red-500 bg-red-50 !border-red-500',
                                'green' => 'text-green-500 bg-green-50 !border-green-500',
                                'cyan' => 'text-cyan-500 bg-cyan-50 !border-cyan-500',
                                default => 'text-slate-500 bg-slate-50 !border-slate-500',
                            };
                        @endphp

                        <flux:card
                            class="flex-1 px-3 py-2 flex flex-col items-center justify-center text-center
                                                                                                               border {{ $color }}">
                            <div class="text-[11px] font-medium uppercase">
                                {{ $kpi['label'] }}
                            </div>
                            @if ($kpi['unit'] !== 'Op')
                                <div class="flex items-center justify-center">
                                    <span class="text-lg font-bold">
                                        {{ $kpi['value'] }}
                                    </span>
                                    <span class="text-sm font-medium opacity-70">
                                        {{ $kpi['unit'] }}
                                    </span>
                                </div>

                            @else
                                <div class="flex items-center gap-1 justify-center">
                                    <span class="text-lg font-bold">
                                        {{ $kpi['value'] }}
                                    </span>
                                    <span class="text-sm font-medium opacity-70">
                                        {{ $kpi['unit'] }}
                                    </span>
                                </div>
                            @endif
                            <div class="text-[11px] font-medium">
                                {{ $kpi['note'] }}
                            </div>

                        </flux:card>
                    @endforeach

                    @if($this->mpAktual < $this->mpAssigned)
                        <flux:card class="bg-amber-50 border border-amber-200 p-4 flex flex-col gap-2">
                            <div class="text-sm text-neutral-700">
                                <span class="font-semibold text-amber-700">Rekomendasi MP : </span>
                                Line dapat berjalan optimal dengan <strong>{{ $this->mpAktual }}</strong>
                                <strong>operator</strong>
                                (saat ini <strong>{{ $this->mpAssigned }}</strong>).
                                Potensi efisiensi <strong>{{ $this->mpAssigned - $this->mpAktual }}</strong>
                                <strong>operator</strong>
                                dapat direalokasi ke line lain.
                            </div>
                        </flux:card>
                    @elseif($this->mpAktual > $this->mpAssigned)
                        <flux:card class="bg-red-50 border border-red-200 p-4 flex flex-col gap-2">
                            <div class="text-sm text-neutral-700">
                                <span class="font-semibold text-red-700">Rekomendasi MP : </span>
                                Line membutuhkan minimal <strong>{{ $this->mpAktual }}</strong> <strong>operator</strong>
                                (saat ini hanya <strong>{{ $this->mpAssigned }}</strong>).
                                Tambahkan <strong>{{ $this->mpAktual - $this->mpAssigned }}</strong>
                                <strong>operator</strong>
                                agar line dapat berjalan optimal.
                            </div>
                        </flux:card>
                    @else
                        <flux:card class="bg-green-50 border border-green-200 p-4 flex flex-col gap-2">
                            <div class="text-sm text-neutral-700">
                                <span class="font-semibold text-green-700">Rekomendasi MP : </span>
                                Jumlah <strong>operator</strong> sudah sesuai (<strong>{{ $this->mpAktual }}</strong>).
                                Line berjalan seimbang dengan kebutuhan teoritis.
                            </div>
                        </flux:card>
                    @endif
                </div>
            </div>
        </flux:card>

    </div>

    <flux:card class="bg-white dark:bg-neutral-900 shadow-sm xl:col-span-2">
        <flux:heading size="md" class="mb-4 flex items-center justify-between">
            <div class="font-semibold">
                Detail Elemen Kerja per Stasiun
                <flux:subheading class="flex items-center text-xs text-neutral-500 space-x-2">
                    <span>Cycle-normalized duration</span>
                    <span>·</span>
                    <span>durasi per siklus</span>
                </flux:subheading>
            </div>
        </flux:heading>

        <flux:table>
            <flux:table.columns class="font-medium bg-gray-100 dark:bg-neutral-700">
                <flux:table.column class="!px-4">Stasiun</flux:table.column>
                <flux:table.column align="center">CT Before</flux:table.column>
                <flux:table.column align="center">CT After</flux:table.column>
                <flux:table.column align="center">MP Assigned</flux:table.column>
                <flux:table.column align="center">CT Efektif</flux:table.column>
                <flux:table.column align="center">vs Takt</flux:table.column>
                <flux:table.column align="center">Status</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($this->elementsData as $el)
                    <flux:table.row
                        class="hover:bg-gray-50 dark:hover:bg-neutral-800 transition 
                                                                                                                                                   odd:bg-white even:bg-gray-50 dark:odd:bg-gray-900/50 dark:even:bg-gray-950">
                        <flux:table.cell class="!font-medium !px-4">{{ $el['station_name'] }}</flux:table.cell>
                        <flux:table.cell align="center">{{ number_format($el['ct_before'], 1) }}s</flux:table.cell>
                        <flux:table.cell align="center">{{ number_format($el['ct_after'], 1) }}s</flux:table.cell>
                        <flux:table.cell align="center">{{ $el['mp_assigned'] }}</flux:table.cell>
                        <flux:table.cell align="center">{{ number_format($el['ct_efektif'], 1) }}s</flux:table.cell>
                        <flux:table.cell align="center" class="!text-green-500">{{ number_format($el['vs_takt'], 1) }}s
                        </flux:table.cell>
                        <flux:table.cell align="center">
                            <flux:badge size="sm"
                                color="{{ $el['status'] === 'No Action' ? 'zinc' : ($el['status'] === 'Resolved' ? 'green' : 'yellow') }}">
                                {{ $el['status'] }}
                            </flux:badge>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        const chartData = @json($this->chartData);

        const stations = chartData.stations;
        const comparisonBefore = chartData.beforeData;
        const comparisonAfter = chartData.afterData;

        const taktTime = @json($taktTime);

        const comparisonOptions = {

            chart: {
                type: 'area',
                height: 450,
                animations: { enabled: true, easing: 'easeinout', speed: 800 }
            },

            series: [
                { name: 'Sebelum', data: comparisonBefore },
                { name: 'Sesudah', data: comparisonAfter }
            ],

            colors: ['#94a3b8', '#16a34a'],

            stroke: {
                curve: 'smooth',
                width: 3
            },

            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'light',
                    type: 'vertical',
                    shadeIntensity: 0.5,
                    gradientToColors: ['#cbd5e1', '#bbf7d0'],
                    opacityFrom: 0.4,
                    opacityTo: 0.1,
                    stops: [0, 100]
                }
            },

            markers: {
                size: 6,
                strokeColors: '#fff',
                strokeWidth: 2,
                hover: { size: 8 }
            },

            xaxis: {
                categories: stations,
                labels: {
                    fontWeight: 600,
                    fontSize: '12px',
                    rotate: 45
                }
            },

            yaxis: {
                min: 0,
                labels: {
                    formatter: val => val + ' s'
                }
            },

            tooltip: {
                shared: true,
                intersect: false,
                theme: 'dark'
            },

            grid: {
                borderColor: 'rgba(255,255,255,0.1)',
                strokeDashArray: 4,
                padding: { left: 20, right: 60 }
            },

            annotations: {
                yaxis: [
                    {
                        y: taktTime,
                        borderColor: '#ef4444',
                        borderWidth: 1,
                        strokeDashArray: 4,
                        label: {
                            text: 'Takt Time = ' + taktTime.toFixed(1) + 's',
                            offsetY: -2,
                            style: {
                                background: '#ef4444',
                                color: '#fff',
                                fontSize: '10px',
                                fontWeight: 500
                            }
                        }
                    }
                ]
            },

            legend: {
                position: 'bottom'
            }

        };

        new ApexCharts(
            document.querySelector("#comparisonChart"),
            comparisonOptions
        ).render();

    });
</script>