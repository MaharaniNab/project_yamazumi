<div class="flex flex-col gap-6">
    <flux:heading size="lg" class="font-semibold">
        Riwayat Analisis
        <flux:subheading class="flex text-sm text-neutral-500 space-x-2">
            <span>Seluruh sesi analisis tersimpan</span>
            <span>·</span>
            <span>klik baris untuk detail</span>
        </flux:subheading>
    </flux:heading>

    <div class="flex items-center space-x-4 mb-3">
        <div class="relative w-full md:w-64">
            <flux:input icon="magnifying-glass" type="text" wire:model.live.debounce.300ms="searchHeader"
                placeholder="Search..." class:input="bg-gray-50 dark:bg-neutral-800 shadow-inner" />
        </div>
        <flux:date-picker mode="range" with-presets wire:model.live="range" class="w-64" />
        <flux:select wire:model.live="selectedLine" class="w-64">
            <flux:select.option value="">Semua Lini</flux:select.option>
            @foreach($lines as $line)
                <flux:select.option value="{{ $line }}">
                    {{ $line }}
                </flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:table>
        <flux:table.columns sticky class="font-medium bg-gray-100 dark:bg-neutral-700">
            <flux:table.column align="center" class="!px-4">#</flux:table.column>
            <flux:table.column>Job / Lini</flux:table.column>
            <flux:table.column align="center">Tanggal</flux:table.column>
            <flux:table.column align="center">PIC</flux:table.column>
            <flux:table.column align="center">Takt Time</flux:table.column>
            <flux:table.column align="center">Line Efficiency</flux:table.column>
            <flux:table.column align="center">Output / Hari</flux:table.column>
            <flux:table.column align="center" class="!px-4">Status</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($this->jobs as $index => $job)
                <flux:table.row class="hover:bg-gray-50 dark:hover:bg-neutral-800 transition odd:bg-white even:bg-gray-50 dark:odd:bg-gray-900/50 dark:even:bg-gray-950">
                    <flux:table.cell align="center" class="!px-4">
                        {{ $loop->iteration }}
                    </flux:table.cell>
                    <flux:table.cell class="!font-medium">
                        <div class="flex flex-col">
                            <span class="font-semibold">
                                {{ $job->line_name }}
                            </span>
                            <span class="text-xs text-gray-400">
                                #{{ substr(md5($job->id), 0, 8) }}
                            </span>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell align="center">{{ $job->created_at->format('d-m-Y') }}</flux:table.cell>
                    <flux:table.cell align="center">{{ $job->user->name }}</flux:table.cell>
                    <flux:table.cell align="center">
                        {{ number_format($job->takt_time, 1) }}
                    </flux:table.cell>

                    <flux:table.cell align="center" >
                        <div class="flex flex-col items-center gap-1">
                            <span class="text-sm font-semibold">
                                {{ number_format($job->line_efficiency, 1) }}%
                            </span>
                            @php
                                $color =
                                    $job->line_efficiency < 70 ? 'bg-red-500' :
                                    ($job->line_efficiency < 85 ? 'bg-yellow-500' : 'bg-green-500');
                            @endphp
                            <div class="w-full h-2 bg-gray-200 dark:bg-neutral-700 rounded-full overflow-hidden">
                                <div class="h-full {{ $color }} rounded-full transition-all"
                                    style="width: {{ $job->line_efficiency }}%">
                                </div>
                            </div>
                        </div>
                    </flux:table.cell>

                    <flux:table.cell align="center">
                        {{ $job->output_harian }} pcs
                    </flux:table.cell>

                    <flux:table.cell align="center">
                        @if($job->bottleneck_count > 0)
                            <flux:badge size="sm" color="red">
                                {{ $job->bottleneck_count }} Bottleneck
                            </flux:badge>
                        @else
                            <flux:badge size="sm" color="green">
                                Balanced
                            </flux:badge>
                        @endif
                    </flux:table.cell>
                </flux:table.row>

            @endforeach

        </flux:table.rows>
    </flux:table>
</div>