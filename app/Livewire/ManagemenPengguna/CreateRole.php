<?php

namespace App\Livewire\ManagemenPengguna;

use App\Models\Menu;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

#[Title('Create Role')]
class CreateRole extends Component
{
    public $name;
    public $selectedPermissions = [];
    public $menus = [];

    protected $rules = [
        'name' => 'required|string|max:50|unique:roles,name',
    ];

    public function mount($roleId = null)
    {
        $this->authorize('create_role');

        // Ambil semua menu beserta permission dan children
        $this->menus = Menu::with(['permissions', 'children.permissions', 'children.children.permissions'])
            ->whereNull('parent_id')
            ->orderBy('order')
            ->get()
            ->map(fn($menu) => $this->formatMenu($menu))
            ->toArray();

        // Jika edit role, isi permission sudah dipilih
        if ($roleId) {
            $role = Role::find($roleId);
            $this->selectedPermissions = $role ? $role->permissions->pluck('name')->toArray() : [];
        }
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

    public function save()
    {
        $this->validate();

        $role = Role::create([
            'name' => $this->name,
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($this->selectedPermissions);
        $this->dispatch('swal-toast', icon: 'success', title: 'Berhasil', text: 'Role berhasil dibuat!');
        return redirect()->route('manajemen.role');
    }

    // Toggle centang semua permission di menu
    public function toggleMenu($menuName)
    {
        $target = $this->findMenuByName($this->menus, $menuName);
        if (!$target) return;

        $perms = $this->getAllPerms($target);

        $allSelected = collect($perms)->every(fn($p) => in_array($p, $this->selectedPermissions));

        if ($allSelected) {
            $this->selectedPermissions = array_values(array_diff($this->selectedPermissions, $perms));
        } else {
            $this->selectedPermissions = array_unique(array_merge($this->selectedPermissions, $perms));
        }
    }

    private function findMenuByName($menus, $name)
    {
        foreach ($menus as $menu) {
            if ($menu['name'] === $name) return $menu;
            if (!empty($menu['children'])) {
                $found = $this->findMenuByName($menu['children'], $name);
                if ($found) return $found;
            }
        }
        return null;
    }

    private function getAllPerms($menu)
    {
        $perms = $menu['permissions'] ?? [];
        if (!empty($menu['children'])) {
            foreach ($menu['children'] as $child) {
                $perms = array_merge($perms, $this->getAllPerms($child));
            }
        }
        return $perms;
    }

    public function isMenuFullySelected($menu)
    {
        $perms = $this->getAllPerms($menu);
        if (empty($perms)) return false;
        return collect($perms)->every(fn($p) => in_array($p, $this->selectedPermissions));
    }

    public function isMenuPartiallySelected($menu)
    {
        $perms = $this->getAllPerms($menu);
        if (empty($perms)) return false;
        $count = collect($perms)->filter(fn($p) => in_array($p, $this->selectedPermissions))->count();
        return $count > 0 && $count < count($perms);
    }


    public function render()
    {
        return view('livewire.managemen-pengguna.create-role', [
            'menus' => $this->menus,
        ]);
    }
}
