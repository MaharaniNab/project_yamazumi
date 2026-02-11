<?php

namespace App\Livewire\ManagemenPengguna;

use App\Models\User;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

#[Title('User Management')]
class UserManagement extends Component
{
    use WithPagination;

    public $perPage = 10;
    public $search = '';
    public $deleteId;

    protected $listeners = ['deleteConfirmed' => 'delete'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    // public function confirmDelete($id)
    // {
    //     $this->authorize('delete_user');
    //     $this->deleteId = $id;
    //     $this->dispatch('confirm-delete', [
    //         'message' => 'Apakah kamu yakin ingin menghapus pengguna ini?'
    //     ]);
    // }

    // public function delete()
    // {
    //     $this->authorize('delete_user');
    //     User::find($this->deleteId)?->delete();
    //     session()->flash('message', 'Pengguna berhasil dihapus.');
    //     return redirect()->route('manajemen.user');
    // }

    public function render()
    {
        $this->authorize('read_user');
        $users = User::with('roles')
            ->when(
                $this->search,
                fn($query) =>
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
            )
            ->paginate($this->perPage);
        return view('livewire.managemen-pengguna.user-management', [
            'users' => $users,
            'roles' => Role::all(),
        ]);
    }
}
