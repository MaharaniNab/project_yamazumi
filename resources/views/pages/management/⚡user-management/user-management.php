<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new
    #[Title('User Management')]
    class extends Component {
    use WithPagination;
    protected $paginationTheme = 'tailwind';

    public $perPage = 10;
    public $search = '';
    public $userToDelete = null;
    public $userId;
    public $name;
    public $identifier;
    public $password;
    public $email;
    public $role_id;
    public $is_active = true;
    public $birth_date;
    public $showUserModal = false;
    public $password_confirmation;


    public function updatingSearch()
    {
        $this->resetPage();
    }

    #[Computed]
    public function users()
    {
        $this->authorize('read_user');
        return User::with('roles')
            ->when(
                $this->search,
                fn($query) =>
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
            )
            ->paginate($this->perPage);
    }

    public function edit(int $id): void
    {
        $this->authorize('update_user');
        $user = User::findOrFail($id);
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->birth_date = $user->birth_date;
        $this->is_active = $user->is_active;
        $this->identifier = $user->nik ?? $user->username ?? $user->email;
        $this->role_id = $user->roles->first()?->id;
        $this->showUserModal = true;
    }

    public function createUser()
    {
        $this->authorize('create_user');
        $this->resetForm();
        $this->showUserModal = true;
    }

    public function resetForm()
    {
        $this->reset([
            'userId',
            'name',
            'email',
            'password',
            'password_confirmation',
            'role_id',
            'birth_date'
        ]);

        $this->is_active = true;
    }

    public function save()
    {
        if ($this->userId) {
            $this->authorize('update_user');
        } else {
            $this->authorize('create_user');
        }

        $this->validate([
            'name' => 'required|string|max:50',
            'email' => 'required|email|unique:users,email,' . $this->userId,
            'birth_date' => 'required|date',
            'is_active' => 'required|boolean',
            'password' => $this->userId
                ? 'nullable|string|min:8|confirmed'
                : 'required|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
        ]);

        if ($this->userId) {
            // UPDATE
            $user = User::findOrFail($this->userId);

            $data = [
                'name' => $this->name,
                'email' => $this->email,
                'birth_date' => $this->birth_date,
                'is_active' => $this->is_active,
                'email_verified_at' => now(),
            ];

            // Hanya update password jika diisi
            if ($this->password) {
                $data['password'] = Hash::make($this->password);
            }

            $user->update($data);
            $message = 'User berhasil diperbarui!';
        } else {
            // CREATE
            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'birth_date' => $this->birth_date,
                'is_active' => (bool) $this->is_active,
                'password' => Hash::make($this->password),
                'email_verified_at' => now(),
            ]);

            $message = 'User berhasil dibuat!';
        }

        // Sync Role
        $role = Role::findOrFail($this->role_id);
        $user->syncRoles([$role->name]);

        $this->dispatch(
            'swal-toast',
            icon: 'success',
            title: 'Berhasil',
            text: $message
        );

        $this->showUserModal = false;
        $this->resetForm();
        $this->resetPage();
    }

    public function toggleUserStatus($id)
    {
        $this->authorize('update_user');

        $user = User::findOrFail($id);

        $user->update([
            'is_active' => !$user->is_active
        ]);

        $this->dispatch(
            'swal-toast',
            icon: 'success',
            title: 'Berhasil',
            text: $user->is_active ? 'User diaktifkan kembali' : 'User dinonaktifkan'
        );
    }

    public function confirmDelete($id)
    {
        $this->authorize('delete_role');

        $this->userToDelete = $id;
        $user = User::findOrFail($id);
        $message = $user
            ? "Apakah Anda yakin ingin menghapus pengguna \"{$user->name}\"?"
            : "Data pengguna tidak ditemukan.";

        $this->dispatch('confirm-delete', message: $message, eventName: 'deleteUser');
    }

    #[On('deleteUser')]
    public function deleteUser()
    {
        $this->authorize('delete_user');

        if (!$this->userToDelete) {
            return;
        }

        $user = User::find($this->userToDelete);

        if (!$user) {
            $this->dispatch('swal-toast', icon: 'error', title: 'Gagal', text: "Data pengguna tidak ditemukan!");
            $this->userToDelete = null;
            return;
        }

        $user->delete();
        $this->dispatch('swal-toast', icon: 'success', title: 'Berhasil', text: 'Pengguna berhasil dihapus!');

        $this->userToDelete = null;
        $this->resetPage();
    }

    #[Computed]
    public function roles()
    {
        return Role::all();
    }
};
