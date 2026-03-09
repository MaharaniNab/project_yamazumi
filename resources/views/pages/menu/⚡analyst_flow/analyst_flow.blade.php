<div
    class="w-full border-t-4 border-slate-500 dark:border-slate-700 bg-white dark:bg-neutral-900 rounded-2xl shadow-sm p-8">
    <div class="mb-8">
        <flux:heading size="lg" class="font-semibold">
            Upload Video Process
        </flux:heading>

        <flux:text size="md" class="text-slate-500 mt-1">
            Lengkapi informasi line dan unggah video untuk memulai proses analisis.
        </flux:text>
    </div>
    {{-- STEPPER --}}
    <div class="flex items-center justify-between mb-10">

        {{-- STEP 1 --}}
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 flex items-center justify-center rounded-full
        {{ $step >= 1 ? 'bg-emerald-500 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-500' }}">
                1
            </div>

            <div>
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                    Metadata Lini
                </p>
            </div>
        </div>

        <div class="flex-1 h-[2px] mx-4
        {{ $step >= 2 ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-slate-700' }}">
        </div>

        {{-- STEP 2 --}}
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 flex items-center justify-center rounded-full
        {{ $step >= 2 ? 'bg-blue-500 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-500' }}">
                2
            </div>

            <div>
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                    Upload Video
                </p>
            </div>
        </div>

        <div class="flex-1 h-[2px] mx-4
        {{ $step >= 3 ? 'bg-indigo-500' : 'bg-slate-200 dark:bg-slate-700' }}">
        </div>

        {{-- STEP 3 --}}
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 flex items-center justify-center rounded-full
        {{ $step >= 3 ? 'bg-indigo-500 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-500' }}">
                3
            </div>

            <div>
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                    Proses Analisis
                </p>
            </div>
        </div>

    </div>
    <form wire:submit.prevent="save" class="space-y-6">
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <flux:input label="Nama Line" required wire:model.defer="nama_line" placeholder="Jas A"
                    class="font-medium" />
                @error('nama_line')
                    <flux:text size="md" class="text-rose-500 mt-1">
                        {{ $message }}
                    </flux:text>
                @enderror

                <flux:input label="Nama Bagian" required wire:model.defer="nama_bagian" placeholder="Front"
                    class="font-medium" />
                @error('nama_bagian')
                    <flux:text size="md" class="text-rose-500 mt-1">
                        {{ $message }}
                    </flux:text>
                @enderror

                <flux:input type="number" label="Output Harian (PCS)" required wire:model.live="output_harian"
                    placeholder="120" class="font-medium" />
                @error('output_harian')
                    <flux:text size="md" class="text-rose-500 mt-1">
                        {{ $message }}
                    </flux:text>
                @enderror

                <flux:input label="Style Product" required wire:model.defer="style_product" placeholder="Man Jas 2P"
                    class="font-medium" />
                @error('style_product')
                    <flux:text size="md" class="text-rose-500 mt-1">
                        {{ $message }}
                    </flux:text>
                @enderror

                <flux:input label="Brand" required wire:model.defer="brand" placeholder="Brand B" class="font-medium" />
                @error('brand')
                    <flux:text size="md" class="text-rose-500 mt-1">
                        {{ $message }}
                    </flux:text>
                @enderror

                <div class="p-4 bg-gray-100 dark:bg-neutral-800 rounded-lg">
                    <h2 class="text-sm font-medium">Takt Time</h2>

                    @if ($this->taktTime)
                        <p class="text-sm font-semibold text-red-600">
                            {{ number_format($this->taktTime, 1) }} detik / pcs
                        </p>
                    @else
                        <p class="text-xs font-medium text-gray-500">
                            Output belum tersedia
                        </p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Divider --}}
        <div class="border-t border-slate-200 dark:border-slate-700"></div>
        <div class="space-y-6">
            <div class="mb-8">
                <flux:heading size="lg" class="font-semibold">
                    Upload Video Rekaman
                </flux:heading>
                <div class="flex flex-wrap gap-1 mt-1 justify-start">
                    <flux:text size="md" class="text-slate-500">
                        Satu video per stasiun
                        <span class="mx-2">•</span>
                        <span class="mx-2">Format yang didukung :</span>
                    </flux:text>
                    <flux:badge color="blue" size="sm">.mp4</flux:badge>
                    <flux:badge color="blue" size="sm">.avi</flux:badge>
                    <flux:badge color="blue" size="sm">.mov</flux:badge>
                    <flux:badge color="blue" size="sm">.mkv</flux:badge>
                </div>
            </div>

            <flux:file-upload wire:model.live="file_list" multiple
                accept="video/mp4,video/quicktime,video/x-msvideo,video/x-matroska">
                <flux:file-upload.dropzone class="min-h-[220px] flex flex-col items-center justify-center
               rounded-2xl border-2 border-dashed
               border-slate-300 dark:border-slate-600
               hover:border-[#2A5298] hover:bg-[#2A5298]/10 hover:text-[#2A5298] transition" with-progress
                    heading="Klik atau drag & drop video di sini"
                    text="Nama file akan otomatis disimpan sebagai nama stasiun kerja" accept=".mp4,.avi,.mov,.mkv" />
            </flux:file-upload>

            @error('file_list')
                <flux:text size="sm" class="text-rose-500">
                    {{ $message }}
                </flux:text>
            @enderror

            @error('file_list.*')
                <flux:text size="sm" class="text-rose-500">
                    {{ $message }}
                </flux:text>
            @enderror

            <div wire:loading wire:target="file_list">
                <flux:text size="sm" class="text-indigo-500">
                    Mengunggah video...
                </flux:text>
            </div>

            @if ($file_list)
                <div class="mt-4 flex flex-col gap-3 w-full">
                    @foreach ($file_list as $index => $video)
                        @if (in_array(strtolower($video->getClientOriginalExtension()), ['mp4', 'mov', 'avi', 'mkv']))

                            <flux:file-item wire:key="video-{{ $index }}" :heading="$video->getClientOriginalName()"
                                :size="$video->getSize()">

                                <div class="flex items-start gap-4 mt-3">
                                    <video src="{{ $video->temporaryUrl() }}" class="w-40 rounded-lg" controls preload="metadata">
                                    </video>
                                </div>
                                <x-slot name="actions">
                                    <flux:file-item.remove wire:click="removeVideo({{ $index }})" aria-label="Remove file" />
                                </x-slot>
                            </flux:file-item>

                            <div class="flex flex-col gap-2">
                                <flux:input label="Nama Stasiun" wire:model.defer="station_names.{{ $index }}" class="w-52" />
                                <flux:text size="xs" class="text-slate-400">
                                    Nama ini akan digunakan sebagai nama file saat disimpan
                                </flux:text>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ACTION --}}
        <div class="pt-2 flex justify-end gap-2">
            <flux:button wire:click="resetForm" variant="filled" class="px-6 disabled:cursor-not-allowed disabled:opacity-50">
                Reset
            </flux:button>

            <flux:link as="button" variant="subtle" href="{{ route('menu.report') }}" icon="exclamation-circle" icon:variant="outline" type="submit" class="!bg-[#2A5298] !text-white px-4 py-2 rounded-lg text-sm disabled:!cursor-not-allowed disabled:!opacity-50"
                :disabled="$step < 3">
                Mulai Analisis
            </flux:link>
        </div>
    </form>
</div>