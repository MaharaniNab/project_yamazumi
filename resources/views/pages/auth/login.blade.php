<x-layouts::auth>
    <div class="flex flex-col gap-6 text-white">

        {{-- Header --}}
        <x-auth-header :title="__('Selamat Datang')" :description="__('Masuk ke sistem untuk memulai analisis lini')"
            style="color: rgb(42, 82, 152);" />

        {{-- Session Status --}}
        <x-auth-session-status class="text-center text-white" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">

            @csrf

            {{-- Email Address --}}
            <div class="flex flex-col gap-2">
                <label for="email" class="text-gray-400 text-[10px]">
                    {{ __('EMAIL ADDRESS') }}
                </label>
                <flux:input name="email" :value="old('email')" type="email" required autofocus autocomplete="email"
                    placeholder="ie@fukuryo.com" />
            </div>
            <div class="relative">
                <div class="flex flex-col gap-2">
                    <label for="password" class="text-gray-400 text-[10px]">
                        {{ __('PASSWORD') }}
                    </label>
                    <flux:input name="password" type="password" required viewable
                    autocomplete="current-password" :placeholder="__('Password')" />
                </div>

                @if (Route::has('password.request'))
                    <flux:link class="absolute top-0 text-sm end-0" style="color: rgb(42, 82, 152);"
                        :href="route('password.request')" wire:navigate>
                        {{ __('Forgot your password?') }}
                    </flux:link>
                @endif
            </div>

            {{-- Submit Button --}}
            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full
                       text-white shadow-lg" style="background-color: rgb(42, 82, 152);" data-test="login-button">
                    {{ __('Masuk ke Sistem') }}
                </flux:button>
            </div>
        </form>

        @if (Route::has('register'))
            <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-400">
                <span>{{ __('Don\'t have an account?') }}</span>
                <flux:link :href="route('register')" wire:navigate style="color: rgb(42, 82, 152);">
                    {{ __('Sign up') }}
                </flux:link>
            </div>
        @endif

    </div>
</x-layouts::auth>