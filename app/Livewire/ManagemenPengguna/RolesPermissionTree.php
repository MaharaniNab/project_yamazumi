<?php

namespace App\Livewire\ManagemenPengguna;

use Livewire\Component;
use App\Models\Menu;

class RolesPermissionTree extends Component
{
    public $menus = [];
    public $selectedPermissions = [];

    public function mount($selectedPermissions = [])
    {
        $this->menus = $this->getMenusFromDatabase();
        $this->selectedPermissions = $selectedPermissions;
    }

    public function updatedSelectedPermissions()
    {
        $this->emitUp('updateSelectedPermissions', $this->selectedPermissions);
    }

    private function getMenusFromDatabase()
    {
        return Menu::with(['permissions', 'children.permissions', 'children.children.permissions'])
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

    public function render()
    {
        return view('livewire.managemen-pengguna.roles-permission-tree', [
            'menus' => $this->menus,
            'selectedPermissions' => $this->selectedPermissions,
        ]);
    }
}
