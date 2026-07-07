<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Sweet Go') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&family=playfair-display:600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>.font-serif { font-family: 'Playfair Display', serif; }</style>
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gradient-to-br from-sweetgo-pink-light via-white to-sweetgo-turquoise-light">
            <div class="text-center">
                <a href="/">
                    <x-brand class="text-4xl" />
                </a>
                <p class="mt-1 text-xs tracking-[0.3em] uppercase text-sweetgo-turquoise">Beauty Experts</p>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-6 bg-white shadow-lg overflow-hidden sm:rounded-2xl border border-sweetgo-pink-light">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
