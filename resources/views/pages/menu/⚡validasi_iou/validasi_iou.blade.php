<div class="flex flex-col gap-8">
    <flux:heading size="lg" class="font-semibold">
        Validasi Temporal IoU
        <flux:subheading class="text-sm text-neutral-500">
            Perbandingan output CV vs ground truth stopwatch manual
        </flux:subheading>
    </flux:heading>


    {{-- KPI --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
        @foreach($kpis as $kpi)
            <div class="group cursor-pointer transition duration-500 hover:-translate-y-2">
                <flux:card
                    class="relative dark:bg-neutral-900 overflow-hidden py-4 px-6 rounded-2xl shadow-md transition-all duration-500"
                    style="border-top:4px solid {{ $kpi['accent'] }}">
                    {{-- LABEL --}}
                    <div class="text-md font-semibold text-gray-600 dark:text-neutral-300">
                        {{ $kpi['label'] }}
                    </div>

                    {{-- VALUE --}}
                    <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                        {{ is_float($kpi['value']) ? number_format($kpi['value'], 3) : $kpi['value'] }}
                        @if(!empty($kpi['unit']))
                            <span class="text-sm text-gray-500">
                                {{ $kpi['unit'] }}
                            </span>
                        @endif
                    </div>

                    {{-- NOTE / STATUS --}}
                    <div class="mt-3 text-sm text-gray-500">
                        @php
                            $noteColor =
                                str_contains($kpi['note'], 'Baik') ? 'green' :
                                (str_contains($kpi['note'], 'Cukup') ? 'yellow' :
                                    (str_contains($kpi['note'], 'Perbaikan') ? 'red' :
                                        'bg-gray-100 text-gray-700'));
                        @endphp
                        <flux:badge color="{{ $noteColor }}" size="sm">
                            {{ $kpi['note'] }}
                        </flux:badge>
                    </div>
                </flux:card>
            </div>
        @endforeach
    </div>

    {{-- MAIN CONTENT --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <flux:card class="bg-white dark:bg-neutral-900 shadow-sm overflow-hidden">
            <div class="flex flex-col">
                <flux:heading size="md" class="font-semibold mb-4">
                    Skor IoU per Aktivitas
                    <flux:subheading class="text-sm text-neutral-500">
                        Perbandingan output CV vs ground truth stopwatch manual
                    </flux:subheading>
                </flux:heading>
                <div class="pt-6 border-t"></div>

                <flux:table container:class="max-h-[60vh]">
                    <flux:table.columns sticky class="font-medium bg-gray-100 dark:bg-neutral-700">
                        <flux:table.column align="center" class="!px-4">Stasiun</flux:table.column>
                        <flux:table.column align="center">Aktivitas</flux:table.column>
                        <flux:table.column align="center">N-PRED</flux:table.column>
                        <flux:table.column align="center">N-GT</flux:table.column>
                        <flux:table.column align="center">IoU Score</flux:table.column>
                        <flux:table.column align="center" class="!px-4">Status</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach($this->iouResults as $row)
                            @php
                                $color =
                                    $row->avg_iou >= 0.70 ? 'green' :
                                    ($row->avg_iou >= 0.50 ? 'yellow' : 'red');
                                $status =
                                    $row->avg_iou >= 0.70 ? 'Baik' :
                                    ($row->avg_iou >= 0.50 ? 'Cukup' : 'Perlu Perbaikan');
                            @endphp

                            <flux:table.row
                                class="hover:bg-gray-50 dark:hover:bg-neutral-800 transition
                                                                                                odd:bg-white even:bg-gray-50 dark:odd:bg-gray-900/50 dark:even:bg-gray-950">
                                <flux:table.cell align="center" class="!font-semibold">
                                    {{ $row->station->station_name }}
                                </flux:table.cell>

                                <flux:table.cell align="center" class="font-medium">
                                    {{ $row->activity }}
                                </flux:table.cell>

                                <flux:table.cell align="center">
                                    {{ $row->n_samples_pred }}
                                </flux:table.cell>

                                <flux:table.cell align="center">
                                    {{ $row->n_samples_gt }}
                                </flux:table.cell>

                                <flux:table.cell align="center">
                                    <div class="flex flex-col items-center gap-1">
                                        <span class="font-semibold w-12">
                                            {{ number_format($row->avg_iou, 3) }}
                                        </span>
                                        <div class="w-32 h-2 bg-gray-200 dark:bg-neutral-700 rounded-full">
                                            <div class="h-2 rounded-full bg-{{ $color }}-500 transition-all duration-500"
                                                style="width: {{ $row->avg_iou * 100 }}%">
                                            </div>
                                        </div>
                                    </div>
                                </flux:table.cell>

                                <flux:table.cell align="center">
                                    <flux:badge color="{{ $color }}" size="sm">
                                        {{ $status }}
                                    </flux:badge>
                                </flux:table.cell>

                            </flux:table.row>

                        @endforeach

                    </flux:table.rows>
                </flux:table>
        </flux:card>
    </div>

    {{-- INPUT GROUND TRUTH --}}
    <flux:card class="bg-white dark:bg-neutral-900 shadow-sm overflow-hidden">
        <flux:heading size="md" class="font-semibold mb-4">
            Input Ground Truth
            <flux:subheading class="text-sm text-neutral-500">
                Masukkan data stopwatch manual
            </flux:subheading>
        </flux:heading>
        <div class="pt-6 border-t"></div>

        <flux:select label="Pilih Stasiun" wire:model="selectedStation" class="w-full" required>
            <flux:select.option value="">
                - Semua Stasiun -
            </flux:select.option>
            @foreach($stations as $station)
                <flux:select.option value="{{ $station->id }}">
                    {{ $station->station_name }}
                </flux:select.option>
            @endforeach
        </flux:select>
        @error('selectedStation')
            <span class="text-red-500 text-xs">
                {{ $message }}
            </span>
        @enderror

        {{-- INPUT SEGMENTS --}}
        <div class="mt-4 space-y-4">
            @foreach($segments as $i => $segment)
                <div class="grid grid-cols-12 gap-3 items-end" wire:key="segment-{{ $i }}">
                    <div class="col-span-5 space-y-1">
                        <flux:input label="Aktivitas" class="w-full" wire:model="segments.{{ $i }}.activity"
                            placeholder="Nama aktivitas" />

                        @error("segments.$i.activity")
                            <span class="text-red-500 text-xs">
                                {{ $message }}
                            </span>
                        @enderror
                    </div>

                    <div class="col-span-3 space-y-1">
                        <flux:input type="number" label="Start (s)" class="w-full" wire:model="segments.{{ $i }}.start"
                            placeholder="0" />
                        @error("segments.$i.start")
                            <span class="text-red-500 text-xs">
                                {{ $message }}
                            </span>
                        @enderror
                    </div>

                    <div class="col-span-3 space-y-1">
                        <flux:input type="number" label="End (s)" class="w-full" wire:model="segments.{{ $i }}.end"
                            placeholder="5" />
                        @error("segments.$i.end")
                            <span class="text-red-500 text-xs">
                                {{ $message }}
                            </span>
                        @enderror
                    </div>

                    <div class="col-span-1 flex justify-center pb-1">

                        <flux:button wire:click="removeSegment({{ $i }})" variant="ghost" size="sm" color="red">
                            <flux:icon.x-mark variant="mini" />
                        </flux:button>

                    </div>

                </div>

            @endforeach
        </div>
        <div class="flex flex-col gap-4 mt-8">
            <flux:button wire:click="addSegment" variant="outline" variant="filled" icon="plus">
                Tambah Segmen GT
            </flux:button>
            <flux:button wire:click="calculateIoU" variant="primary" color="blue" icon="calculator">
                Simpan & Hitung IoU
            </flux:button>
        </div>
    </flux:card>
</div>
</div>