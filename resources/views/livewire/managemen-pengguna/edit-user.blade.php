<div class="bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 rounded-xl shadow-md">
    <div class="flex items-center justify-between border-b border-gray-100 dark:border-zinc-700 px-4 py-2">
        <h3>Edit Pengguna</h3>
        <a href="{{ route('manajemen.user') }}" wire:navigate class="text-blue-600 hover:text-blue-800 transition">
            <flux:icon icon="arrow-left-circle" class="size-8" />
        </a>
    </div>

    <form wire:submit.prevent="update" class="p-6 space-y-3">

        <!-- Nama -->
        <div>
            <label class="block text-sm font-semibold mb-1">Nama</label>
            <input type="text" wire:model.defer="name" class="w-full border rounded px-3 py-2 text-sm">
            @error('name')
            <span class="text-red-500 text-xs">{{ $message }}</span>
            @enderror
        </div>

        <!-- Email -->
        <div>
            <label class="block text-sm font-semibold mb-1">Email</label>
            <input type="email" wire:model.defer="email" class="w-full border rounded px-3 py-2 text-sm">
            @error('email')
            <span class="text-red-500 text-xs">{{ $message }}</span>
            @enderror
        </div>

        <!-- Password -->
        <div>
            <label class="block text-sm font-semibold mb-1">Password</label>
            <input type="password" wire:model.defer="password" placeholder="Kosongkan jika tidak diganti" class="w-full border rounded px-3 py-2 text-sm">
            @error('password')
            <span class="text-red-500 text-xs">{{ $message }}</span>
            @enderror
        </div>

        <!-- Role -->
        <div>
            <label class="block text-sm font-semibold mb-1">Role</label>
            <select wire:model.defer="role_id" class="w-full border rounded px-3 py-2 text-sm bg-white dark:bg-zinc-800">
                <option value="">-- Pilih Role --</option>
                @foreach($roles as $role)
                <option value="{{ $role->id }}">{{ $role->name }}</option>
                @endforeach
            </select>
            @error('role_id')
            <span class="text-red-500 text-xs">{{ $message }}</span>
            @enderror
        </div>

        <!-- Tombol Aksi -->
        <div class="mt-4 flex justify-end gap-2">
            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded text-sm">
                Update
            </button>
        </div>
    </form>
</div>
