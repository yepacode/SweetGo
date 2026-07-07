<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Error') · Sweet Go</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&family=playfair-display:600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>.font-serif { font-family: 'Playfair Display', serif; }</style>
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen flex flex-col items-center justify-center bg-gradient-to-br from-sweetgo-pink-light via-white to-sweetgo-turquoise-light px-6 py-10 text-center">
        <a href="/" class="mb-6">
            <span class="font-serif text-4xl">
                <span class="text-sweetgo-pink font-bold">Sweet</span>
                <span class="text-sweetgo-turquoise mx-1">&#10022;</span>
                <span class="text-sweetgo-pink font-bold">Go</span>
            </span>
        </a>

        <div class="text-7xl font-bold text-sweetgo-pink">@yield('codigo')</div>
        <h1 class="mt-4 text-2xl font-semibold text-gray-800">@yield('titulo')</h1>
        <p class="mt-2 text-gray-500 max-w-md">@yield('descripcion')</p>

        <div class="mt-8 flex gap-3">
            @auth
                <a href="{{ route('dashboard') }}" class="px-6 py-3 rounded-full bg-sweetgo-pink text-white font-medium shadow-sm hover:opacity-90">Volver al panel</a>
            @else
                <a href="/" class="px-6 py-3 rounded-full bg-sweetgo-pink text-white font-medium shadow-sm hover:opacity-90">Ir al inicio</a>
            @endauth
        </div>
    </div>
</body>
</html>
