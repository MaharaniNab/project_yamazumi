{{-- <div class="bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 rounded-xl shadow-md">
    <div class="flex items-center justify-between border-b border-gray-100 dark:border-zinc-700 px-4 py-2">
        <h3>Tambah Role</h3>
        <a href="{{ route('manajemen.role') }}" wire:navigate class="text-blue-600 hover:text-blue-800 transition">
             <flux:icon icon="arrow-left-circle" class="size-8" />
        </a>
    </div>

    <form wire:submit.prevent="save" class="p-6 space-y-6">
        <div>
            <label class="block mb-2 text-sm">Nama Role Akses</label>
            <input type="text" wire:model="name" class="w-full border rounded px-3 py-2 text-sm" placeholder="Masukkan nama role">
            @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="border p-3 rounded-md bg-white dark:bg-zinc-800">
            <label class="block mb-2 text-sm text-gray-800 dark:text-gray-200">
                Pilih Permissions
            </label>
            <ul class="space-y-3 block text-sm">
                @foreach ($menus as $menu)
                    <li>
                        <div class="flex items-center gap-2 text-gray-800 dark:text-gray-100 mb-1">
                            <input type="checkbox" 
                                   wire:click="toggleMenu('{{ $menu['name'] }}')"
                                   @checked($this->isMenuFullySelected($menu))
                                   x-data
                                   x-init="$watch('$wire.selectedPermissions', () => { $el.indeterminate = @js($this->isMenuPartiallySelected($menu)); })"
                                   class="rounded text-blue-600 focus:ring-blue-500 dark:bg-zinc-800 dark:border-zinc-600">
                            <span>{{ $menu['name'] }}</span>
                        </div>

                        @if(isset($menu['permissions']) && !empty($menu['permissions']))
                            <div class="pl-4">
                                @foreach($menu['permissions'] as $perm)
                                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                        <input type="checkbox" wire:model="selectedPermissions" value="{{ $perm }}" class="rounded text-blue-600 focus:ring-blue-500 dark:bg-zinc-800 dark:border-zinc-600">
                                        {{ $perm }}
                                    </label>
                                @endforeach
                            </div>
                        @endif

                        @if(isset($menu['children']) && !empty($menu['children']))
                            <div class="pl-4 mt-2 border-l-2 border-gray-200 dark:border-zinc-600">
                                @include('livewire.managemen-pengguna.partials.permission-tree', [
                                    'menus' => $menu['children'],
                                    'selectedPermissions' => $selectedPermissions
                                ])
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="flex justify-end gap-2 pt-4 border-t">
            <button type="submit" class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm">Simpan</button>
        </div>
    </form>
</div> --}}
<div class="bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 rounded-2xl shadow-sm">
    <!-- Header -->
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-zinc-700">
        <h3 class="text-base font-semibold">
            Tambah Role
        </h3>

        <a href="{{ route('manajemen.role') }}"
           wire:navigate
           class="text-gray-500 hover:text-blue-600 transition">
            <flux:icon icon="arrow-left-circle" class="w-7 h-7" />
        </a>
    </div>

    <!-- Form -->
    <form wire:submit.prevent="save" class="p-6 space-y-6">
        <!-- Nama Role -->
        <div>
            <label class="block mb-1 text-sm font-medium">
                Nama Role Akses
            </label>
            <input
                type="text"
                wire:model="name"
                placeholder="Masukkan nama role"
                class="w-full rounded-lg border border-gray-300 dark:border-zinc-600
                       bg-white dark:bg-zinc-900
                       px-3 py-2 text-sm
                       focus:ring-2 focus:ring-blue-500 focus:outline-none transition"
            >
            @error('name')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <!-- Permissions -->
        <div class="rounded-xl border border-gray-200 dark:border-zinc-600 bg-gray-50 dark:bg-zinc-900 p-4">
            <label class="block mb-3 text-sm font-medium">
                Pilih Permissions
            </label>

            <ul class="space-y-4 text-sm">
                @foreach ($menus as $menu)
                    <li>
                        <!-- Menu Parent -->
                        <div class="flex items-center gap-2 mb-2">
                            <input
                                type="checkbox"
                                wire:click="toggleMenu('{{ $menu['name'] }}')"
                                @checked($this->isMenuFullySelected($menu))
                                x-data
                                x-init="$watch('$wire.selectedPermissions', () => {
                                    $el.indeterminate = @js($this->isMenuPartiallySelected($menu))
                                })"
                                class="rounded border-gray-300 dark:border-zinc-600
                                       text-blue-600 focus:ring-blue-500"
                            >
                            <span class="font-medium">
                                {{ $menu['name'] }}
                            </span>
                        </div>

                        <!-- Permissions -->
                        @if(!empty($menu['permissions']))
                            <div class="pl-6 space-y-1">
                                @foreach($menu['permissions'] as $perm)
                                    <label class="flex items-center gap-2">
                                        <input
                                            type="checkbox"
                                            wire:model="selectedPermissions"
                                            value="{{ $perm }}"
                                            class="rounded border-gray-300 dark:border-zinc-600
                                                   text-blue-600 focus:ring-blue-500"
                                        >
                                        <span>{{ $perm }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @endif

                        <!-- Child Menu -->
                        @if(!empty($menu['children']))
                            <div class="pl-6 mt-3 border-l border-gray-200 dark:border-zinc-600">
                                @include(
                                    'livewire.managemen-pengguna.partials.permission-tree',
                                    [
                                        'menus' => $menu['children'],
                                        'selectedPermissions' => $selectedPermissions
                                    ]
                                )
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-2 pt-4 border-t border-gray-100 dark:border-zinc-700">
            <button
                type="submit"
                class="px-4 py-2 rounded-lg text-sm font-medium
                       bg-blue-600 text-white
                       hover:bg-blue-700 transition"
            >
                Simpan
            </button>
        </div>
    </form>
</div>
