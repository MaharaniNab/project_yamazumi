<?php

namespace App\Livewire\ManagemenPengguna;

use App\Models\Role;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Role Management')]
class RoleManagement extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $search = '';
    public $roleToDelete = null;

    protected $listeners = ['deleteConfirmed' => 'deleteRole'];
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $this->authorize('read_role');

        $roles = Role::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('id', 'asc')
            ->paginate($this->perPage);

        return view('livewire.managemen-pengguna.role-management', compact('roles'));
    }
}
