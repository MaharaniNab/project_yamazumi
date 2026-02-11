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
    class extends Component
    {
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
        public $showUserModal = false;


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
            $this->identifier = $user->nik ?? $user->username ?? $user->email;
            $this->role_id = $user->roles->first()?->id;
            $this->showUserModal = true;
        }

        public function createUser()
        {
            $this->authorize('create_user');
            $this->showUserModal = true;
        }

        public function save()
        {
            $this->authorize('update_user');
            $this->validate(
                [
                    'name' => 'required|string|max:50',
                    'email' => 'required|email|unique:users,email',
                    'password' => 'required|string|min:8|confirmed',
                ]
            );
            $user = User::updateOrCreate(
                ['id' => $this->userId],
                [
                    'name' => $this->name,
                    'email' => $this->email,
                    'password' => Hash::make($this->password),
                    'email_verified_at' => $this->now() ?? null,
                ]
            );

            if ($this->role_id) {
                $role = Role::findOrFail($this->role_id);
                $user->syncRoles([$role->name]);
            }

            $this->dispatch('swal-toast', icon: 'success', title: 'Berhasil', text: $this->roleId
                ? 'User berhasil diperbarui!'
                : 'User berhasil dibuat!');
            $this->showUserModal = false;
            $this->reset(['userId', 'name', 'email', 'password', 'role_id']);
            $this->resetPage();
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
