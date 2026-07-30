<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Catálogo · {{ config('sweetgo.negocio.nombre', 'Sweet Go') }}</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&family=playfair-display:600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .font-serif { font-family: 'Playfair Display', serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="font-sans antialiased bg-[#FDF7FB] text-gray-800"
      x-data="catalogo({ productos: {{ Illuminate\Support\Js::from($productos->map(fn($p) => [
          'id' => $p->id,
          'nombre' => $p->nombre,
          'referencia' => $p->referencia,
          'precio' => (float) $p->precio,
          'categoria' => $p->categoria?->nombre,
          'categoria_id' => $p->categoria_id,
          'imagen' => $p->imagen ? \Illuminate\Support\Facades\Storage::url($p->imagen) : null,
          'agotado' => $p->stock_actual <= 0,
      ])) }}, whatsapp: '{{ $whatsapp }}' })">

    {{-- Header --}}
    <header class="bg-white border-b border-sweetgo-pink-light sticky top-0 z-20">
        <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="/" class="block">
                <img src="{{ asset('img/sweetgo-logo.png') }}" alt="Sweet Go — Beauty Experts" class="h-12 w-auto select-none">
            </a>
            <a :href="'https://wa.me/' + whatsapp + '?text=' + encodeURIComponent('¡Hola Sweet Go! Vengo del catálogo 💖')"
               target="_blank" class="hidden sm:inline-flex items-center gap-2 px-4 py-2 rounded-full bg-green-500 text-white text-sm font-medium hover:opacity-90">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.5 14.4c-.3-.1-1.7-.9-2-1-.3-.1-.5-.1-.6.1-.2.3-.7 1-.9 1.1-.2.2-.3.2-.6.1-.3-.1-1.2-.5-2.3-1.4-.9-.8-1.4-1.7-1.6-2-.2-.3 0-.5.1-.6.1-.1.3-.3.4-.5.1-.1.2-.3.3-.4.1-.2 0-.3 0-.5s-.6-1.5-.8-2c-.2-.5-.4-.4-.6-.5H8c-.2 0-.5.1-.7.3-.2.3-.9.9-.9 2.2s.9 2.5 1 2.7c.1.2 1.8 2.8 4.4 3.9 1.9.8 2.4.8 2.9.6.4-.1 1.7-.7 1.9-1.3.2-.6.2-1.2.2-1.3-.1-.1-.3-.2-.6-.3z"/><path d="M12 2C6.5 2 2 6.5 2 12c0 1.8.5 3.5 1.3 5L2 22l5.1-1.3C8.5 21.5 10.2 22 12 22c5.5 0 10-4.5 10-10S17.5 2 12 2zm0 18c-1.6 0-3.1-.4-4.4-1.2l-.3-.2-3 .8.8-2.9-.2-.3C4 15 3.5 13.5 3.5 12 3.5 7.3 7.3 3.5 12 3.5S20.5 7.3 20.5 12 16.7 20.5 12 20.5z"/></svg>
                WhatsApp
            </a>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-6">
        {{-- Buscador --}}
        <div class="mb-5">
            <input type="text" x-model="buscar" placeholder="Buscar producto o referencia…"
                   class="w-full rounded-xl border-gray-200 shadow-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
        </div>

        {{-- Chips de categorías --}}
        <div class="flex flex-wrap gap-2 mb-6">
            <button @click="cat = null" :class="cat === null ? 'bg-sweetgo-pink text-white' : 'bg-white text-gray-600 border border-sweetgo-pink-light'"
                    class="px-4 py-1.5 rounded-full text-sm font-medium">Todos</button>
            @foreach ($categorias as $c)
                <button @click="cat = {{ $c->id }}" :class="cat === {{ $c->id }} ? 'bg-sweetgo-pink text-white' : 'bg-white text-gray-600 border border-sweetgo-pink-light'"
                        class="px-4 py-1.5 rounded-full text-sm font-medium">{{ $c->nombre }}</button>
            @endforeach
        </div>

        {{-- Grid de productos --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            <template x-for="p in filtrados()" :key="p.id">
                <div class="bg-white rounded-2xl border border-sweetgo-pink-light overflow-hidden shadow-sm hover:shadow-md transition flex flex-col relative"
                     :class="p.agotado && 'opacity-80'">
                    <div class="aspect-square bg-sweetgo-pink-light/50 flex items-center justify-center overflow-hidden relative">
                        <template x-if="p.imagen">
                            <img :src="p.imagen" :alt="p.nombre" class="w-full h-full object-contain object-center p-2" :class="p.agotado && 'grayscale'">
                        </template>
                        <template x-if="!p.imagen">
                            <span class="text-4xl text-sweetgo-pink">&#10022;</span>
                        </template>
                        <span x-show="p.agotado" class="absolute top-2 right-2 px-2 py-0.5 rounded-full bg-gray-800/90 text-white text-[10px] font-bold uppercase tracking-wider">Agotado</span>
                    </div>
                    <div class="p-3 flex flex-col flex-1">
                        <p class="text-[10px] text-sweetgo-turquoise uppercase tracking-wide" x-text="p.categoria || ''"></p>
                        <h3 class="text-sm font-medium text-gray-800 leading-tight mt-0.5 flex-1" x-text="p.nombre"></h3>
                        <p class="text-xs text-gray-400 mt-1" x-show="p.referencia" x-text="'Ref: ' + p.referencia"></p>
                        <p class="text-lg font-bold text-sweetgo-pink mt-1" x-text="money(p.precio)"></p>
                        <a x-show="!p.agotado" :href="linkWhatsApp(p)" target="_blank"
                           class="mt-2 text-center px-3 py-1.5 rounded-lg bg-green-500 text-white text-xs font-medium hover:opacity-90">Pedir por WhatsApp</a>
                        <span x-show="p.agotado" class="mt-2 text-center px-3 py-1.5 rounded-lg bg-gray-100 text-gray-400 text-xs font-medium cursor-not-allowed">No disponible</span>
                    </div>
                </div>
            </template>
        </div>

        <div x-show="filtrados().length === 0" x-cloak class="text-center text-gray-400 py-16">
            No se encontraron productos.
        </div>
    </main>

    {{-- Botón flotante WhatsApp (móvil) --}}
    <a :href="'https://wa.me/' + whatsapp + '?text=' + encodeURIComponent('¡Hola Sweet Go! Vengo del catálogo 💖')"
       target="_blank" class="sm:hidden fixed bottom-5 right-5 z-30 w-14 h-14 rounded-full bg-green-500 text-white shadow-lg flex items-center justify-center">
        <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.5 2 2 6.5 2 12c0 1.8.5 3.5 1.3 5L2 22l5.1-1.3C8.5 21.5 10.2 22 12 22c5.5 0 10-4.5 10-10S17.5 2 12 2zm0 18c-1.6 0-3.1-.4-4.4-1.2l-.3-.2-3 .8.8-2.9-.2-.3C4 15 3.5 13.5 3.5 12 3.5 7.3 7.3 3.5 12 3.5S20.5 7.3 20.5 12 16.7 20.5 12 20.5zm5.5-6.1c-.3-.1-1.7-.9-2-1-.3-.1-.5-.1-.6.1-.2.3-.7 1-.9 1.1-.2.2-.3.2-.6.1-.3-.1-1.2-.5-2.3-1.4-.9-.8-1.4-1.7-1.6-2-.2-.3 0-.5.1-.6.1-.1.3-.3.4-.5.1-.1.2-.3.3-.4.1-.2 0-.3 0-.5s-.6-1.5-.8-2c-.2-.5-.4-.4-.6-.5h-.5c-.2 0-.5.1-.7.3-.2.3-.9.9-.9 2.2s.9 2.5 1 2.7c.1.2 1.8 2.8 4.4 3.9 1.9.8 2.4.8 2.9.6.4-.1 1.7-.7 1.9-1.3.2-.6.2-1.2.2-1.3-.1-.1-.3-.2-.6-.3z"/></svg>
    </a>

    <footer class="max-w-6xl mx-auto px-4 py-8 text-center text-xs text-gray-400">
        Sweet Go · Beauty Experts &nbsp;|&nbsp; Precios en pesos colombianos (COP), sujetos a cambio sin previo aviso.
    </footer>

    <script>
        function catalogo({ productos, whatsapp }) {
            return {
                productos, whatsapp,
                buscar: '', cat: null,
                money(v) { return '$' + Math.round(v || 0).toLocaleString('es-CO'); },
                filtrados() {
                    const b = this.buscar.trim().toLowerCase();
                    return this.productos.filter(p => {
                        const okCat = this.cat === null || p.categoria_id === this.cat;
                        const okBuscar = !b || p.nombre.toLowerCase().includes(b) || (p.referencia || '').toLowerCase().includes(b);
                        return okCat && okBuscar;
                    });
                },
                linkWhatsApp(p) {
                    const msg = `¡Hola Sweet Go! 💖 Me interesa: ${p.nombre}` + (p.referencia ? ` (Ref ${p.referencia})` : '') + ` - ${this.money(p.precio)}`;
                    return 'https://wa.me/' + this.whatsapp + '?text=' + encodeURIComponent(msg);
                },
            };
        }
    </script>
</body>
</html>
