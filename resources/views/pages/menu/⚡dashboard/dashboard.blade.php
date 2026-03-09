<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="lg" class="font-semibold">
                Line Jas B — Sewing Dept.
            </flux:heading>

            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 font-medium">
                Analisis Variabilitas & Robust Line Balancing ·
                {{ $target }} pcs/hari · {{ $operators }} Operator
            </p>
        </div>

        <div class="flex gap-2">
            <flux:badge size="sm" rounded icon="exclamation-triangle" variant="micro" color="red">
                Bottleneck Aktif
            </flux:badge>

            <flux:badge size="sm" rounded icon="exclamation-triangle" variant="micro" color="amber">
                High Risk Station
            </flux:badge>
        </div>
    </div>


    {{-- KPI GRID --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">

        @foreach($kpis as $kpi)

            @php
                $delta = $kpi['value'] - $kpi['target'];

                $isGood = $kpi['direction'] === 'higher'
                    ? $delta >= 0
                    : $delta <= 0;

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


            <div class="group cursor-pointer transition duration-500 hover:-translate-y-2">

                <flux:card class="relative overflow-hidden py-4 px-6 rounded-2xl shadow-md transition-all duration-500"
                    style="border-top:4px solid {{ $kpi['accent'] }}">

                    <div class="text-md font-semibold text-gray-700 dark:text-neutral-200">
                        {{ $kpi['label'] }}
                    </div>

                    <div class="mt-3 text-4xl font-bold tracking-tight text-gray-900 dark:text-white">
                        {{ $kpi['value'] }}
                        <span class="text-base font-medium text-gray-500 dark:text-neutral-400">
                            {{ $kpi['unit'] }}
                        </span>
                    </div>

                    <div class="mt-3 text-xs uppercase flex items-center gap-1 text-gray-500">

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


        {{-- CHART --}}
        <flux:card class="bg-white dark:bg-neutral-900 shadow-sm xl:col-span-3">

            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="md" class="font-semibold">
                        Cycle Time per Stasiun
                    </flux:heading>

                    <p class="text-xs text-neutral-500 font-medium">
                        Mean CT vs Robust CT (μ+2σ) ·
                        Takt Time: {{ number_format($taktTime, 1) }}s
                    </p>

                </div>
            </div>


            <div wire:ignore class="mt-4 border-t flex justify-center items-center w-full">

                <div id="ctChart" class="h-70 w-full"></div>

            </div>

        </flux:card>



        {{-- STATION STATUS --}}
        <flux:card class="bg-white dark:bg-neutral-900 shadow-sm">

            <flux:heading size="md" class="mb-4 font-semibold">
                Status Stasiun

                <flux:subheading class="text-xs text-neutral-500 font-medium">
                    {{ $operators }} Operator · Takt {{ number_format($taktTime, 1) }}s
                </flux:subheading>

            </flux:heading>

            <div class="pt-4 border-t"></div>


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


                        <flux:table.row>

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

                                            <span class="text-gray-400">
                                                CV {{ number_format($cv, 1) }}%
                                            </span>

                                        </div>

                                    </div>

                                </div>

                            </flux:table.cell>


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



    {{-- WORK ELEMENT --}}
    <div class="grid grid-cols-1 xl:grid-cols-5 gap-6">

        <flux:card class="bg-white dark:bg-neutral-900 shadow-sm xl:col-span-2">

            <flux:heading size="md" class="mb-4 font-semibold">

                Elemen Kerja

                <flux:subheading class="text-xs text-neutral-500">
                    Bottleneck Station
                </flux:subheading>

            </flux:heading>


            <div class="border-t mb-3"></div>


            <flux:table>

                <flux:table.columns>
                    <flux:table.column>Elemen</flux:table.column>
                    <flux:table.column align="center">Kategori</flux:table.column>
                    <flux:table.column align="center">Durasi</flux:table.column>
                    <flux:table.column align="center">Total</flux:table.column>
                </flux:table.columns>


                <flux:table.rows>

                    @foreach($elements as $el)
                        <flux:table.row>
                            <flux:table.cell>
                                {{ $el->elemen_kerja }}
                            </flux:table.cell>
                            <flux:table.cell align="center">
                                <flux:badge
                                    color="{{ $el->kategori_va == 'VA' ? 'green' : ($el->kategori_va == 'N-NVA' ? 'yellow' : 'red') }}">
                                    {{ $el->kategori_va }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell align="center">
                                {{ number_format($el->durasi_detik, 1) }}s
                            </flux:table.cell>
                            <flux:table.cell align="center">
                                {{ number_format($el->total_durasi, 1) }}s
                                <p class="text-xs text-amber-500">
                                    σ±{{ number_format($el->std_dev, 1) }}s
                                </p>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>

        {{-- COMPARISON --}}
        <flux:card class="bg-white dark:bg-neutral-900 shadow-sm xl:col-span-3 overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <flux:heading size="md" class="font-semibold">
                        Perbandingan Optimasi
                    </flux:heading>
                    <flux:subheading class="text-xs text-neutral-500">
                        Sebelum vs Sesudah
                    </flux:subheading>
                </div>
            </div>

            <div class="mt-4 border-t"></div>

            <div class="p-2 pb-0" wire:ignore>
                <div id="comparisonChart" class="h-64"></div>
            </div>


            <div class="grid grid-cols-3 gap-6 py-2 bg-gray-50 dark:bg-neutral-800 border-t">
                @foreach($metrics as $m)

                    <div class="flex flex-col items-center">
                        <div class="text-xs uppercase text-gray-400 font-mono">
                            {{ $m['label'] }}
                        </div>

                        <div class="font-mono text-md font-semibold">
                            {{ $m['before'] }} →
                            <span class="text-green-600">
                                {{ $m['after'] }}
                            </span>

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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const taktTime = @json($taktTime);
        const stations = @json($stations);
        const meanData = @json($meanCT);
        const robustData = @json($robustCT);
        const cvData = @json($cvData);

        const comparisonBefore = @json($beforeData);
        const comparisonAfter = @json($afterData);

        // ================= STATUS STATION =================

        const meanStatus = meanData.map(v => {
            if (v > taktTime) return 'bottleneck'
            if (v > taktTime * 0.9) return 'at-risk'
            if (v < taktTime * 0.75) return 'underloaded'
            return 'balanced'

        })

        // ================= MARKER COLOR =================

        const markerColors = meanStatus.map(s => {
            if (s === 'bottleneck') return '#ef4444'
            if (s === 'at-risk') return '#f59e0b'
            if (s === 'underloaded') return '#3b82f6'
            return '#10b981'
        })
        // ================= MAIN CHART =================

        const ctOptions = {

            chart: {
                type: 'area',
                height: 450,
                width: '100%',
                animations: { enabled: true, easing: 'easeinout', speed: 600 }
            },

            series: [
                { name: 'Mean Cycle Time', data: meanData },
                { name: 'Robust CT (μ+2σ)', data: robustData }
            ],

            markers: { size: 0 },

            xaxis: {
                categories: stations,
                labels: {
                    style: {
                        colors: markerColors,
                        fontWeight: 600,
                        fontSize: '12px'
                    },
                    rotate: 45
                }
            },

            yaxis: {
                min: 0,
                labels: {
                    formatter: val => val + ' s'
                }
            },

            stroke: { width: [3, 3], dashArray: [0, 6], curve: 'smooth' },
            colors: ['#1e3a8a', '#38bdf8'],

            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'light',
                    type: 'vertical',
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

            // ================= TAKT LINE =================
            annotations: {
                yaxis: [
                    {
                        y: taktTime,
                        borderColor: '#ef4444',
                        strokeDashArray: 4,
                        label: {
                            style: { color: '#fff', background: '#ef4444' },
                            text: 'Takt Time = ' + taktTime.toFixed(1) + 's'
                        }
                    }
                ],

                // ================= STATION LABEL =================

                points: stations.map((station, i) => {
                    let color = '#10b981'
                    let text = 'Balanced'
                    if (meanStatus[i] === 'bottleneck') {
                        color = '#ef4444'
                        text = 'Bottleneck'
                    }
                    else if (meanStatus[i] === 'at-risk') {
                        color = '#f59e0b'
                        text = 'At Risk'
                    }
                    else if (meanStatus[i] === 'underloaded') {
                        color = '#3b82f6'
                        text = 'Underloaded'
                    }
                    return {
                        x: station,
                        y: meanData[i],
                        marker: { size: 0 },
                        label: {
                            text: text,
                            borderColor: color,
                            offsetY: 30,
                            style: { color: '#fff', background: color }
                        }
                    }
                })
            },

            // ================= TOOLTIP =================

            tooltip: {
                theme: 'dark',
                custom: function ({ series, seriesIndex, dataPointIndex, w }) {

                    const station = stations[dataPointIndex]
                    const val = series[seriesIndex][dataPointIndex]
                    const diff = val - taktTime
                    const cv = cvData[dataPointIndex]

                    const status =
                        diff > 0
                            ? `⚠ +${diff.toFixed(1)}s overflow`
                            : `✓ ${Math.abs(diff).toFixed(1)}s idle`

                    return `
                        <div style="
                        background:#0B1628;
                        padding:8px 10px;
                        border-radius:6px;
                        border:1px solid rgba(91,155,213,.2);
                        color:#D4E1EF;
                        font-size:10px;
                        line-height:1.4;
                        min-width:150px">

                        <div style="color:#A8D4F5;font-weight:600;margin-bottom:4px">
                        ${station}
                        </div>

                        <div style="margin-bottom:4px">
                        ${w.config.series[seriesIndex].name} :
                        <b>${val}s</b>
                        </div>

                        <div>
                        ${status}
                        </div>

                        <div style="color:#9CA3AF">
                        CV : ${cv}%
                        </div>

                        </div>

                        `
                }
            },
            legend: { position: 'bottom' }

        }
        // ================= RENDER =================
        new ApexCharts(
            document.querySelector("#ctChart"),
            ctOptions
        ).render();

        // ================= COMPARISON CHART =================

        const comparisonOptions = {

            chart: {
                type: 'area',
                height: 280,
                animations: { enabled: true, easing: 'easeinout', speed: 800 }
            },

            series: [
                { name: 'Sebelum', data: comparisonBefore },
                { name: 'Sesudah', data: comparisonAfter }
            ],

            colors: ['#94a3b8', '#16a34a'],

            stroke: { curve: 'smooth', width: 3 },

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
                    style: {
                        colors: markerColors,
                        fontWeight: 600,
                        fontSize: '12px'
                    },
                    trim: true,
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
                strokeDashArray: 4
            },

            legend: { position: 'bottom' }

        }

        new ApexCharts(
            document.querySelector("#comparisonChart"),
            comparisonOptions
        ).render();

    });
</script>