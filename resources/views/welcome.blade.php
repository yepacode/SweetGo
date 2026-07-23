<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Sweet Go') }} · Beauty Experts</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&family=playfair-display:600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>.font-serif { font-family: 'Playfair Display', serif; }</style>
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen flex flex-col items-center justify-center bg-gradient-to-br from-sweetgo-pink-light via-white to-sweetgo-turquoise-light px-6">
        <div class="text-center max-w-xl">
            <x-brand class="h-32 mx-auto" />

            <p class="mt-6 text-gray-500 leading-relaxed">
                Sistema de gestión comercial e inventario. Catálogo, cotizaciones, garantías y control de stock
                en un solo lugar.
            </p>

            <div class="mt-10 flex items-center justify-center gap-4">
                @auth
                    <a href="{{ route('dashboard') }}"
                       class="px-6 py-3 rounded-full bg-sweetgo-pink text-white font-medium shadow-sm hover:opacity-90 transition">
                        Ir al panel
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="px-6 py-3 rounded-full bg-sweetgo-pink text-white font-medium shadow-sm hover:opacity-90 transition">
                        Iniciar sesión
                    </a>
                @endauth
            </div>
        </div>

        <footer class="mt-16 text-xs text-gray-400">
            &copy; {{ date('Y') }} Sweet Go · Desarrollado por MY Tech Solutions
        </footer>
    </div>
</body>
</html>
