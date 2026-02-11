<?php

namespace App\Livewire\ManagemenPengguna;

use Livewire\Component;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Title;

#[Title('Create User')]
class CreateUser extends Component
{

    public $name;
    public $email;
    public $password;
    public $role_id;

    protected $rules = [
        'name' => 'required|string|max:100',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|min:6',
        'role_id' => 'required|exists:roles,id',
    ];

    public function mount()
    {
        $this->authorize('create_user');
        $this->reset(['name', 'email', 'password', 'role_id']);
    }

    public function save()
    {
        $this->validate();

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'email_verified_at' => $this-> now() ?? null,
        ]);

        if ($this->role_id) {
            $role = Role::findOrFail($this->role_id);
            $user->assignRole($role->name);
        }

        $this->dispatch('swal-toast', icon: 'success', title: 'Berhasil', text: 'Pengguna berhasil ditambahkan!');
        return redirect()->route('manajemen.user');
    }

    public function render()
    {
        return view('livewire.managemen-pengguna.create-user', [
            'roles' => Role::all(),
        ]);
    }
}
