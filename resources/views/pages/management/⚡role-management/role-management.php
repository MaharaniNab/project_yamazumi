<?php

use App\Models\Menu;
use App\Models\Role;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new 
#[Title('Role Management')]
class extends Component
{
    use WithPagination;
    protected $paginationTheme = 'tailwind';

    public $perPage = 10;
    public $search = '';
    public $roleToDelete = null;
    public $roleId;
    public $name;
    public $selectedPermissions = [];
    public $menus = [];
    public $showRoleModal = false;

    protected $rules = [
        'name' => 'required|string|max:50',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('delete_role');

        $this->roleToDelete = $id;

        $role = Role::find($id);

        $message = $role
            ? "Apakah Anda yakin ingin menghapus role \"{$role->name}\"?"
            : "Data role tidak ditemukan.";

        $this->dispatch('confirm-delete', message: $message, eventName: 'deleteRole');
    }

    #[On('deleteRole')]
    public function deleteRole(): void
    {
        $this->authorize('delete_role');

        if (!$this->roleToDelete) {
            return;
        }

        $role = Role::find($this->roleToDelete);

        if (!$role) {
            $this->dispatch('swal-toast', icon: 'error', title: 'Gagal', text: "Data role tidak ditemukan!");
            $this->roleToDelete = null;
            return;
        }

        $role->delete();
        $this->dispatch('swal-toast', icon: 'success', title: 'Berhasil', text: 'Role berhasil dihapus!');

        $this->roleToDelete = null;
        $this->resetPage();
    }

    public function edit(int $id): void
    {
        $this->authorize('update_role');

        $role = Role::with('permissions')->findOrFail($id);

        $this->roleId = $role->id;
        $this->name = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();

        $this->loadMenus();

        $this->showRoleModal = true;
    }

    public function createRole()
    {
        $this->authorize('create_role');
        $this->reset(['roleId', 'name', 'selectedPermissions']);
        $this->loadMenus();
        $this->showRoleModal = true;
    }

    private function loadMenus(): void
    {
        $this->menus = Menu::with([
            'permissions',
            'children.permissions',
            'children.children.permissions'
        ])
            ->whereNull('parent_id')
            ->orderBy('order')
            ->get()
            ->map(fn($menu) => $this->formatMenu($menu))
            ->toArray();
        // dd($this->menus);
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:50|unique:roles,name,' . $this->roleId,
        ]);

        $role = Role::updateOrCreate(
            ['id' => $this->roleId],
            [
                'name' => $this->name,
                'guard_name' => 'web',
            ]
        );

        $role->syncPermissions($this->selectedPermissions);
        $this->dispatch('swal-toast', icon: 'success', title: 'Berhasil', text: $this->roleId
            ? 'Role berhasil diperbarui!'
            : 'Role berhasil dibuat!');
        $this->showRoleModal = false;
        $this->reset(['roleId', 'name', 'selectedPermissions']);
        $this->resetPage();
    }


    #[Computed]
    public function roles()
    {
        return Role::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('id', 'asc')
            ->paginate($this->perPage);
    }

    private function formatMenu($menu)
    {
        return [
            'id' => $menu->id,
            'name' => $menu->name,
            'permissions' => $menu->permissions->pluck('name')->toArray(),
            'children' => $menu->children
                ->sortBy('order')
                ->map(fn($child) => $this->formatMenu($child))
                ->values()
                ->toArray(),
        ];
    }
};
