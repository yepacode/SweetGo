@extends('layouts.admin')

@section('title', 'Catálogo')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Catálogo</h2>
            <p class="text-sm text-gray-400">Selecciona un cliente, arma el carrito y genera la cotización en un solo flujo.</p>
        </div>
        <a href="{{ route('cotizaciones.index') }}" class="text-sm text-sweetgo-turquoise hover:underline">Ver cotizaciones existentes →</a>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-600 px-4 py-3 text-sm">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div x-data="cotizador({
            clientes: {{ Illuminate\Support\Js::from($clientes) }},
            productos: {{ Illuminate\Support\Js::from($productos) }},
            listasPrecios: {{ Illuminate\Support\Js::from($listasPrecios) }},
            csrf: '{{ csrf_token() }}',
            postUrl: '{{ route('catalogo.store') }}',
            esAdmin: {{ $esAdmin ? 'true' : 'false' }},
            vendedorIdInicial: {{ (int) auth()->id() }},
        })" x-cloak class="bg-white rounded-xl border border-sweetgo-pink-light overflow-hidden">

        {{-- PASO 1: seleccionar cliente --}}
        <div x-show="paso === 1" class="p-6">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-sm font-semibold text-gray-700">Paso 1 · Elige un cliente</h3>
                    <p class="text-xs text-gray-400">Los precios del catálogo se calculan según la lista asignada al cliente.</p>
                </div>
                <input type="text" x-model="buscarCliente" placeholder="Buscar por nombre, documento, teléfono o correo…"
                       class="w-full sm:w-96 rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink text-sm">
            </div>

            {{-- Filtros inteligentes --}}
            <div class="bg-sweetgo-pink-light/30 rounded-xl p-4 mb-5 space-y-3">
                {{-- Chips: lista de precios --}}
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="text-[10px] uppercase tracking-wide text-gray-500 font-medium mr-1">Lista</span>
                    <button type="button" @click="filtroLista = null"
                            :class="filtroLista === null ? 'bg-sweetgo-pink text-white border-sweetgo-pink' : 'bg-white text-gray-500 border-sweetgo-pink-light hover:border-sweetgo-pink'"
                            class="px-3 py-1 rounded-full text-xs font-medium border transition">
                        Todas <span class="opacity-70" x-text="'(' + clientes.length + ')'"></span>
                    </button>
                    <template x-for="lista in listasDisponibles()" :key="lista.id">
                        <button type="button" @click="filtroLista = lista.id"
                                :class="filtroLista === lista.id ? 'bg-sweetgo-pink text-white border-sweetgo-pink' : 'bg-white text-gray-500 border-sweetgo-pink-light hover:border-sweetgo-pink'"
                                class="px-3 py-1 rounded-full text-xs font-medium border transition">
                            <span x-text="lista.nombre"></span> <span class="opacity-70" x-text="'(' + lista.count + ')'"></span>
                        </button>
                    </template>
                </div>

                {{-- Chips: ciudad --}}
                <div class="flex flex-wrap items-center gap-1.5" x-show="ciudadesDisponibles().length">
                    <span class="text-[10px] uppercase tracking-wide text-gray-500 font-medium mr-1">Ciudad</span>
                    <button type="button" @click="filtroCiudad = null"
                            :class="filtroCiudad === null ? 'bg-sweetgo-turquoise text-white border-sweetgo-turquoise' : 'bg-white text-gray-500 border-sweetgo-turquoise-light hover:border-sweetgo-turquoise'"
                            class="px-3 py-1 rounded-full text-xs font-medium border transition">
                        Todas
                    </button>
                    <template x-for="ciudad in ciudadesDisponibles()" :key="ciudad.nombre">
                        <button type="button" @click="filtroCiudad = ciudad.nombre"
                                :class="filtroCiudad === ciudad.nombre ? 'bg-sweetgo-turquoise text-white border-sweetgo-turquoise' : 'bg-white text-gray-500 border-sweetgo-turquoise-light hover:border-sweetgo-turquoise'"
                                class="px-3 py-1 rounded-full text-xs font-medium border transition">
                            <span x-text="ciudad.nombre"></span> <span class="opacity-70" x-text="'(' + ciudad.count + ')'"></span>
                        </button>
                    </template>
                </div>

                {{-- Fila: toggle sucursales + orden + limpiar --}}
                <div class="flex flex-wrap items-center gap-2 pt-1">
                    <button type="button" @click="soloConSucursales = !soloConSucursales"
                            :class="soloConSucursales ? 'bg-sweetgo-pink text-white border-sweetgo-pink' : 'bg-white text-gray-500 border-gray-200 hover:border-sweetgo-pink'"
                            class="px-3 py-1 rounded-full text-xs font-medium border transition inline-flex items-center gap-1">
                        <span x-show="soloConSucursales">✓</span>
                        Con sucursales
                    </button>

                    <div class="ml-auto flex items-center gap-2">
                        <label class="text-[10px] uppercase tracking-wide text-gray-500">Ordenar</label>
                        <select x-model="orden" class="rounded-lg border-gray-200 text-xs py-1 pr-8 focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                            <option value="nombre">Nombre A→Z</option>
                            <option value="nombre-desc">Nombre Z→A</option>
                            <option value="lista">Lista de precios</option>
                            <option value="sucursales">Más sucursales</option>
                        </select>

                        <button type="button" x-show="hayFiltrosActivos()" @click="limpiarFiltros()"
                                class="text-xs text-red-400 hover:text-red-600 hover:underline">Limpiar</button>
                    </div>
                </div>

                {{-- Contador --}}
                <p class="text-xs text-gray-500 pt-1 border-t border-white/50">
                    <span x-text="clientesFiltrados().length"></span> de <span x-text="clientes.length"></span> clientes
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 items-stretch">
                <template x-for="c in clientesFiltrados()" :key="c.id">
                    <div class="bg-white rounded-xl border border-sweetgo-pink-light shadow-sm hover:shadow-md hover:border-sweetgo-pink transition flex flex-col p-4 h-full">
                        <h4 class="font-serif text-lg font-semibold text-gray-800 leading-tight uppercase" x-text="c.nombre"></h4>

                        <dl class="mt-3 text-sm text-gray-600 space-y-1.5 flex-1">
                            {{-- Razón social / documento --}}
                            <div class="flex items-center gap-2" x-show="c.documento">
                                <svg class="w-4 h-4 text-sweetgo-turquoise shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                <span x-text="[c.tipo_documento, c.documento].filter(Boolean).join(' ')"></span>
                            </div>
                            {{-- Ciudad --}}
                            <div class="flex items-center gap-2" x-show="c.ciudad">
                                <svg class="w-4 h-4 text-sweetgo-turquoise shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <span x-text="c.ciudad"></span>
                            </div>
                            {{-- Teléfono principal --}}
                            <div class="flex items-center gap-2" x-show="c.telefonos.length || c.telefono">
                                <svg class="w-4 h-4 text-sweetgo-turquoise shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.95.68l1.5 4.49a1 1 0 01-.5 1.21l-2.26 1.13a11 11 0 005.52 5.52l1.13-2.26a1 1 0 011.21-.5l4.49 1.5a1 1 0 01.68.95V19a2 2 0 01-2 2h-1C9.72 21 3 14.28 3 6V5z"/></svg>
                                <span x-text="c.telefonos[0]?.numero || c.telefono"></span>
                                <span class="text-xs text-gray-400" x-show="c.telefonos.length > 1" x-text="'+' + (c.telefonos.length - 1)"></span>
                            </div>
                            {{-- Correo principal --}}
                            <div class="flex items-center gap-2 min-w-0" x-show="c.emails.length || c.email">
                                <svg class="w-4 h-4 text-sweetgo-turquoise shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                <span class="truncate" x-text="c.emails[0]?.email || c.email"></span>
                                <span class="text-xs text-gray-400 shrink-0" x-show="c.emails.length > 1" x-text="'+' + (c.emails.length - 1)"></span>
                            </div>
                            {{-- Lista de precios --}}
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-sweetgo-turquoise shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                Lista: <span class="font-medium text-gray-700" x-text="c.lista_nombre"></span>
                            </div>
                        </dl>

                        {{-- Pill de resumen (dirección o sucursales) --}}
                        <div class="mt-3 rounded-lg bg-sweetgo-turquoise-light/60 border border-sweetgo-turquoise-light px-3 py-2 text-xs text-gray-700 flex items-start gap-2"
                             x-show="c.direccion || c.sucursales.length">
                            <svg class="w-4 h-4 text-sweetgo-turquoise shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                            <span class="flex-1">
                                <span x-show="c.direccion" x-text="c.direccion"></span>
                                <span x-show="c.sucursales.length" x-cloak
                                      x-text="(c.direccion ? ' · ' : '') + c.sucursales.length + ' sucursal' + (c.sucursales.length > 1 ? 'es' : '')"></span>
                            </span>
                        </div>

                        {{-- Botón seleccionar (siempre al fondo) --}}
                        <div class="mt-auto pt-3">
                            <button type="button" @click="elegirCliente(c)"
                                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-sweetgo-pink text-white text-sm font-medium hover:opacity-90 shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                Seleccionar
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <div x-show="clientesFiltrados().length === 0" class="text-center text-gray-400 py-16">
                <template x-if="clientes.length === 0">
                    <p>Aún no tienes clientes visibles. Pídele al administrador que te asigne clientes o <a href="{{ route('clientes.create') }}" class="text-sweetgo-pink hover:underline">crea uno nuevo</a>.</p>
                </template>
                <template x-if="clientes.length > 0">
                    <p>Ningún cliente coincide con los filtros. <button type="button" @click="limpiarFiltros()" class="text-sweetgo-pink hover:underline">Limpiar filtros</button></p>
                </template>
            </div>
        </div>

        {{-- PASO 2: catálogo full-width con drawer del carrito --}}
        <div x-show="paso === 2" class="min-h-[70vh]">
            {{-- Barra de cliente (sticky) --}}
            <div class="px-6 py-3 border-b border-sweetgo-pink-light bg-sweetgo-pink-light/30 flex items-center justify-between gap-2 sticky top-0 z-10 backdrop-blur">
                <div class="min-w-0 flex-1">
                    <p class="text-[10px] uppercase tracking-wide text-sweetgo-turquoise">Cotizando para</p>
                    <p class="font-medium text-gray-800 truncate" x-text="clienteSel.nombre"></p>
                    <p class="text-xs text-gray-500 truncate">
                        <span x-text="clienteSel.lista_nombre"></span>
                        <span x-show="clienteSel.documento"> · <span x-text="clienteSel.documento"></span></span>
                        <span x-show="clienteSel.telefono"> · <span x-text="clienteSel.telefono"></span></span>
                    </p>
                </div>
                <button type="button" @click="volverACliente()"
                        class="text-xs text-sweetgo-turquoise hover:underline whitespace-nowrap shrink-0">← Cambiar cliente</button>
            </div>

            {{-- Buscador + chips categorías --}}
            <div class="px-6 py-4 space-y-3 bg-white sticky top-[68px] z-10 border-b border-gray-100">
                <input type="text" x-model="buscarProducto" placeholder="Buscar producto o referencia…"
                       class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink text-sm">
                <div class="flex flex-wrap gap-1.5">
                    <button type="button" @click="cat = null"
                            :class="cat === null ? 'bg-sweetgo-pink text-white' : 'bg-white text-gray-500 border border-sweetgo-pink-light hover:border-sweetgo-pink'"
                            class="px-3 py-1 rounded-full text-xs font-medium">Todos</button>
                    @foreach ($categorias as $catRow)
                        <button type="button" @click="cat = {{ $catRow->id }}"
                                :class="cat === {{ $catRow->id }} ? 'bg-sweetgo-pink text-white' : 'bg-white text-gray-500 border border-sweetgo-pink-light hover:border-sweetgo-pink'"
                                class="px-3 py-1 rounded-full text-xs font-medium">{{ $catRow->nombre }}</button>
                    @endforeach
                </div>
            </div>

            {{-- Grid de productos (full width, se empuja a la izquierda cuando el drawer abre) --}}
            <div x-init="() => { window.addEventListener('resize', () => { anchoVentana = window.innerWidth }) }"
                 class="px-4 pb-6 pt-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 transition-all duration-300"
                 :style="carritoAbierto && anchoVentana >= 640 ? 'padding-right: 400px' : ''">
                <template x-for="p in productosFiltrados()" :key="p.id">
                    <div class="group bg-white rounded-xl border border-sweetgo-pink-light overflow-hidden flex flex-col hover:shadow-md hover:border-sweetgo-pink transition select-none"
                         :class="p.stock <= 0 && 'opacity-60'">
                        {{-- Imagen clickeable → abre modal de detalle --}}
                        <div x-show="!carritoAbierto" @click="verDetalle(p)"
                             class="aspect-[4/3] bg-white flex items-center justify-center overflow-hidden cursor-zoom-in">
                            <template x-if="p.imagen">
                                <img :src="p.imagen" :alt="p.nombre" class="w-full h-full object-contain object-center p-2">
                            </template>
                            <template x-if="!p.imagen">
                                <span class="text-3xl text-sweetgo-pink-light">&#10022;</span>
                            </template>
                        </div>

                        {{-- Contenido --}}
                        <div class="px-2.5 pt-2 pb-2.5 flex flex-col flex-1"
                             :class="!carritoAbierto && 'border-t border-gray-50'">
                            <p class="text-[9px] text-sweetgo-turquoise uppercase tracking-wide font-medium truncate"
                               x-text="p.categoria || 'Sin categoría'"></p>
                            <h4 @click="verDetalle(p)"
                                class="text-[12px] font-semibold text-gray-800 leading-tight mt-0.5 line-clamp-2 cursor-pointer hover:text-sweetgo-pink transition"
                                x-text="p.nombre"></h4>
                            <p class="text-[10px] text-sweetgo-turquoise font-medium mt-0.5" x-show="p.referencia"
                               x-text="'Ref ' + p.referencia"></p>
                            <p class="text-sm font-bold text-gray-800 mt-1" x-text="money(precioEn(p))"></p>

                            {{-- Chip disponibilidad --}}
                            <div class="mt-1.5">
                                <span x-show="p.stock > 0"
                                      class="inline-flex items-center gap-0.5 text-[9px] font-medium px-1.5 py-0.5 rounded bg-sweetgo-turquoise-light text-sweetgo-turquoise border border-sweetgo-turquoise/40">
                                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    <span x-text="p.stock + ' disponibles'"></span>
                                </span>
                                <span x-show="p.stock <= 0"
                                      class="inline-flex items-center text-[9px] font-medium px-1.5 py-0.5 rounded bg-gray-100 text-gray-500 border border-gray-200">Agotado</span>
                                <span x-show="p.tiene_variantes" x-cloak
                                      class="inline-flex items-center text-[9px] font-medium px-1.5 py-0.5 rounded bg-sweetgo-pink-light text-sweetgo-pink border border-sweetgo-pink/40 ml-1"
                                      x-text="p.variantes.length + ' variantes'"></span>
                            </div>

                            {{-- Botones acción (siempre visibles) --}}
                            <div class="mt-2 flex gap-1.5">
                                <button type="button" @click.stop="verDetalle(p)"
                                        class="shrink-0 px-2 py-1.5 rounded-lg border border-sweetgo-turquoise text-sweetgo-turquoise text-[11px] font-medium hover:bg-sweetgo-turquoise-light inline-flex items-center gap-1"
                                        title="Ver detalle">
                                    <svg class="w-3.5 h-3.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                                <button type="button" @click.stop="agregar(p)"
                                        :disabled="p.stock <= 0"
                                        class="flex-1 px-2 py-1.5 rounded-lg bg-sweetgo-pink text-white text-[11px] font-medium hover:opacity-90 disabled:bg-gray-200 disabled:text-gray-400 disabled:cursor-not-allowed inline-flex items-center justify-center gap-1">
                                    <template x-if="p.tiene_variantes">
                                        <span>Elegir variante</span>
                                    </template>
                                    <template x-if="!p.tiene_variantes && !enCarrito(p.id)">
                                        <span>+ Agregar</span>
                                    </template>
                                    <template x-if="!p.tiene_variantes && enCarrito(p.id)">
                                        <span>✓ (<span x-text="cantidadEn(p.id)"></span>)</span>
                                    </template>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div x-show="productosFiltrados().length === 0" class="text-center text-gray-400 py-16">
                No se encontraron productos.
            </div>

            {{-- Botón flotante del carrito (siempre visible) --}}
            <button type="button" @click="carritoAbierto = true" id="btnCarritoFlotante"
                    class="fixed bottom-6 right-6 z-30 flex items-center gap-2 px-4 py-3 rounded-full bg-sweetgo-pink text-white shadow-lg hover:opacity-90 transition font-medium">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span class="text-sm">Carrito</span>
                <span x-show="carrito.length" x-cloak
                      class="inline-flex items-center justify-center min-w-[22px] h-[22px] px-1.5 rounded-full bg-white text-sweetgo-pink text-xs font-bold"
                      x-text="carrito.length"></span>
                <span x-show="carrito.length" x-cloak class="hidden sm:inline text-sm border-l border-white/30 pl-2 ml-1"
                      x-text="money(total())"></span>
            </button>

            {{-- Overlay OPACO (bloquea completamente el catálogo detrás) --}}
            <div @click="carritoAbierto = false"
                 :class="carritoAbierto ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'"
                 class="fixed inset-0 bg-gray-900/80 z-40 transition-opacity duration-200 backdrop-blur-sm"></div>

            {{-- Drawer del carrito (desde la derecha, opaco) --}}
            <aside @keydown.escape.window="carritoAbierto = false"
                   :class="carritoAbierto ? 'translate-x-0' : 'translate-x-full'"
                   class="fixed inset-y-0 right-0 z-50 w-full sm:w-96 bg-white flex flex-col shadow-2xl transform transition-transform duration-300 border-l border-sweetgo-pink-light">
                {{-- Barra superior del drawer con botón CERRAR grande e inequívoco --}}
                <div class="px-3 py-3 border-b-2 border-sweetgo-pink-light bg-white flex items-center gap-2 shrink-0">
                    <button type="button" @click="carritoAbierto = false"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2.5 rounded-lg bg-sweetgo-pink text-white text-sm font-semibold hover:opacity-90 transition [&_*]:pointer-events-none">
                        <svg pointer-events="none" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path pointer-events="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7"/></svg>
                        <span>Cerrar carrito</span>
                    </button>
                    <button type="button" x-show="carrito.length"
                            @click.stop="confirm('¿Vaciar el carrito?') && (carrito = [])"
                            class="px-3 py-2.5 rounded-lg border border-gray-200 text-red-500 text-xs font-medium hover:bg-red-50">Vaciar</button>
                </div>

                {{-- Subtítulo con conteo --}}
                <div class="px-5 py-2 bg-sweetgo-pink-light/40 border-b border-sweetgo-pink-light shrink-0 flex items-center gap-2">
                    <svg class="w-4 h-4 text-sweetgo-pink" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <span class="text-xs font-semibold text-gray-700">Carrito · <span x-text="carrito.length"></span> item(s)</span>
                </div>

                <div class="flex-1 overflow-y-auto px-5 py-3 space-y-2">
                    <template x-if="carrito.length === 0">
                        <p class="text-xs text-gray-400 italic text-center py-8">
                            El carrito está vacío. Agrega productos desde el catálogo.
                        </p>
                    </template>
                    <template x-for="(item, i) in carrito" :key="item.producto_id">
                        <div class="bg-white rounded-lg border border-gray-100 p-3">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-800 truncate" x-text="item.nombre"></p>
                                    <p class="text-[10px] text-gray-400" x-show="item.referencia" x-text="'Ref: ' + item.referencia"></p>
                                </div>
                                <button type="button" @click="carrito.splice(i, 1)"
                                        class="text-gray-300 hover:text-red-500 text-lg leading-none" title="Quitar">×</button>
                            </div>
                            <div class="flex items-center gap-2 mt-2">
                                <div class="flex items-center border border-gray-200 rounded-lg">
                                    <button type="button" @click="item.cantidad = Math.max(1, item.cantidad - 1)"
                                            class="px-2 py-1 text-gray-500 hover:bg-gray-50">−</button>
                                    <input type="number" x-model.number="item.cantidad" min="1"
                                           :max="stockDe(item.producto_id) || undefined"
                                           @input="topearCantidad(item)"
                                           class="w-12 text-center border-0 focus:ring-0 text-sm py-1">
                                    <button type="button"
                                            @click="item.cantidad = Math.min(item.cantidad + 1, stockDe(item.producto_id) || Infinity)"
                                            :disabled="stockDe(item.producto_id) && item.cantidad >= stockDe(item.producto_id)"
                                            class="px-2 py-1 text-gray-500 hover:bg-gray-50 disabled:text-gray-300 disabled:cursor-not-allowed">+</button>
                                </div>
                                <div class="flex-1 text-right">
                                    <input type="number" x-model.number="item.precio_unitario" min="0" step="1"
                                           :readonly="!puedeEditarPrecio"
                                           class="w-24 rounded border-gray-200 text-right text-xs py-1 focus:border-sweetgo-pink focus:ring-sweetgo-pink read-only:bg-gray-50 read-only:cursor-not-allowed"
                                           :title="puedeEditarPrecio ? 'Precio unitario' : 'Solo admin puede modificar precio'">
                                </div>
                            </div>
                            <p x-show="stockDe(item.producto_id) && item.cantidad > stockDe(item.producto_id)" x-cloak
                               class="text-[10px] text-red-500 mt-1" x-text="'Stock insuficiente (máx ' + stockDe(item.producto_id) + ')'"></p>
                            <p class="text-right text-xs font-semibold text-sweetgo-pink mt-1"
                               x-text="money(item.cantidad * item.precio_unitario)"></p>
                        </div>
                    </template>
                </div>

                {{-- Comentarios + validez --}}
                <div class="px-5 py-3 border-t border-sweetgo-pink-light bg-white space-y-3">
                    <div>
                        <label class="block text-[10px] uppercase tracking-wide text-gray-500 mb-1">Observaciones</label>
                        <textarea x-model="notas" rows="2" placeholder="Orden de compra, especificaciones, condiciones especiales…"
                                  class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink text-sm"></textarea>
                    </div>
                    @if ($esAdmin)
                        <div>
                            <label class="block text-[10px] uppercase tracking-wide text-gray-500 mb-1">Asignar a vendedor</label>
                            <select x-model.number="vendedorId"
                                    class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink text-xs">
                                @foreach ($vendedores as $v)
                                    <option value="{{ $v->id }}" @selected($v->id === auth()->id())>{{ $v->name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-[10px] text-gray-400">Esta asignación no se puede cambiar después.</p>
                        </div>
                    @endif

                    <div>
                        <label class="block text-[10px] uppercase tracking-wide text-gray-500 mb-1">Válida hasta</label>
                        <input type="date" x-model="validez"
                               class="w-full rounded-lg border-gray-200 focus:border-sweetgo-pink focus:ring-sweetgo-pink text-xs">
                    </div>

                    <div class="border-t border-gray-100 pt-3 space-y-1 text-sm">
                        <div class="flex justify-between text-gray-500">
                            <span>Subtotal</span>
                            <span x-text="money(subtotal())"></span>
                        </div>
                        {{-- Toggle IVA --}}
                        <label class="flex items-center justify-between text-xs text-gray-600 py-1 cursor-pointer">
                            <span class="flex items-center gap-2">
                                <input type="checkbox" x-model="conIva"
                                       class="rounded border-gray-300 text-sweetgo-pink focus:ring-sweetgo-pink">
                                <span>Aplicar IVA (<span x-text="ivaPorcentaje"></span>%)</span>
                            </span>
                            <span x-show="conIva" x-cloak class="text-gray-500" x-text="'+' + money(montoIva())"></span>
                        </label>
                        <div class="flex justify-between font-bold text-sweetgo-pink text-base pt-1">
                            <span>Total</span>
                            <span x-text="money(total())"></span>
                        </div>
                    </div>

                    <button type="button" @click="enviar()" :disabled="!puedeEnviar() || enviando"
                            class="w-full px-4 py-2.5 rounded-lg bg-sweetgo-pink text-white font-semibold text-sm shadow-sm hover:opacity-90 disabled:bg-gray-200 disabled:text-gray-400 disabled:cursor-not-allowed">
                        <span x-show="!enviando">Generar cotización</span>
                        <span x-show="enviando">Guardando…</span>
                    </button>
                    <p x-show="mensajeError" x-cloak class="text-xs text-red-500" x-text="mensajeError"></p>
                </div>
            </aside>
        </div>

        {{-- MODAL DETALLE DE PRODUCTO --}}
        <div x-show="productoDetalle" x-cloak
             @click="productoDetalle = null"
             @keydown.escape.window="productoDetalle = null"
             class="fixed inset-0 z-[60] bg-gray-900/70 backdrop-blur-sm flex items-center justify-center p-4"
             :class="productoDetalle ? 'opacity-100' : 'opacity-0 pointer-events-none'">
            <div @click.stop x-show="productoDetalle"
                 class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[92vh] overflow-hidden flex flex-col"
                 :class="productoDetalle ? 'scale-100' : 'scale-95'"
                 style="transition: transform .2s">
                <template x-if="productoDetalle">
                    <div class="flex flex-col overflow-hidden">
                        {{-- Header --}}
                        <div class="px-5 py-3 border-b border-sweetgo-pink-light bg-sweetgo-pink-light/40 flex items-center justify-between shrink-0">
                            <div class="flex items-center gap-3 min-w-0">
                                <p class="text-[10px] uppercase tracking-wide text-sweetgo-turquoise font-medium shrink-0"
                                   x-text="productoDetalle.categoria || 'Sin categoría'"></p>
                                <span class="text-gray-300">·</span>
                                <p class="text-xs text-gray-500 truncate" x-text="productoDetalle.referencia ? 'Ref ' + productoDetalle.referencia : ''"></p>
                            </div>
                            <button type="button" @click="productoDetalle = null"
                                    class="w-9 h-9 rounded-lg flex items-center justify-center text-gray-500 hover:text-white hover:bg-sweetgo-pink transition [&_*]:pointer-events-none"
                                    aria-label="Cerrar detalle">
                                <svg pointer-events="none" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path pointer-events="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-0 overflow-y-auto">
                            {{-- Imagen --}}
                            <div class="aspect-square bg-sweetgo-pink-light/30 flex items-center justify-center overflow-hidden">
                                <template x-if="productoDetalle.imagen">
                                    <img :src="productoDetalle.imagen" :alt="productoDetalle.nombre" class="w-full h-full object-contain object-center p-3">
                                </template>
                                <template x-if="!productoDetalle.imagen">
                                    <span class="text-6xl text-sweetgo-pink">&#10022;</span>
                                </template>
                            </div>

                            {{-- Info --}}
                            <div class="p-5 flex flex-col gap-3">
                                <h3 class="font-serif text-xl font-semibold text-gray-800 leading-tight" x-text="productoDetalle.nombre"></h3>

                                <div class="flex items-center gap-2">
                                    <span x-show="productoDetalle.stock > 0"
                                          class="inline-flex items-center gap-1 text-xs font-medium px-2 py-1 rounded bg-sweetgo-turquoise-light text-sweetgo-turquoise border border-sweetgo-turquoise/40">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        <span x-text="productoDetalle.stock + ' disponibles'"></span>
                                    </span>
                                    <span x-show="productoDetalle.stock <= 0"
                                          class="inline-flex items-center text-xs font-medium px-2 py-1 rounded bg-gray-100 text-gray-500 border border-gray-200">Agotado</span>
                                    <span x-show="productoDetalle.stock_maximo" x-cloak
                                          class="text-[10px] text-gray-400"
                                          x-text="'· Máx por cotización: ' + productoDetalle.stock_maximo"></span>
                                </div>

                                <p x-show="productoDetalle.descripcion" x-cloak
                                   class="text-sm text-gray-600 whitespace-pre-line" x-text="productoDetalle.descripcion"></p>

                                {{-- Precio activo destacado (según cliente) --}}
                                <div class="rounded-lg bg-sweetgo-pink-light/60 border border-sweetgo-pink-light px-4 py-3">
                                    <p class="text-[10px] uppercase tracking-wide text-sweetgo-pink font-medium"
                                       x-text="'Precio · ' + (clienteSel?.lista_nombre || 'Lista predeterminada')"></p>
                                    <p class="text-2xl font-bold text-sweetgo-pink" x-text="money(precioEn(productoDetalle))"></p>
                                </div>

                                {{-- Tabla precios por lista (solo si NO tiene variantes) --}}
                                <div x-show="!productoDetalle.tiene_variantes && listasPrecios.length > 1" x-cloak class="rounded-lg border border-gray-100 overflow-hidden">
                                    <div class="px-3 py-2 bg-gray-50 text-[10px] uppercase tracking-wide text-gray-500 font-medium">Precios por lista</div>
                                    <table class="w-full text-sm">
                                        <tbody>
                                            <template x-for="lp in listasPrecios" :key="lp.id">
                                                <tr class="border-t border-gray-100">
                                                    <td class="px-3 py-2 text-gray-600 flex items-center gap-1">
                                                        <span x-text="lp.nombre"></span>
                                                        <span x-show="lp.es_publica" x-cloak class="text-[9px] uppercase bg-sweetgo-turquoise-light text-sweetgo-turquoise px-1 py-0.5 rounded">Pública</span>
                                                        <span x-show="lp.es_predeterminada" x-cloak class="text-[9px] uppercase bg-sweetgo-pink-light text-sweetgo-pink px-1 py-0.5 rounded">Predet.</span>
                                                    </td>
                                                    <td class="px-3 py-2 text-right font-medium"
                                                        :class="(clienteSel?.lista_precio_id === lp.id) ? 'text-sweetgo-pink font-bold' : 'text-gray-700'"
                                                        x-text="money(productoDetalle.precios[lp.id] ?? productoDetalle.precio_base)"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>

                                {{-- Selector de variantes --}}
                                <div x-show="productoDetalle.tiene_variantes" x-cloak class="rounded-lg border border-sweetgo-pink-light overflow-hidden">
                                    <div class="px-3 py-2 bg-sweetgo-pink-light/40 text-[10px] uppercase tracking-wide text-sweetgo-pink font-semibold">Elige una variante</div>
                                    <div class="divide-y divide-gray-100">
                                        <template x-for="v in productoDetalle.variantes" :key="v.id">
                                            <div class="px-3 py-2.5 flex items-center gap-3">
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-gray-800 truncate" x-text="v.nombre"></p>
                                                    <p class="text-[10px] text-sweetgo-turquoise">Ref <span x-text="v.referencia"></span></p>
                                                </div>
                                                <div class="text-right">
                                                    <p class="text-sm font-bold text-sweetgo-pink" x-text="money(precioVariante(productoDetalle, v))"></p>
                                                    <p class="text-[10px]"
                                                       :class="v.stock > 0 ? 'text-sweetgo-turquoise' : 'text-gray-400'"
                                                       x-text="v.stock > 0 ? v.stock + ' disp.' : 'Agotado'"></p>
                                                </div>
                                                <button type="button" @click="agregarVariante(productoDetalle, v)"
                                                        :disabled="v.stock <= 0"
                                                        class="px-3 py-1.5 rounded-lg bg-sweetgo-pink text-white text-xs font-medium hover:opacity-90 disabled:bg-gray-200 disabled:text-gray-400 disabled:cursor-not-allowed inline-flex items-center gap-1 whitespace-nowrap">
                                                    <template x-if="!enCarrito(productoDetalle.id, v.id)">
                                                        <span>+ Agregar</span>
                                                    </template>
                                                    <template x-if="enCarrito(productoDetalle.id, v.id)">
                                                        <span>✓ (<span x-text="cantidadEn(productoDetalle.id, v.id)"></span>)</span>
                                                    </template>
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Footer con acción --}}
                        <div class="px-5 py-3 border-t border-sweetgo-pink-light bg-white flex items-center gap-2 shrink-0">
                            <button type="button" @click="productoDetalle = null"
                                    class="px-4 py-2 rounded-lg border border-gray-200 text-gray-500 text-sm hover:bg-gray-50">Cerrar</button>
                            <button type="button" x-show="!productoDetalle.tiene_variantes"
                                    @click="agregar(productoDetalle); productoDetalle = null"
                                    :disabled="productoDetalle.stock <= 0"
                                    class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-sweetgo-pink text-white text-sm font-semibold hover:opacity-90 disabled:bg-gray-200 disabled:text-gray-400 disabled:cursor-not-allowed">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span x-text="enCarrito(productoDetalle.id) ? 'Sumar otra unidad al carrito' : 'Agregar al carrito'"></span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <script>
        // Fecha local (evita el bug de UTC de toISOString: en Colombia después de las 19:00 daría mañana).
        function hoyLocal() {
            const d = new Date();
            const pad = n => String(n).padStart(2, '0');
            return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
        }

        function cotizador({ clientes, productos, listasPrecios, csrf, postUrl, esAdmin, vendedorIdInicial }) {
            return {
                clientes, productos, listasPrecios, csrf, postUrl,
                puedeEditarPrecio: !!esAdmin,
                esAdmin: !!esAdmin,
                // ID del vendedor asignado (solo lo cambia el admin desde el selector).
                vendedorId: vendedorIdInicial || null,
                productoDetalle: null,
                paso: 1,
                clienteSel: null,

                // Al montar: si viene ?cliente=X en la URL, saltar al paso 2 con ese cliente.
                init() {
                    const params = new URLSearchParams(window.location.search);
                    const cid = parseInt(params.get('cliente'), 10);
                    if (cid) {
                        const c = this.clientes.find(x => x.id === cid);
                        if (c) this.elegirCliente(c);
                    }
                },

                buscarCliente: '',
                filtroLista: null,       // ahora guarda lista_precio_id (no nombre)
                filtroCiudad: null,
                soloConSucursales: false,
                orden: 'nombre',
                buscarProducto: '', cat: null,
                carrito: [],
                carritoAbierto: false,
                anchoVentana: (typeof window !== 'undefined' ? window.innerWidth : 1024),
                notas: '',
                fecha: hoyLocal(),
                validez: '',
                descuento: 0,
                conIva: false,
                ivaPorcentaje: 19,
                enviando: false,
                mensajeError: '',

                money(v) { return '$' + Math.round(Number(v) || 0).toLocaleString('es-CO'); },

                clientesFiltrados() {
                    const b = this.buscarCliente.trim().toLowerCase();
                    let out = this.clientes.filter(c => {
                        if (this.filtroLista && c.lista_precio_id !== this.filtroLista) return false;
                        if (this.filtroCiudad && (c.ciudad || '') !== this.filtroCiudad) return false;
                        if (this.soloConSucursales && !c.sucursales.length) return false;
                        if (b) {
                            const tels = c.telefonos.map(t => t.numero).join(' ');
                            const mails = c.emails.map(e => e.email).join(' ');
                            const sucs = c.sucursales.map(s => (s.nombre || '') + ' ' + (s.direccion || '')).join(' ');
                            const hay = [c.nombre, c.documento, c.telefono, c.email, c.ciudad, tels, mails, sucs]
                                .filter(Boolean).join(' ').toLowerCase();
                            if (!hay.includes(b)) return false;
                        }
                        return true;
                    });
                    const cmp = {
                        'nombre': (a, b) => a.nombre.localeCompare(b.nombre, 'es'),
                        'nombre-desc': (a, b) => b.nombre.localeCompare(a.nombre, 'es'),
                        'lista': (a, b) => (a.lista_nombre || 'zzz').localeCompare(b.lista_nombre || 'zzz', 'es') || a.nombre.localeCompare(b.nombre, 'es'),
                        'sucursales': (a, b) => b.sucursales.length - a.sucursales.length || a.nombre.localeCompare(b.nombre, 'es'),
                    };
                    return out.sort(cmp[this.orden] || cmp['nombre']);
                },

                listasDisponibles() {
                    const map = new Map();
                    this.clientes.forEach(c => {
                        if (!c.lista_precio_id) return;
                        const key = c.lista_precio_id;
                        if (!map.has(key)) map.set(key, { id: key, nombre: c.lista_nombre, count: 0 });
                        map.get(key).count += 1;
                    });
                    return Array.from(map.values()).sort((a, b) => a.nombre.localeCompare(b.nombre, 'es'));
                },

                ciudadesDisponibles() {
                    const map = new Map();
                    this.clientes.forEach(c => {
                        if (!c.ciudad) return;
                        map.set(c.ciudad, (map.get(c.ciudad) || 0) + 1);
                    });
                    return Array.from(map, ([nombre, count]) => ({ nombre, count }))
                        .sort((a, b) => b.count - a.count);
                },

                hayFiltrosActivos() {
                    return this.filtroLista || this.filtroCiudad || this.soloConSucursales || this.buscarCliente.trim();
                },

                limpiarFiltros() {
                    this.filtroLista = null;
                    this.filtroCiudad = null;
                    this.soloConSucursales = false;
                    this.buscarCliente = '';
                },

                elegirCliente(c) {
                    const clienteAnterior = this.clienteSel?.id;
                    this.clienteSel = c;
                    this.paso = 2;
                    // Solo repreciar si CAMBIÓ de cliente. Preservar items que el admin ya negoció manualmente.
                    if (clienteAnterior && clienteAnterior !== c.id) {
                        this.carrito.forEach(item => {
                            const p = this.productos.find(x => x.id === item.producto_id);
                            if (p) item.precio_unitario = this.precioEn(p);
                        });
                    }
                },

                stockDe(productoId) {
                    return this.productos.find(p => p.id === productoId)?.stock ?? 0;
                },

                topearCantidad(item) {
                    const stock = this.stockDe(item.producto_id);
                    if (stock && item.cantidad > stock) item.cantidad = stock;
                    if (item.cantidad < 1 || isNaN(item.cantidad)) item.cantidad = 1;
                },

                volverACliente() {
                    if (this.carrito.length && !confirm('Vas a cambiar de cliente. El carrito se conservará pero los precios se recalculan según la lista del nuevo cliente. ¿Continuar?')) {
                        return;
                    }
                    this.paso = 1;
                },

                precioEn(p) {
                    if (!this.clienteSel) return p.precio_base;
                    const listaId = this.clienteSel.lista_precio_id;
                    if (listaId && p.precios && p.precios[listaId] !== undefined) {
                        return Number(p.precios[listaId]);
                    }
                    return p.precio_base;
                },

                productosFiltrados() {
                    const b = this.buscarProducto.trim().toLowerCase();
                    return this.productos.filter(p => {
                        const okCat = this.cat === null || p.categoria_id === this.cat;
                        const okBuscar = !b || p.nombre.toLowerCase().includes(b) || (p.referencia || '').toLowerCase().includes(b);
                        return okCat && okBuscar;
                    });
                },

                enCarrito(id, varianteId = null) {
                    return this.carrito.some(i => i.producto_id === id && (i.variante_producto_id ?? null) === varianteId);
                },
                cantidadEn(id, varianteId = null) {
                    return this.carrito.find(i => i.producto_id === id && (i.variante_producto_id ?? null) === varianteId)?.cantidad || 0;
                },

                verDetalle(p) {
                    this.productoDetalle = p;
                },

                /** Devuelve el precio de una variante en la lista del cliente actual, con fallback al padre. */
                precioVariante(p, v) {
                    if (!this.clienteSel) return v.precio_padre_base || p.precio_base;
                    const listaId = this.clienteSel.lista_precio_id;
                    if (listaId && v.precios && v.precios[listaId] !== undefined) {
                        return Number(v.precios[listaId]);
                    }
                    return this.precioEn(p);
                },

                agregar(p) {
                    if (p.tiene_variantes) {
                        // Abrimos el mini-modal de variantes; el modal grande de detalle también soporta agregar.
                        this.productoDetalle = p;
                        return;
                    }
                    this._agregarItem({
                        producto_id: p.id,
                        variante_producto_id: null,
                        nombre: p.nombre,
                        referencia: p.referencia,
                        precio_unitario: this.precioEn(p),
                    });
                    this.pulsarBoton();
                },

                agregarVariante(p, v) {
                    this._agregarItem({
                        producto_id: p.id,
                        variante_producto_id: v.id,
                        nombre: p.nombre + ' · ' + v.nombre,
                        referencia: v.referencia,
                        precio_unitario: this.precioVariante(p, v),
                    });
                    this.pulsarBoton();
                },

                _agregarItem(item) {
                    const key = i => i.producto_id === item.producto_id && (i.variante_producto_id ?? null) === (item.variante_producto_id ?? null);
                    const existente = this.carrito.find(key);
                    if (existente) {
                        existente.cantidad += 1;
                    } else {
                        this.carrito.push({...item, cantidad: 1});
                    }
                },

                pulsarBoton() {
                    const btn = document.getElementById('btnCarritoFlotante');
                    if (!btn) return;
                    btn.classList.remove('scale-110');
                    void btn.offsetWidth;
                    btn.classList.add('scale-110');
                    setTimeout(() => btn.classList.remove('scale-110'), 200);
                },

                subtotal() {
                    return this.carrito.reduce((s, i) => {
                        const cant = Number(i.cantidad) || 0;
                        const pu = Number(i.precio_unitario) || 0;
                        return s + (cant * pu);
                    }, 0);
                },
                descuentoAplicado() {
                    return Math.max(0, Math.min(Number(this.descuento) || 0, this.subtotal()));
                },
                baseImponible() {
                    return Math.max(0, this.subtotal() - this.descuentoAplicado());
                },
                montoIva() {
                    if (!this.conIva) return 0;
                    return Math.round(this.baseImponible() * (Number(this.ivaPorcentaje) / 100));
                },
                total() {
                    return this.baseImponible() + this.montoIva();
                },

                puedeEnviar() {
                    if (!this.clienteSel || !this.carrito.length || !this.fecha) return false;
                    // Rechazar cantidades > stock (para productos con stock definido)
                    return !this.carrito.some(i => {
                        const s = this.stockDe(i.producto_id);
                        return s > 0 && i.cantidad > s;
                    });
                },

                async enviar() {
                    if (!this.puedeEnviar() || this.enviando) return;
                    if ((Number(this.descuento) || 0) > this.subtotal()) {
                        this.mensajeError = 'El descuento no puede superar el subtotal.';
                        return;
                    }
                    this.enviando = true;
                    this.mensajeError = '';

                    const fd = new FormData();
                    fd.append('_token', this.csrf);
                    fd.append('cliente_id', this.clienteSel.id);
                    // Solo admin manda user_id (vendedor); si es vendedor lo ignora el backend.
                    if (this.esAdmin && this.vendedorId) fd.append('user_id', this.vendedorId);
                    fd.append('fecha', this.fecha);
                    if (this.validez) fd.append('validez', this.validez);
                    fd.append('descuento', Math.max(0, Number(this.descuento) || 0));
                    fd.append('con_iva', this.conIva ? '1' : '0');
                    fd.append('iva_porcentaje', this.ivaPorcentaje);
                    if (this.notas) fd.append('notas', this.notas);
                    this.carrito.forEach((i, idx) => {
                        fd.append(`items[${idx}][producto_id]`, i.producto_id);
                        if (i.variante_producto_id) {
                            fd.append(`items[${idx}][variante_producto_id]`, i.variante_producto_id);
                        }
                        fd.append(`items[${idx}][cantidad]`, Math.max(1, Number(i.cantidad) || 1));
                        fd.append(`items[${idx}][precio_unitario]`, Math.max(0, Number(i.precio_unitario) || 0));
                    });

                    try {
                        const res = await fetch(this.postUrl, {
                            method: 'POST',
                            body: fd,
                            headers: { 'Accept': 'application/json, text/html', 'X-Requested-With': 'XMLHttpRequest' },
                            redirect: 'follow',
                        });
                        if (res.redirected) {
                            window.location.href = res.url;
                        } else if (res.status === 422) {
                            // Errores de validación: extraerlos del JSON de Laravel
                            const body = await res.json().catch(() => ({}));
                            const errores = body?.errors ? Object.values(body.errors).flat().join(' · ') : (body?.message || 'Datos inválidos');
                            this.mensajeError = errores;
                        } else if (res.ok) {
                            window.location.reload();
                        } else {
                            this.mensajeError = 'No se pudo guardar la cotización (código ' + res.status + '). Revisa los campos.';
                        }
                    } catch (e) {
                        this.mensajeError = 'Error de red: ' + e.message;
                    } finally {
                        this.enviando = false;
                    }
                },
            };
        }
    </script>
@endsection
