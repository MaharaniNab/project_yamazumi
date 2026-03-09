<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-transparent antialiased">

    <div class="relative flex min-h-screen flex-col items-center justify-center gap-6 p-6 md:p-10">

        <!-- Background gambar murni tanpa overlay atau warna -->
        <div class="absolute inset-0 bg-cover bg-center z-0" 
             style="background-image: url({{ asset('assets/Background_2.png') }}); opacity: 0.3;">
        </div>

        <!-- Konten di atas background -->
        <div class="relative z-30 flex w-full max-w-sm flex-col gap-2">
            <a href="{{ route('dashboard') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                <span class="flex h-9 w-9 mb-1 items-center justify-center rounded-md">
                    <x-app-logo-icon class="size-9 fill-current text-black dark:text-white" />
                </span>
                <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
            </a>

            <div class="flex flex-col gap-6">
                {{ $slot }}
            </div>
        </div>
    </div>

    @fluxScripts
</body>
</html>
