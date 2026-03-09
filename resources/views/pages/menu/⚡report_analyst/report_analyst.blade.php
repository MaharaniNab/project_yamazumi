<div class="flex flex-col gap-6">
    @php
        $taktTime = 216.0;
        $operators = 6;
        $target = 120;

        $stations = [
            'Jahit Pasang Lengan',
            'Jahit Panjang Facing',
            'Obras Tepi Kain',
            'Pasang Furing',
            'Setrika Pressing',
            'QC & Finishing',
        ];

        $meanCT = [248.5, 235, 200, 228, 175, 140];
        $robustCT = [280, 260, 210, 250, 200, 160];
        $cvData = [8.9, 11.2, 14.1, 22.5, 5.2, 3.8];

    @endphp
    {{-- HEADER --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="lg" class="font-semibold">
                Line Jas B — Sewing Dept.
            </flux:heading>
            <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-neutral-500 mt-1 font-medium">
                Analisis Variabilitas & Robust Line Balancing ·
                {{ $target }} pcs/hari · {{ $operators }} Operator
            </p>
        </div>
        <div class="flex gap-2">
            <flux:badge size="sm" rounded icon="exclamation-triangle" variant="micro" color="red">Bottleneck Aktif
            </flux:badge>
            <flux:badge size="sm" rounded icon="exclamation-triangle" variant="micro" color="amber">High Risk Station
            </flux:badge>
        </div>
    </div>

    {{-- KPI GRID --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
        @php
            $kpis = [
                [
                    'label' => 'Line Efficiency',
                    'value' => 72.4,
                    'target' => 75,
                    'unit' => '%',
                    'direction' => 'higher',
                    'accent' => '#3D7A99'
                ],
                [
                    'label' => 'Balance Delay',
                    'value' => 27.6,
                    'target' => 15,
                    'unit' => '%',
                    'direction' => 'lower',
                    'accent' => '#2C8C83'
                ],
                [
                    'label' => 'Smoothness Index',
                    'value' => 48.3,
                    'target' => 40,
                    'unit' => '%',
                    'direction' => 'lower',
                    'accent' => '#FA6868'
                ],
                [
                    'label' => 'Output Aktual/Hari',
                    'value' => 108,
                    'target' => 120,
                    'unit' => 'pcs',
                    'direction' => 'higher',
                    'accent' => '#312E81'
                ],
            ];
        @endphp

        @foreach($kpis as $kpi)
            @php
                $delta = $kpi['value'] - $kpi['target'];

                // Tentukan status berdasarkan direction
                if ($kpi['direction'] === 'higher') {
                    $isGood = $delta >= 0;
                } else {
                    $isGood = $delta <= 0;
                }

                if ($delta == 0) {
                    $arrow = '→';
                    $color = 'text-yellow-500';
                } elseif ($isGood) {
                    $arrow = '↑';
                    $color = 'text-green-500';
                } else {
                    $arrow = '↓';
                    $color = 'text-red-500';
                }

                $note = "vs target {$kpi['target']}{$kpi['unit']}";
            @endphp

            <div class="group cursor-pointer transition duration-500 hover:bg-{{ $kpi['accent'] }}/10 hover:-translate-y-2">
                <flux:card class="relative overflow-hidden py-4 px-6 rounded-2xl shadow-md transition-all duration-500"
                    style="border-top: 4px solid {{ $kpi['accent'] }};"> {{-- Glow hover --}} <div
                        class="absolute -top-10 -right-10 w-25 h-25 rounded-full blur-3xl opacity-0 group-hover:opacity-100 transition duration-500"
                        style="background-color: {{ $kpi['accent'] }}33;"> </div>

                    <div class="text-md font-semibold text-gray-700 dark:text-neutral-200">
                        {{ $kpi['label'] }}
                    </div>

                    <div class="mt-3 text-4xl font-bold tracking-tight text-gray-900 dark:text-white">
                        {{ $kpi['value'] }}
                        <span class="text-base font-medium text-gray-500 dark:text-neutral-400">
                            {{ $kpi['unit'] }}
                        </span>
                    </div>

                    <div class="mt-3 text-xs uppercase flex items-center gap-1 text-gray-500 dark:text-neutral-400">

                        <span class="{{ $color }} font-semibold">
                            {{ $arrow }} {{ abs($delta) }}{{ $kpi['unit'] }}
                        </span>

                        <span>
                            {{ $note }}
                        </span>

                    </div>

                </flux:card>
            </div>

        @endforeach
    </div>

    {{-- MAIN GRID --}}
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-6">
        <flux:card class="bg-white dark:bg-neutral-900 shadow-sm xl:col-span-3">
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="md" class="font-semibold">
                        Cycle Time per Stasiun
                    </flux:heading>
                    <p class="text-xs text-neutral-500 font-medium flex items-center gap-2">
                        <span>Mean CT vs Robust CT (μ+2σ)</span>
                        <span>·</span>
                        <span>Takt Time: {{ number_format($taktTime, 1) }}s</span>
                    </p>
                </div>
            </div>

            <div wire:ignore
                class="mt-4 border-t-2 border-gray-200 dark:border-neutral-800 dark:border-neutral-800 flex justify-center items-center w-full">
                <div id="ctChart" class="h-70 w-full">
                </div>
            </div>
        </flux:card>
        <flux:card class="bg-white dark:bg-neutral-900 shadow-sm">
            <flux:heading size="md" class="mb-4 font-semibold">
                Status Stasiun
                <flux:subheading class="text-xs text-neutral-500 font-medium">
                    {{ $operators }} Operator · Take {{ number_format($taktTime, 1) }}s
                </flux:subheading>
            </flux:heading>
            <div class="pt-4 border-t-2 border-gray-200 dark:border-neutral-800 dark:border-neutral-800"></div>
            <flux:table>
                <flux:table.rows>
                    @foreach($stations as $i => $station)
                        @php
                            $ct = $meanCT[$i];
                            $cv = $cvData[$i];
                            $diff = $ct - $taktTime;

                            if ($ct > $taktTime) {
                                $status = 'BOTTLENECK';
                                $color = 'red';
                            } elseif ($ct > $taktTime * 0.9) {
                                $status = 'AT-RISK';
                                $color = 'amber';
                            } elseif ($ct < $taktTime * 0.75) {
                                $status = 'UNDERLOADED';
                                $color = 'blue';
                            } else {
                                $status = 'BALANCED';
                                $color = 'green';
                            }
                        @endphp
                        <flux:table.row class="border border-{{ $color }}-200 dark:border-{{ $color }}-700">
                            <flux:table.cell>
                                <div class="flex items-start gap-3">
                                    <div class="w-2 h-2 mt-2 rounded-full bg-{{ $color }}-500"></div>
                                    <div>
                                        <div class="font-medium text-sm">
                                            {{ $station }}
                                        </div>

                                        <div class="flex items-center gap-2 text-xs mt-1">
                                            <span
                                                class="px-2 py-0.5 rounded-md bg-{{ $color }}-200 text-{{ $color }}-700 font-medium">
                                                {{ $status }}
                                            </span>

                                            <span class="text-gray-400 dark:text-neutral-500">
                                                CV: {{ number_format($cv, 1) }}%
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </flux:table.cell>

                            {{-- Cycle Time --}}
                            <flux:table.cell align="center">
                                <div class="font-semibold text-sm">
                                    {{ number_format($ct, 1) }}s
                                </div>

                                <div class="text-xs text-{{ $color }}-700">
                                    @if($diff > 0)
                                        +{{ number_format($diff, 1) }}s overflow
                                    @else
                                        Idle {{ number_format(abs($diff), 1) }}s
                                    @endif
                                </div>
                            </flux:table.cell>

                        </flux:table.row>

                    @endforeach

                </flux:table.rows>
            </flux:table>
        </flux:card>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-5 gap-6">
        <flux:card class="bg-white dark:bg-neutral-900 shadow-sm xl:col-span-2">
            <flux:heading size="md" class="mb-4 font-semibold">
                Elemen Kerja — Jahit Pasang Lengan
                <div class="justify-between items-center flex">
                    <flux:subheading class="text-xs text-neutral-500 font-medium">
                        Bottleneck Station · CT Mean {{ number_format($meanCT[0], 1) }}s
                    </flux:subheading>
                    <flux:badge color="red" size="sm">
                        HIGH RISK · CV 8{{ number_format($cvData[0], 1) }}%
                    </flux:badge>
                </div>
            </flux:heading>
            <div class="mt-3 border-t-2 border-gray-200 dark:border-neutral-800 dark:border-neutral-800"></div>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column align="left">Elemen Kerja</flux:table.column>
                    <flux:table.column align="center">Kategori</flux:table.column>
                    <flux:table.column align="center">Proporsi</flux:table.column>
                    <flux:table.column align="center">Durasi/σ</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    <flux:table.row class="hover:bg-gray-100 dark:hover:bg-neutral-800 transition">
                        <flux:table.cell align="left">Proses Jahit</flux:table.cell>
                        <flux:table.cell align="center">
                            <flux:badge color="green" size="sm">VA</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="center">62.0s</flux:table.cell>
                        <flux:table.cell align="center">198.0s
                            <p class="text-xs text-amber-500">σ±14.2s</p>
                        </flux:table.cell>
                    </flux:table.row>
                    <flux:table.row class="hover:bg-gray-100 dark:hover:bg-neutral-800 transition">
                        <flux:table.cell align="left">Pengecekan Barang</flux:table.cell>
                        <flux:table.cell align="center">
                            <flux:badge color="yellow" size="sm">N-NVA</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="center">38.0s</flux:table.cell>
                        <flux:table.cell align="center">22.0s
                            <p class="text-xs text-amber-500">σ±7.2s</p>
                        </flux:table.cell>
                    </flux:table.row>
                    <flux:table.row class="hover:bg-gray-100 dark:hover:bg-neutral-800 transition">
                        <flux:table.cell align="left">Meletakkan Barang</flux:table.cell>
                        <flux:table.cell align="center">
                            <flux:badge color="red" size="sm">NVA</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="center">20.0s</flux:table.cell>
                        <flux:table.cell align="center">16.0s
                            <p class="text-xs text-amber-500">σ±4.0s</p>
                        </flux:table.cell>
                    </flux:table.row>
                    <flux:table.row class="hover:bg-gray-100 dark:hover:bg-neutral-800 transition">
                        <flux:table.cell align="left">Mengambil Produk</flux:table.cell>
                        <flux:table.cell align="center">
                            <flux:badge color="red" size="sm">NVA</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="center">38.0s</flux:table.cell>
                        <flux:table.cell align="center">12.6s
                            <p class="text-xs text-amber-500">σ±3.6s</p>
                        </flux:table.cell>
                    </flux:table.row>
                </flux:table.rows>
            </flux:table>
            <div
                class="flex flex-wrap items-center gap-3 p-4 text-xs bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300 dark:bg-neutral-800 dark:text-neutral-300">
                <span>
                    NVA Total:
                    <span class="font-semibold text-red-600">62.0s</span>
                </span>

                <span class="text-gray-400 dark:text-neutral-500">•</span>

                <span>
                    Potensi reduksi ~40% dengan perbaikan layout
                </span>

                <span class="text-gray-400 dark:text-neutral-500">→</span>

                <span class="font-semibold text-green-600">
                    penghematan ±11.4s / siklus
                </span>

            </div>
        </flux:card>

        {{-- COMPARISON CHART --}}
        <flux:card class="bg-white dark:bg-neutral-900 shadow-sm xl:col-span-3 overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <flux:heading size="md" class="font-semibold">
                        Perbandingan Optimasi
                    </flux:heading>

                    <flux:subheading class="text-xs text-neutral-500 font-medium">
                        Sebelum vs Sesudah Robust Balancing
                    </flux:subheading>
                </div>

                <flux:badge size="sm" color="zinc">
                    Z: 312 → 218
                </flux:badge>
            </div>

            <div class="mt-4 border-t-2 border-gray-200 dark:border-neutral-800 dark:border-neutral-800"></div>

            {{-- Chart --}}
            <div class="p-2 pb-0" wire:ignore>
                <div id="comparisonChart" class="h-64"></div>
            </div>

            {{-- Comparison Strip --}}
            @php
                $metrics = [
                    [
                        'label' => 'Line Efficiency',
                        'before' => '72.4%',
                        'after' => '88.6%',
                        'icon' => 'arrow-up',
                        'delta' => '16.2%',
                    ],
                    [
                        'label' => 'Balance Delay',
                        'before' => '27.6%',
                        'after' => '11.4%',
                        'icon' => 'arrow-down',
                        'delta' => '16.2%',
                    ],
                    [
                        'label' => 'Output / Hari',
                        'before' => '108',
                        'after' => '122 pcs',
                        'icon' => 'arrow-up',
                        'delta' => '14 pcs',
                    ],
                ];
            @endphp

            <div
                class="grid grid-cols-3 flex items-center justify-center gap-6 py-2 bg-gray-50 dark:bg-neutral-800 border-t border-gray-200 dark:border-neutral-800 dark:border-neutral-800">

                @foreach ($metrics as $m)
                    <div class="flex flex-col items-center">
                        <div class="text-xs uppercase tracking-wider text-gray-400 font-mono">
                            {{ $m['label'] }}
                        </div>
                        <div class="font-mono text-md font-semibold text-gray-800 dark:text-neutral-200">
                            {{ $m['before'] }} →
                            <span class="text-green-600">{{ $m['after'] }}</span>
                        </div>
                        <flux:badge color="green" class="text-[11px]">
                            <flux:icon icon="{{ $m['icon'] }}" variant="micro" />
                            {{ $m['delta'] }}
                        </flux:badge>
                    </div>
                @endforeach

            </div>

        </flux:card>

    </div>
</div>

{{-- ===================== APEXCHART SCRIPT ===================== --}}

<script>
    document.addEventListener('DOMContentLoaded', function () {

        const taktTime = @json($taktTime);
        const stations = @json($stations);
        const meanData = @json($meanCT);
        const robustData = @json($robustCT);

        // Tentukan status stasiun
        const meanStatus = meanData.map(v => {
            if (v > taktTime) return 'bottleneck';
            if (v > taktTime * 0.9) return 'at-risk';
            return 'balanced';
        });

        // Warna marker sesuai status
        const markerColors = meanStatus.map(s => {
            if (s === 'bottleneck') return '#ef4444'; // merah
            if (s === 'at-risk') return '#f59e0b';    // amber
            return '#10b981';                          // hijau
        });

        // ================= MAIN CHART =================
        const ctOptions = {
            chart: {
                type: 'area',
                height: 450,
                width: '100%',
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 600
                },
            },

            series: [
                { name: 'Mean Cycle Time', data: meanData },
                { name: 'Robust CT (μ+2σ)', data: robustData }
            ],
            markers: {
                size: 0 // hilangkan titik point
            },

            xaxis: {
                categories: stations,
                labels: {
                    style: { colors: markerColors, fontWeight: 600, fontSize: '12px' },
                    trim: true,
                    rotate: 45
                },
            },

            stroke: { width: [3, 3], dashArray: [0, 6], curve: 'smooth' },

            colors: ['#1e3a8a', '#38bdf8'],

            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'light',
                    type: 'vertical',
                    shadeIntensity: 0.5,
                    gradientToColors: ['#18295e', '#307da1'],
                    opacityFrom: 0.4,
                    opacityTo: 0.1,
                    stops: [0, 100]
                }
            },

            grid: {
                borderColor: '#e5e7eb',
                strokeDashArray: 4,
                padding: { left: 20, right: 60 }
            },

            annotations: {
                yaxis: [
                    {
                        y: taktTime,
                        borderColor: '#ef4444',
                        strokeDashArray: 4,
                        label: {
                            borderColor: '#ef4444',
                            style: {
                                color: '#fff',
                                background: '#ef4444',
                                borderRadius: 6
                            },
                            text: 'Takt Time = ' + taktTime.toFixed(1) + 's'
                        }
                    },
                    {
                        y: taktTime,
                        y2: Math.max(...robustData) + 20,
                        fillColor: '#ef4444',
                        opacity: 0.05
                    }
                ],

                points: stations.map((station, i) => {

                    let color = '#10b981'
                    let text = 'Balanced'

                    if (meanStatus[i] === 'bottleneck') {
                        color = '#ef4444'
                        text = 'Bottleneck'
                    } else if (meanStatus[i] === 'at-risk') {
                        color = '#f59e0b'
                        text = 'At-Risk'
                    }

                    return {
                        x: station,
                        y: meanData[i],

                        marker: { size: 0 },

                        label: {
                            text: text,
                            borderColor: color,
                            offsetY: 30,
                            style: {
                                color: '#fff',
                                background: color
                            }
                        }
                    }
                })
            },

            tooltip: {
                theme: 'dark',
                custom: function ({ series, seriesIndex, dataPointIndex, w }) {
                    const station = stations[dataPointIndex];
                    const val = series[seriesIndex][dataPointIndex];
                    const diff = val - taktTime;

                    const tags = ['BOTTLENECK', 'BOTTLENECK', 'AT-RISK', 'BOTTLENECK', 'BALANCED', 'UNDERLOADED'];
                    const cvs = ['8.9%', '11.2%', '14.1%', '22.5%', '5.2%', '3.8%'];

                    const status = diff > 0
                        ? `⚠ +${diff.toFixed(1)}s OVERFLOW`
                        : `✓ −${Math.abs(diff).toFixed(1)}s idle`;

                    return `
      <div style="
        background:#0B1628;
        padding:8px 10px;
        border-radius:6px;
        border:1px solid rgba(91,155,213,.2);
        color:#D4E1EF;
        font-size:10px;
        line-height:1.4;
        min-width:150px;
      ">
        <div style="color:#A8D4F5;font-weight:600;margin-bottom:4px">
          ${station}
        </div>

        <div style="margin-bottom:4px">
          ${w.config.series[seriesIndex].name}: 
          <b>${val}s</b> ${status}
        </div>

        <div style="color:#9CA3AF">
          Status: ${tags[dataPointIndex]} · CV: ${cvs[dataPointIndex]}
        </div>
      </div>`;
                }
            },
            legend: { position: 'bottom' }
        }
        // Render chart
        new ApexCharts(document.querySelector("#ctChart"), ctOptions).render();
        // ================= COMPARISON CHART =================
        const comparisonOptions = {
            chart: {
                type: 'area',
                height: 280,
                width: '100%',
                animations: { enabled: true, easing: 'easeinout', speed: 800 }
            },

            series: [
                { name: 'Sebelum', data: [72.4, 27.6, 108, 99, 18] },
                { name: 'Sesudah', data: [70.6, 11.4, 92, 88, 10] }
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
                    style: { colors: markerColors, fontWeight: 600, fontSize: '12px' },
                    trim: true,
                    rotate: 45
                },
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
                theme: 'dark',
                style: {
                    fontSize: '11px',
                    lineHeight: '0.4',
                    padding: '6px 8px', // lebih kecil dari default
                },
                y: {
                    formatter: function (val, opts) {
                        const index = opts.dataPointIndex;
                        const before = comparisonOptions.series[0].data[index];
                        const delta = val - before;
                        const sign = delta > 0 ? '+' : '';
                        return val + ' s (' + sign + delta.toFixed(1) + ' s)';
                    }
                }
            },

            grid: {
                borderColor: 'rgba(255,255,255,0.1)', // garis tipis semi-transparan
                strokeDashArray: 4,
                padding: { left: 20, right: 60 }
            },

            legend: { position: 'bottom' }
        };
        new ApexCharts(document.querySelector("#comparisonChart"), comparisonOptions).render();
    });
</script>