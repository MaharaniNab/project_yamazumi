<x-layouts::auth>
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Name -->
            <div class="flex flex-col gap-2">
                <label for="name" class="text-gray-400 text-[10px]">
                    {{ __('NAME') }}
                </label>
                <flux:input name="name" :value="old('name')" type="text" required autofocus autocomplete="name"
                    :placeholder="__('Full name')"
                    class="dark:text-white dark:placeholder:text-gray-400 dark:border-gray-600" />
            </div>

            <!-- Email Address -->
            <div class="flex flex-col gap-2">
                <label for="email" class="text-gray-400 text-[10px]">
                    {{ __('EMAIL ADDRESS') }}
                </label>
                <flux:input name="email" :value="old('email')" type="email" required autocomplete="email"
                    placeholder="email@example.com"
                    class="dark:text-white dark:placeholder:text-gray-400 dark:border-gray-600" />
            </div>

            <!-- Password -->
            <div class="flex flex-col gap-2">
                <label for="password" class="text-gray-400 text-[10px]">
                    {{ __('PASSWORD') }}
                </label>
                <flux:input name="password" type="password" required autocomplete="new-password"
                    :placeholder="__('Password')" />
            </div>

            <!-- Confirm Password -->
            <div class="flex flex-col gap-2">
                <label for="password_confirmation" class="text-gray-400 text-[10px]">
                    {{ __('CONFIRM PASSWORD') }}
                </label>
                <flux:input name="password_confirmation" type="password" required autocomplete="new-password"
                    :placeholder="__('Confirm password')" />
            </div>

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" style="background-color: rgb(42, 82, 152);"
                    data-test="register-user-button">
                    {{ __('Create account') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-400">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link :href="route('login')" wire:navigate style="color: rgb(42, 82, 152);">{{ __('Log in') }}
            </flux:link>
        </div>
    </div>
</x-layouts::auth>