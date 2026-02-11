<?php

namespace App\Livewire\ManagemenPengguna;

use App\Models\User;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Title;

#[Title('Edit User')]
class EditUser extends Component
{
    public $userId;
    public $name;
    public $identifier; // NIK / Username / Email
    public $password;
    public $role_id;
    public $email;

    public function mount($userId)
    {
        $this->authorize('update_user');

        $user = User::findOrFail($userId);
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->identifier = $user->nik ?? $user->username ?? $user->email;
        $this->role_id = $user->roles->first()?->id;
    }

    protected function rules()
    {
        return [
            'name' => 'required|string|max:100',
            'identifier' => 'required|string|max:100',
            'password' => 'nullable|min:6',
            'role_id' => 'required|exists:roles,id',
        ];
    }

    public function update()
    {
        $this->validate();

        $user = User::findOrFail($this->userId);

        // Cari field identifier yang cocok
        if (filter_var($this->identifier, FILTER_VALIDATE_EMAIL)) {
            $user->email = $this->identifier;
        } elseif (is_numeric($this->identifier)) {
            $user->nik = $this->identifier;
        } else {
            $user->username = $this->identifier;
        }

        $user->name = $this->name;
        $user->password = $this->password ? Hash::make($this->password) : $user->password;
        $user->save();

        // Update Role
        if ($this->role_id) {
            $role = Role::findOrFail($this->role_id);
            $user->syncRoles([$role->name]);
        }

        $this->dispatch('swal-toast', icon: 'success', title: 'Berhasil', text: 'Data pengguna berhasil diperbarui!');
        return redirect()->route('manajemen.user');
    }

    public function render()
    {
        return view('livewire.managemen-pengguna.edit-user', [
            'roles' => Role::all(),
        ]);
    }
}
