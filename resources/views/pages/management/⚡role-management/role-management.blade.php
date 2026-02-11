<div class="w-full bg-white dark:bg-neutral-900 shadow-xl rounded-xl overflow-hidden 
     border-t-4 border-blue-500 mx-auto">

    <!-- HEADER -->
    <div class="border-b border-gray-200 dark:border-neutral-800 p-4 flex justify-between items-center bg-gray-50 dark:bg-neutral-800/60">
        <h3 class="text-md font-semibold text-gray-800 dark:text-gray-100">Managemen Role</h3>
    </div>
    <div class="p-6 space-y-2">
        <div class="flex justify-between items-center py-4">
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
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search..." class="pl-10 pr-4 py-2 rounded-lg text-sm border border-gray-300/80 dark:border-neutral-700 bg-gray-50 dark:bg-neutral-800 shadow-inner focus:ring-blue-500 focus:outline-none w-full transition-all">
                    <flux:icon icon="magnifying-glass" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
                </div>
                {{-- BUTTON --}}
                @can('create_role')
                <flux:button icon="plus" wire:click="createRole" class="!bg-blue-600 hover:!bg-blue-700 !text-white text-sm px-3 py-2 rounded-md shadow-sm transition-all duration-200">
                    Tambah Role
                </flux:button>
                @endcan

            </div>
        </div>

        {{-- TABLE --}}
        <flux:table container:class="max-h-[70vh]" :paginate="$this->roles" class="min-w-full">
            <flux:table.columns sticky class="font-medium bg-gray-100 dark:bg-neutral-800">
                <flux:table.column align="center">#</flux:table.column>
                <flux:table.column align="center">Role Pengguna</flux:table.column>
                <flux:table.column align="center">Aksi</flux:table.column>
            </flux:table.columns>

            {{-- BODY --}}
            <flux:table.rows>
                @forelse ($this->roles as $index => $role)
                <flux:table.row wire:key="role-{{ $role->id }}" class="hover:bg-gray-50 dark:hover:bg-neutral-800 transition odd:bg-white even:bg-gray-50 dark:odd:bg-gray-900/50 dark:even:bg-gray-950">
                    <flux:table.cell align="center">{{ $this->roles->firstItem() + $index }}</flux:table.cell>
                    <flux:table.cell align="center">{{ $role->name }}</flux:table.cell>
                    <flux:table.cell align="center">
                        <div class="flex justify-center items-center gap-2">
                            @can('update_role')
                            <flux:modal.trigger name="edit-role-{{ $role->id }}">
                                <flux:button icon="pencil-square" icon:variant="solid" tooltip="Edit Role" tooltip:position="top" wire:click="edit({{ $role->id }})" size="sm" variant="primary" color="blue" />
                            </flux:modal.trigger>
                            @endcan
                            @can('delete_role')
                            <flux:button icon="trash" icon:variant="solid" tooltip="Hapus Role" tooltip:position="top" wire:click="confirmDelete({{ $role->id }})" size="sm" variant="danger" />
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>

                @empty
                <flux:table.row>
                    <flux:table.cell align="center" colspan="3" class="py-8">
                        Tidak ada data tersedia
                    </flux:table.cell>
                </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    {{-- It is not the man who has too little, but the man who craves more, that is poor. - Seneca --}}
    <flux:modal wire:model="showRoleModal" flyout variant="floating" class="md:w-2xl">

        <form wire:submit.prevent="save" class="space-y-6">

            {{-- HEADER --}}
            <div class="space-y-1">
                <flux:heading size="lg">
                    {{ $roleId ? 'Edit Role' : 'Tambah Role' }}
                </flux:heading>

                <flux:subheading>
                    {{ $roleId 
                    ? 'Ubah nama role dan permission yang dimiliki.' 
                    : 'Buat role baru dan atur permission.' }}
                </flux:subheading>
            </div>

            {{-- INPUT --}}
            <flux:input label="Nama Role Akses" placeholder="Masukkan nama role" wire:model.defer="name" />

            @error('name')
            <p class="text-xs text-rose-500">{{ $message }}</p>
            @enderror

            {{-- PERMISSIONS --}}
            <div class="rounded-xl border border-gray-200 dark:border-zinc-700 p-4 space-y-3">
                <label class="text-sm font-medium">
                    Pilih Permissions
                </label>

                <ul class="space-y-2 text-sm">
                    @foreach ($menus as $menu)
                    <li class="p-2 bg-white dark:bg-neutral-900">
                        {{-- SATU GROUP UNTUK PARENT + CHILD --}}
                        <flux:checkbox.group wire:model="selectedPermissions">
                            <div class="flex items-center gap-3 mb-3">
                                <flux:checkbox.all />
                                <span class="font-semibold text-neutral-800 dark:text-neutral-100">
                                    {{ $menu['name'] }}
                                </span>
                            </div>
                            {{-- PARENT PERMISSIONS --}}
                            @if(!empty($menu['permissions']))
                            <div class="pl-8 text-neutral-700 dark:text-neutral-300">
                                @foreach($menu['permissions'] as $perm)
                                <flux:checkbox value="{{ $perm }}" label="{{ $perm }}" />
                                @endforeach
                            </div>
                            @endif

                            {{-- CHILDREN --}}
                            @if(!empty($menu['children']))
                            <div class="mt-2 pl-6 space-y-2">
                                @foreach($menu['children'] as $child)
                                <div class="space-y-2">
                                    <div class="flex items-center gap-3">
                                        <span class="font-medium">
                                            {{ $child['name'] }}
                                        </span>
                                    </div>

                                    {{-- CHILD PERMISSIONS --}}
                                    @if(!empty($child['permissions']))
                                    <div class="pl-8 space-y-2">
                                        @foreach($child['permissions'] as $perm)
                                        <flux:checkbox value="{{ $perm }}" label="{{ $perm }}" />
                                        @endforeach
                                    </div>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </flux:checkbox.group>
                    </li>
                    @endforeach
                </ul>
            </div>


            {{-- FOOTER --}}
            <div class="mt-8 pt-5 border-t border-gray-200 dark:border-zinc-700">
                <div class="flex justify-end gap-3">

                    {{-- BATAL --}}
                    <flux:modal.close>
                        <flux:button variant="filled" class="px-3 py-2 rounded-lg font-medium hover:bg-gray-100 dark:hover:bg-zinc-800 transition-all duration-200">
                            Batal
                        </flux:button>
                    </flux:modal.close>

                    {{-- SUBMIT --}}
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled" class="px-5 py-2 rounded-lg font-semibold
                   shadow-md hover:shadow-lg
                   transition-all duration-200">

                        <span wire:loading.remove class="flex items-center gap-2">
                            {{ $roleId ? 'Update Role' : 'Simpan Role' }}
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
