<div
    class="w-full bg-white dark:bg-neutral-900 shadow-xl rounded-xl overflow-hidden border-t-4 border-blue-500 mx-auto">
    <div
        class="border-b border-gray-200 dark:border-neutral-800 p-4 flex justify-between items-center bg-gray-50 dark:bg-neutral-800/60">
        <h3 class="text-md font-semibold text-gray-800 dark:text-gray-100">Managemen user</h3>
    </div>
    <div class="p-6 space-y-4">
        <div class="flex justify-between items-center">
            {{-- Show entries --}}
            <div class="flex items-center gap-2 text-sm">
                <span>Show</span>
                <select wire:model.live="perPage" class="h-9 border rounded px-2">
                    <option>10</option>
                    <option>25</option>
                    <option>50</option>
                    <option>100</option>
                </select>
                <span>entries</span>
            </div>

            {{-- Search + Add --}}
            <div class="flex items-center gap-4">
                <div class="relative w-full md:w-64">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search..."
                        class="pl-10 pr-4 py-2 rounded-lg text-sm border border-gray-300/80 dark:border-neutral-700 bg-gray-50 dark:bg-neutral-800 shadow-inner focus:ring-blue-500 focus:outline-none w-full transition-all">
                    <flux:icon icon="magnifying-glass"
                        class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
                </div>
                {{-- BUTTON --}}
                @can('create_user')
                    <flux:button icon="plus" wire:click="createUser"
                        class="!bg-blue-600 hover:!bg-blue-700 !text-white text-sm px-3 py-2 rounded-md shadow-sm transition-all duration-200">
                        Tambah user
                    </flux:button>
                @endcan

            </div>
        </div>

        {{-- TABLE --}}
        <flux:table container:class="max-h-[70vh]" :paginate="$this->users" class="min-w-full">
            <flux:table.columns sticky class="font-medium bg-gray-100 dark:bg-neutral-800">
                <flux:table.column align="center">#</flux:table.column>
                <flux:table.column align="center">Nama Pengguna</flux:table.column>
                <flux:table.column align="center">Email Pengguna</flux:table.column>
                <flux:table.column align="center">Status</flux:table.column>
                <flux:table.column align="center">Role Pengguna</flux:table.column>
                <flux:table.column align="center">Aksi</flux:table.column>
            </flux:table.columns>

            {{-- BODY --}}
            <flux:table.rows>
                @forelse ($this->users as $index => $user)
                            <flux:table.row wire:key="user-{{ $user->id }}"
                                class="hover:bg-gray-50 dark:hover:bg-neutral-800 transition odd:bg-white even:bg-gray-50 dark:odd:bg-gray-900/50 dark:even:bg-gray-950">
                                <flux:table.cell align="center">{{ $this->users->firstItem() + $index }}</flux:table.cell>
                                <flux:table.cell align="center">{{ $user->name }}</flux:table.cell>
                                <flux:table.cell align="center">{{ $user->email }}</flux:table.cell>
                                <flux:table.cell align="center">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-md
                                                        {{ $user->is_active
                    ? 'bg-green-100 text-green-700'
                    : 'bg-red-100 text-red-700' }}">
                                        {{ $user->is_active ? 'Aktif' : 'Tidak Aktif' }}
                                    </span>
                                </flux:table.cell>
                                @php
                                    $role = $user->roles->first();

                                    $colors = [
                                        1 => 'bg-red-100 text-red-700',      // Admin
                                        2 => 'bg-purple-100 text-purple-700',// Manager
                                        3 => 'bg-blue-100 text-blue-700',    // Supervisor
                                        4 => 'bg-green-100 text-green-700',  // Operator
                                        5 => 'bg-gray-100 text-gray-700',    // Guest
                                    ];

                                    $color = $colors[$role?->id] ?? 'bg-gray-100 text-gray-700';
                                @endphp

                                <flux:table.cell align="center">
                                    <span class="px-2.5 py-0.5 text-xs font-semibold rounded-md {{ $color }}">
                                        {{ $role?->name ?? '-' }}
                                    </span>
                                </flux:table.cell>
                                <flux:table.cell align="center">
                                    <div class="flex justify-center items-center gap-2">
                                        @can('update_user')
                                            <flux:modal.trigger name="edit-user-{{ $user->id }}">
                                                <flux:button icon="pencil-square" icon:variant="solid" tooltip="Edit user"
                                                    tooltip:position="top" wire:click="edit({{ $user->id }})" size="sm"
                                                    variant="primary" color="blue" />
                                            </flux:modal.trigger>
                                        @endcan
                                        @can('delete_user')
                                            <flux:button icon="trash" icon:variant="solid" tooltip="Hapus user" tooltip:position="top"
                                                wire:click="confirmDelete({{ $user->id }})" size="sm" variant="danger" />
                                        @endcan
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>

                @empty
                    <flux:table.row>
                        <flux:table.cell align="center" colspan="5" class="py-8">
                            Tidak ada data tersedia
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal wire:model="showUserModal" flyout variant="floating" class="md:w-2xl">
        <form wire:submit.prevent="save" class="space-y-6">

            {{-- HEADER --}}
            <div class="space-y-1">
                <flux:heading size="lg">
                    {{ $userId ? 'Edit User' : 'Tambah User' }}
                </flux:heading>

                <flux:subheading>
                    {{ $userId
    ? 'Ubah nama user dan role yang dimiliki.'
    : 'Buat user baru dan atur role yang dimiliki.' }}
                </flux:subheading>
            </div>

            {{-- NAMA --}}
            <flux:input label="Nama Pengguna" placeholder="Masukkan nama pengguna" wire:model.defer="name" />
            @error('name')
                <flux:text size="sm" class="text-rose-500 -mt-2">
                    {{ $message }}
                </flux:text>
            @enderror

            {{-- EMAIL --}}
            <flux:input type="email" label="Email Pengguna" placeholder="Masukkan email pengguna"
                wire:model.defer="email" />
            @error('email')
                <flux:text size="sm" class="text-rose-500 -mt-2">
                    {{ $message }}
                </flux:text>
            @enderror

            {{-- TANGGAL LAHIR --}}
            <flux:date-picker selectable-header label="Tanggal Lahir" placeholder="Pilih tanggal lahir"
                wire:model.defer="birth_date" />
            @error('birth_date')
                <flux:text size="sm" class="text-rose-500 -mt-2">
                    {{ $message }}
                </flux:text>
            @enderror

            {{-- PASSWORD --}}
            <flux:input type="password" label="Password"
                placeholder="{{ $userId ? 'Kosongkan jika tidak diubah' : 'Masukkan password' }}"
                wire:model.defer="password" :description="$userId ? 'Kosongkan jika tidak diubah.' : null" />
            @error('password')
                <flux:text size="sm" class="text-rose-500 -mt-2">
                    {{ $message }}
                </flux:text>
            @enderror

            {{-- PASSWORD CONFIRMATION --}}
            <flux:input type="password" label="Konfirmasi Password" placeholder="Ulangi password"
                wire:model.defer="password_confirmation" />
            @error('password_confirmation')
                <flux:text size="sm" class="text-rose-500 -mt-2">
                    {{ $message }}
                </flux:text>
            @enderror

            {{-- STATUS --}}
            <div class="space-y-1">
                <flux:heading size="sm">
                    Status
                </flux:heading>
                <flux:switch required wire:model.defer="is_active" align="left" />
                @error('is_active')
                    <flux:text size="sm" class="text-rose-500 -mt-2">
                        {{ $message }}
                    </flux:text>
                @enderror
            </div>

            {{-- ROLE --}}
            <flux:select label="Role" placeholder="-- Pilih Role --" wire:model.defer="role_id">
                <flux:select.option value="">
                    - Pilih Role -
                </flux:select.option>
                @foreach($this->roles as $role)
                    <flux:select.option value="{{ $role->id }}">
                        {{ $role->name }}
                    </flux:select.option>
                @endforeach
            </flux:select>
            @error('role_id')
                <flux:text size="sm" class="text-rose-500 -mt-2">
                    {{ $message }}
                </flux:text>
            @enderror

            {{-- FOOTER --}}
            <div class="mt-8 pt-5 border-t border-gray-200 dark:border-zinc-700">
                <div class="flex justify-end gap-3">
                    <flux:modal.close>
                        <flux:button variant="filled"
                            class="px-3 py-2 rounded-lg font-medium hover:bg-gray-100 dark:hover:bg-zinc-800 transition-all duration-200">
                            Batal
                        </flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled" class="px-5 py-2 rounded-lg font-semibold
                   shadow-md hover:shadow-lg
                   transition-all duration-200">

                        <span wire:loading.remove class="flex items-center gap-2">
                            {{ $userId ? 'Update User' : 'Simpan User' }}
                        </span>

                        <span wire:loading class="flex items-center gap-2">
                            <flux:icon icon="arrow-path" class="w-4 h-4 animate-spin" />
                            Menyimpan...
                        </span>
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>