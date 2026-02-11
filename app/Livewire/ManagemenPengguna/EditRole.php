<?php

namespace App\Livewire\ManagemenPengguna;

use App\Models\Menu;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;

#[Title('Edit Role')]
class EditRole extends Component
{
    public $roleId;
    public $name;
    public $selectedPermissions = [];
    public $menus = [];

    protected $rules = [
        'name' => 'required|string|max:50',
    ];

    public function mount($roleId)
    {
        $this->authorize('update_role');

        $role = Role::with('permissions')->findOrFail($roleId);
        $this->roleId = $role->id;
        $this->name = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();

        $this->menus = Menu::with(['permissions', 'children.permissions', 'children.children.permissions'])
            ->whereNull('parent_id')
            ->orderBy('order')
            ->get()
            ->map(fn($menu) => $this->formatMenu($menu))
            ->toArray();
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

    public function update()
    {
        $this->validate();

        $role = Role::findOrFail($this->roleId);
        $role->update([
            'name' => $this->name,
            'guard_name' => 'web',
        ]);
        $role->syncPermissions($this->selectedPermissions);

        $this->dispatch('swal-toast', icon: 'success', title: 'Berhasil', text: 'Role berhasil diperbarui!');
        return redirect()->route('manajemen.role');
    }

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
        // Pastikan $menu['permissions'] adalah array
        $perms = isset($menu['permissions']) && is_array($menu['permissions']) ? $menu['permissions'] : [];

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
        return view('livewire.managemen-pengguna.edit-role', [
            'menus' => $this->menus,
        ]);
    }
}
