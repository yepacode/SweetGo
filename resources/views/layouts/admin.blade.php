<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Sweet Go') }} @hasSection('title') · @yield('title') @endif</title>
    @include('partials.favicon')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&family=playfair-display:600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .font-serif { font-family: 'Playfair Display', serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="font-sans antialiased bg-[#FDF7FB] text-gray-800">
<div x-data="{ sidebarOpen: false }" class="min-h-screen flex"
     x-init="window.addEventListener('pageshow', e => { if (e.persisted) sidebarOpen = false })">

    {{-- ===================== SIDEBAR ===================== --}}
    <aside
        class="fixed inset-y-0 left-0 z-40 w-64 bg-white border-r border-sweetgo-pink-light shadow-sm transform transition-transform duration-200 lg:translate-x-0 lg:static lg:inset-0"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">

        <div class="h-16 flex items-center px-6 border-b border-sweetgo-pink-light">
            <a href="{{ route('dashboard') }}">
                <x-brand class="h-9" />
            </a>
        </div>

        <nav class="p-4 space-y-1 overflow-y-auto" style="height: calc(100vh - 4rem);">
            @php
                $esAdmin = auth()->user()?->hasRole('admin');

                // Módulos base (vendedor solo ve Dashboard, Clientes, Cotizaciones, Catálogo).
                $modules = [
                    ['label' => 'Dashboard',    'route' => 'dashboard',        'icon' => 'M3 12l9-9 9 9M5 10v10a1 1 0 001 1h4v-6h4v6h4a1 1 0 001-1V10'],
                    ['label' => 'Clientes',      'route' => 'clientes.index',   'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z'],
                    ['label' => 'Cotizaciones',  'route' => 'cotizaciones.index','icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                    ['label' => 'Catálogo',      'route' => 'catalogo.index',   'icon' => 'M12 6.5A2.5 2.5 0 0114.5 4H21v14h-6.5a2.5 2.5 0 00-2.5 2.5M12 6.5A2.5 2.5 0 009.5 4H3v14h6.5a2.5 2.5 0 012.5 2.5M12 6.5v14'],
                ];

                // Créditos / cuentas por cobrar — visible a todos (admin ve todo, vendedor lo suyo)
                $modules[] = ['label' => 'Créditos',   'route' => 'creditos.index',   'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'];

                if ($esAdmin) {
                    // Módulos solo para administrador: inventario, productos, garantías, links, usuarios, reportes, bitácora.
                    $modules[] = ['label' => 'Productos',  'route' => 'productos.index',  'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'];
                    $modules[] = ['label' => 'Inventario', 'route' => 'stock.index',      'icon' => 'M4 7v10a2 2 0 002 2h12a2 2 0 002-2V7M4 7l2-3h12l2 3M9 12h6'];
                    $modules[] = ['label' => 'Garantías',  'route' => 'garantias.index',  'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'];
                    $modules[] = ['label' => 'Zonas envío','route' => 'zonas-envio.index','icon' => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7'];
                    $modules[] = ['label' => 'Links',      'route' => 'links.index',      'icon' => 'M13.828 10.172a4 4 0 015.656 5.656l-3 3a4 4 0 01-5.656-5.656M10.172 13.828a4 4 0 01-5.656-5.656l3-3a4 4 0 015.656 5.656'];
                    $modules[] = ['label' => 'Usuarios',   'route' => 'usuarios.index',   'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'];
                    $modules[] = ['label' => 'Reportes',   'route' => 'reportes.index',   'icon' => 'M9 17v-2a2 2 0 00-2-2H5a2 2 0 00-2 2v2m6 0h6m-6 0v2m6-2v2m0-2a2 2 0 012-2h2a2 2 0 012 2v0M9 5a2 2 0 012-2h2a2 2 0 012 2v8H9V5z'];
                    $modules[] = ['label' => 'Bitácora',   'route' => 'bitacora.index',   'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'];
                }
            @endphp

            @foreach ($modules as $m)
                @php
                    $exists = \Illuminate\Support\Facades\Route::has($m['route']);
                    $active = $exists && request()->routeIs($m['route']);
                @endphp
                <a href="{{ $exists ? route($m['route']) : '#' }}" @click="sidebarOpen = false"
                   @class([
                       'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition',
                       'bg-sweetgo-pink text-white shadow-sm' => $active,
                       'text-gray-600 hover:bg-sweetgo-pink-light hover:text-sweetgo-pink' => !$active,
                       'opacity-50 cursor-not-allowed' => !$exists,
                   ])>
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $m['icon'] }}" />
                    </svg>
                    <span>{{ $m['label'] }}</span>
                    @unless ($exists)
                        <span class="ml-auto text-[10px] uppercase tracking-wide text-gray-400">pronto</span>
                    @endunless
                </a>
            @endforeach
        </nav>
    </aside>

    {{-- overlay móvil --}}
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
         class="fixed inset-0 z-30 bg-black/30 lg:hidden"></div>

    {{-- ===================== CONTENIDO ===================== --}}
    <div class="flex-1 flex flex-col min-w-0">
        {{-- topbar --}}
        <header class="h-16 bg-white border-b border-sweetgo-pink-light flex items-center justify-between px-4 lg:px-8 sticky top-0 z-20">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-gray-500 hover:text-sweetgo-pink">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="hidden md:block text-lg font-semibold text-gray-700">@yield('title', 'Panel')</h1>

                {{-- Búsqueda global --}}
                <div x-data="busquedaGlobal()"
                     @keydown.window.slash.prevent="if (document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') { $refs.input.focus() }"
                     class="relative ml-2 md:ml-6 w-56 sm:w-72">
                    <div class="relative">
                        <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/>
                        </svg>
                        <input x-ref="input" type="text" x-model="q" @input.debounce.300ms="buscar()" @focus="abierto = true" @keydown.escape="abierto = false; q = ''"
                               placeholder="Buscar…"
                               class="w-full pl-9 pr-8 py-1.5 rounded-lg border border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                        <kbd class="hidden md:inline-block absolute right-2 top-1/2 -translate-y-1/2 text-[10px] text-gray-400 border border-gray-200 rounded px-1.5">/</kbd>
                    </div>
                    <div x-show="abierto && q.length >= 2" x-cloak @click.outside="abierto = false"
                         class="absolute left-0 mt-2 w-96 max-w-[90vw] bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden z-40 text-sm max-h-[70vh] overflow-y-auto">
                        <template x-if="cargando">
                            <p class="px-4 py-6 text-center text-gray-400">Buscando…</p>
                        </template>
                        <template x-if="!cargando && grupos.length === 0">
                            <p class="px-4 py-6 text-center text-gray-400">Sin resultados para "<span x-text="q"></span>"</p>
                        </template>
                        <template x-for="grupo in grupos" :key="grupo.titulo">
                            <div>
                                <div class="px-4 py-2 bg-sweetgo-pink-light/60 text-xs font-medium text-sweetgo-pink uppercase tracking-wide" x-text="grupo.titulo"></div>
                                <template x-for="item in grupo.items" :key="grupo.titulo + '-' + item.url">
                                    <a :href="item.url" class="block px-4 py-2 hover:bg-sweetgo-pink-light/30 border-b border-gray-50">
                                        <div class="text-gray-800 font-medium truncate" x-text="item.label"></div>
                                        <div class="text-xs text-gray-400 truncate" x-text="item.sub"></div>
                                    </a>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4">
                {{-- Centro de notificaciones --}}
                <div x-data="{ notifOpen: false }" class="relative">
                    <button @click="notifOpen = !notifOpen"
                            class="relative w-9 h-9 rounded-full text-gray-500 hover:text-sweetgo-pink hover:bg-sweetgo-pink-light flex items-center justify-center"
                            aria-label="Notificaciones">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        @if ($notificaciones['total'] > 0)
                            <span class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-sweetgo-pink text-white text-[10px] font-bold flex items-center justify-center">
                                {{ $notificaciones['total'] > 99 ? '99+' : $notificaciones['total'] }}
                            </span>
                        @endif
                    </button>

                    <div x-show="notifOpen" x-cloak @click.outside="notifOpen = false"
                         class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden text-sm z-40">
                        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                            <span class="font-semibold text-gray-700">Notificaciones</span>
                            <span class="text-xs text-gray-400">{{ $notificaciones['total'] }} en total</span>
                        </div>
                        <div class="max-h-96 overflow-y-auto divide-y divide-gray-100">
                            @if (! empty($notificaciones['alertas']) && $notificaciones['alertas']->isNotEmpty())
                                <div class="px-4 py-2 bg-sweetgo-pink-light text-xs font-medium text-sweetgo-pink uppercase tracking-wide flex items-center justify-between">
                                    <span>Alertas ({{ $notificaciones['alertas']->count() }})</span>
                                    <form method="POST" action="{{ route('notificaciones.leidas') }}" class="inline">
                                        @csrf
                                        <button class="text-[10px] normal-case text-gray-400 hover:text-sweetgo-pink hover:underline">marcar todas leídas</button>
                                    </form>
                                </div>
                                @foreach ($notificaciones['alertas'] as $al)
                                    <a href="{{ $al->url ?? '#' }}" class="block px-4 py-2 hover:bg-sweetgo-pink-light/30">
                                        <div class="flex items-start justify-between gap-2">
                                            <span class="font-medium text-gray-800 text-xs">{{ $al->titulo }}</span>
                                            <span class="text-[10px] text-gray-400 whitespace-nowrap">{{ $al->created_at->diffForHumans() }}</span>
                                        </div>
                                        <div class="text-xs text-gray-600 mt-0.5 whitespace-pre-line line-clamp-6">{{ $al->mensaje }}</div>
                                    </a>
                                @endforeach
                            @endif

                            @if ($notificaciones['stockBajo']->isNotEmpty())
                                <div class="px-4 py-2 bg-red-50 text-xs font-medium text-red-600 uppercase tracking-wide">Stock bajo</div>
                                @foreach ($notificaciones['stockBajo'] as $p)
                                    <a href="{{ route('stock.kardex', $p) }}" class="flex items-center justify-between px-4 py-2 hover:bg-sweetgo-pink-light/30">
                                        <span class="text-gray-700 truncate">{{ $p->nombre }}</span>
                                        <span class="ml-2 inline-block px-2 py-0.5 rounded-full bg-red-100 text-red-600 text-xs font-medium">{{ $p->stock_actual }}/{{ $p->stock_minimo }}</span>
                                    </a>
                                @endforeach
                            @endif

                            @if (! empty($notificaciones['creditosVencidos']) && $notificaciones['creditosVencidos']->isNotEmpty())
                                <div class="px-4 py-2 bg-red-50 text-xs font-medium text-red-600 uppercase tracking-wide">Créditos vencidos</div>
                                @foreach ($notificaciones['creditosVencidos'] as $c)
                                    <a href="{{ route('cotizaciones.show', $c) }}" class="flex items-center justify-between px-4 py-2 hover:bg-sweetgo-pink-light/30">
                                        <span class="text-gray-700 truncate">{{ $c->numero }} · {{ $c->cliente?->nombre }}</span>
                                        <span class="ml-2 inline-block px-2 py-0.5 rounded-full bg-red-100 text-red-600 text-xs font-medium">${{ number_format($c->saldoCredito(), 0, ',', '.') }}</span>
                                    </a>
                                @endforeach
                            @endif

                            @if (! empty($notificaciones['creditosPorVencer']) && $notificaciones['creditosPorVencer']->isNotEmpty())
                                <div class="px-4 py-2 bg-amber-50 text-xs font-medium text-amber-700 uppercase tracking-wide">Créditos por vencer</div>
                                @foreach ($notificaciones['creditosPorVencer'] as $c)
                                    <a href="{{ route('cotizaciones.show', $c) }}" class="flex items-center justify-between px-4 py-2 hover:bg-sweetgo-pink-light/30">
                                        <span class="text-gray-700 truncate">{{ $c->numero }} · {{ $c->cliente?->nombre }}</span>
                                        <span class="ml-2 inline-block px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-xs font-medium">${{ number_format($c->saldoCredito(), 0, ',', '.') }}</span>
                                    </a>
                                @endforeach
                            @endif

                            @if ($notificaciones['cotizacionesEnviadas']->isNotEmpty())
                                <div class="px-4 py-2 bg-sweetgo-turquoise-light/50 text-xs font-medium text-teal-700 uppercase tracking-wide">Cotizaciones enviadas</div>
                                @foreach ($notificaciones['cotizacionesEnviadas'] as $cot)
                                    <a href="{{ route('cotizaciones.show', $cot) }}" class="flex items-center justify-between px-4 py-2 hover:bg-sweetgo-pink-light/30">
                                        <span class="text-gray-700">{{ $cot->numero }}</span>
                                        <span class="text-xs text-gray-500">${{ number_format($cot->total, 0, ',', '.') }}</span>
                                    </a>
                                @endforeach
                            @endif

                            @if ($notificaciones['garantiasAbiertas']->isNotEmpty())
                                <div class="px-4 py-2 bg-amber-50 text-xs font-medium text-amber-700 uppercase tracking-wide">Garantías abiertas</div>
                                @foreach ($notificaciones['garantiasAbiertas'] as $g)
                                    <a href="{{ route('garantias.show', $g) }}" class="flex items-center justify-between px-4 py-2 hover:bg-sweetgo-pink-light/30">
                                        <span class="text-gray-700">{{ $g->numero }}</span>
                                        <span class="text-xs text-gray-500">{{ ucfirst(str_replace('_', ' ', $g->estado)) }}</span>
                                    </a>
                                @endforeach
                            @endif

                            @if ($notificaciones['total'] === 0)
                                <p class="px-4 py-8 text-center text-gray-400">No tienes notificaciones. Todo en orden ✓</p>
                            @endif
                        </div>
                    </div>
                </div>

                <span class="hidden sm:inline text-sm text-gray-500">{{ auth()->user()?->name }}</span>
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="w-9 h-9 rounded-full bg-sweetgo-turquoise text-white flex items-center justify-center font-semibold">
                        {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}
                    </button>
                    <div x-show="open" x-cloak @click.outside="open = false"
                         class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-100 py-1 text-sm z-40">
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-gray-600 hover:bg-sweetgo-pink-light">Mi perfil</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-gray-600 hover:bg-sweetgo-pink-light">Cerrar sesión</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        {{-- flash --}}
        @if (session('success'))
            <div class="mx-4 lg:mx-8 mt-4 rounded-lg bg-sweetgo-turquoise-light border border-sweetgo-turquoise text-teal-800 px-4 py-3 text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mx-4 lg:mx-8 mt-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
                {{ session('error') }}
            </div>
        @endif

        <main class="flex-1 p-4 lg:p-8">
            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </div>
</div>
@stack('scripts')

<script>
    // Búsqueda global del topbar
    function busquedaGlobal() {
        return {
            q: '', abierto: false, cargando: false, grupos: [],
            async buscar() {
                if (this.q.trim().length < 2) { this.grupos = []; this.abierto = false; return; }
                this.cargando = true; this.abierto = true;
                try {
                    const r = await fetch('{{ route('buscar') }}?q=' + encodeURIComponent(this.q));
                    const data = await r.json();
                    this.grupos = data.grupos || [];
                } catch (e) { this.grupos = []; }
                finally { this.cargando = false; }
            },
        };
    }
</script>
</body>
</html>
