<div class="w-full bg-white dark:bg-neutral-900 shadow-xl rounded-xl overflow-hidden 
     border-t-4 border-blue-500 mx-auto">

    <!-- HEADER -->
    <div class="border-b border-gray-200 dark:border-neutral-800 p-4 flex justify-between items-center bg-gray-50 dark:bg-neutral-800/60">
        <h3 class="text-md font-semibold text-gray-800 dark:text-gray-100">Managemen Role</h3>
    </div>

    <!-- Body -->
    <div class="px-6 space-y-2">
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
                </div> @can('create_role')
                <a href="{{ route('manajemen.role.create') }}" class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-4 py-2 text-sm rounded-lg shadow-md flex items-center gap-2 transition-all">
                    <flux:icon icon="plus" class="size-4 mr-2 text-white" /> Tambah Role
                </a>
                @endcan
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto max-h-[70vh] overflow-y-auto rounded-lg border border-gray-200 dark:border-neutral-700">
            <table class="min-w-full text-sm text-left">
                <thead class="bg-gray-100 dark:bg-neutral-800 sticky top-0 z-10 text-center">
                    <tr>
                        <th class="border border-gray-200 dark:border-neutral-700 px-3 py-1 text-center">No</th>
                        <th class="border border-gray-200 dark:border-neutral-700 px-3 py-1 text-center">Role Pengguna</th>
                        <th class="border border-gray-200 dark:border-neutral-700 px-3 py-1 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($roles as $index => $role)
                    <tr class="text-center">
                        <td class="border border-gray-200 dark:border-neutral-700 px-3 py-1">{{ $roles->firstItem() + $index }}</td>
                        <td class="border border-gray-200 dark:border-neutral-700 px-3 py-1">{{ $role->name }}</td>

                        <td class="border border-gray-200 dark:border-neutral-700 px-3 py-1">
                            <div class="flex justify-center gap-2">
                                @can('update_role')
                                <flux:tooltip content="Edit" position="top">
                                    <flux:button onclick="window.location='{{ route('manajemen.role.edit', $role->id) }}'" icon="pencil-square" icon:variant="solid" class="w-5 h-5 flex items-center justify-center rounded-lg border
                       border-blue-500! text-blue-500! hover:bg-blue-500! hover:text-white!
                       shadow-sm transition duration-200" />
                                </flux:tooltip>
                                @endcan

                                <flux:tooltip content="Edit" position="top">
                                    <flux:button x-on:click="$dispatch('swal-toast')" icon="pencil-square" icon:variant="solid" class="w-5 h-5 flex items-center justify-center rounded-lg border
                       border-blue-500! text-blue-500! hover:bg-blue-500! hover:text-white!
                       shadow-sm transition duration-200" />
                                </flux:tooltip>

                                {{-- @can('delete_role')
                                <flux:tooltip content="Delete" position="top">
                                    <flux:button wire:click="confirmDelete({{ $role->id }})" icon="archive-box-x-mark" icon:variant="solid" class="w-5 h-5 flex items-center justify-center rounded-lg border
                                border-rose-500! text-rose-500! hover:bg-rose-500! hover:text-white!
                                shadow-sm transition duration-200" />
                                </flux:tooltip>
                                @endcan --}}
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center text-gray-500 py-4">Belum ada role</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Info + Pagination (DataTables style) --}}
        <div class="py-6">
            {{ $roles->links() }}
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    window.addEventListener('confirm-delete', event => {
        Swal.fire({
            title: 'Konfirmasi Hapus'
            , text: event.detail.message
            , icon: 'warning'
            , showCancelButton: true
            , confirmButtonColor: '#d33'
            , cancelButtonColor: '#3085d6'
            , confirmButtonText: 'Ya, hapus!'
            , cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                Livewire.dispatch('deleteConfirmed');
            }
        });
    });

</script>
