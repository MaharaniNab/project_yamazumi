<div class="flex flex-col gap-6">
    <flux:heading size="lg" class="font-semibold">
        Riwayat Analisis
        <flux:subheading class="flex text-sm text-neutral-500 space-x-2">
            <span>Seluruh sesi analisis tersimpan</span>
            <span>·</span>
            <span>klik baris untuk detail</span>
        </flux:subheading>
    </flux:heading>

    <div class="flex flex-wrap items-center gap-3 mb-3">
        <div class="w-full md:w-64 flex-shrink-0">
            <flux:input icon="magnifying-glass" type="text" wire:model.live.debounce.300ms="search"
                placeholder="Search..." class:input="!bg-gray-50 dark:!bg-neutral-700 !shadow-inner !text-gray-700 dark:!text-gray-300" />
        </div>

        <div x-data="dateRangePicker()" class="relative w-full md:w-75 flex-shrink-0" @click.outside="open = false">
            <button type="button" @click="open = !open"
                class="w-full flex items-center justify-between border border-zinc-200 dark:border-zinc-600 bg-gray-50 dark:bg-neutral-700
                   transition-colors text-sm text-gray-700 dark:text-gray-300 rounded-lg p-2.5 shadow-inner focus:outline-none focus:ring-2">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-zinc-400 dark:text-zinc-300" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10m-12 8h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <span x-text="displayValue || 'Pilih rentang tanggal'"></span>
                </div>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 opacity-70" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="open" x-transition.scale.origin.top class="absolute z-20 bg-white dark:bg-neutral-700 p-4 flex flex-col items-center rounded-xl mt-2 w-75 shadow-lg">
                <!-- Judul -->
                <h4 class="w-full text-xs font-medium text-gray-700 dark:text-gray-200 mb-2 text-left">
                    Pilih Rentang Tanggal
                </h4>

                <!-- Wrapper isi (INI KUNCINYA) -->
                <div class="w-full flex justify-center">
                    <div class="inline-flex items-center gap-3 px-6">
                        <div class="flex flex-col w-32">
                            <label class="text-xs text-gray-500 dark:text-gray-300 mb-1">Dari</label>
                            <input type="date" x-model="start" @change="updateRange"
                                class="w-full border border-gray-300 dark:border-neutral-700 dark:bg-neutral-800 rounded px-2 py-1 text-sm text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>

                        <div class="flex flex-col w-32">
                            <label class="text-xs text-gray-500 dark:text-gray-300 mb-1">Sampai</label>
                            <input type="date" x-model="end" @change="updateRange"
                                class="w-full border border-gray-300 dark:border-neutral-700 dark:bg-neutral-800 rounded px-2 py-1 text-sm text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>
                    </div>
                </div>

                <!-- Button -->
                <div class="w-full mt-3 flex justify-end">
                    <button @click="clearRange"
                        class="text-xs text-gray-500 dark:text-gray-300 hover:text-red-500 transition">
                        Hapus pilihan
                    </button>
                </div>
            </div>
        </div>

        <!-- LINE FILTER -->
        <div class="w-full md:w-52 flex-shrink-0">
            <flux:select wire:model.live="selectedLine" class="!bg-gray-50 dark:!bg-neutral-700 !shadow-inner !text-gray-700 dark:!text-gray-300">
                <flux:select.option value="">Semua Lini</flux:select.option>
                @foreach($lines as $line)
                    <flux:select.option value="{{ $line }}">
                        {{ $line }}
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>

    </div>

    <flux:table :paginate="$this->jobs">
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
            @forelse($this->jobs as $index => $job)
                <flux:table.row  wire:click="openDetail({{ $job->id }})"
                    class="hover:bg-gray-50 dark:hover:bg-neutral-800 transition odd:bg-white even:bg-gray-50 dark:odd:bg-gray-900/50 dark:even:bg-gray-950">
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

                    <flux:table.cell align="center">
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
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="8" class="text-center py-8 text-gray-500">
                        Tidak ada data yang tersedia.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <script>
        function dateRangePicker() {
            return {
                open: false
                , start: ''
                , end: ''
                , displayValue: ''
                , updateRange() {
                    if (this.start && this.end) {
                        this.displayValue = `${this.start} → ${this.end}`;
                        this.$wire.set('startDate', this.start);
                        this.$wire.set('endDate', this.end);
                    }
                }
                , clearRange() {
                    this.start = '';
                    this.end = '';
                    this.displayValue = '';
                    this.$wire.set('startDate', null);
                    this.$wire.set('endDate', null);
                }
                ,
            };
        }

    </script>
</div>