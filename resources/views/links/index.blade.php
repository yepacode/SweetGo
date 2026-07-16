@extends('layouts.admin')

@section('title', 'Links')

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-gray-800">Links del catálogo</h2>
        <p class="text-sm text-gray-500">Genera enlaces compartibles del catálogo por WhatsApp o redes. Cada link es un catálogo público, sin login.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Crear enlace --}}
        <div class="bg-white rounded-xl border border-sweetgo-pink-light p-6 h-fit">
            <h3 class="font-semibold text-gray-700 mb-1">Nuevo enlace</h3>
            <p class="text-xs text-gray-400 mb-4">{{ $totalProductos }} productos activos se mostrarán.</p>
            <form method="POST" action="{{ route('links.enlaces.crear') }}" class="space-y-3">
                @csrf
                <input type="text" name="titulo" placeholder="Título (ej. Público, Mayoristas)"
                       class="w-full rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
                <button class="w-full px-4 py-2 rounded-lg bg-sweetgo-pink text-white text-sm font-medium hover:opacity-90">Generar enlace</button>
            </form>
        </div>

        {{-- Lista de enlaces --}}
        <div class="lg:col-span-2 space-y-4">
            @forelse ($enlaces as $enlace)
                <div x-data="{ copiado: false }" class="bg-white rounded-xl border border-sweetgo-pink-light p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-gray-800">{{ $enlace->titulo }}</span>
                                @if ($enlace->activo)
                                    <span class="inline-block px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-xs">Activo</span>
                                @else
                                    <span class="inline-block px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 text-xs">Inactivo</span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-400 mt-1">{{ $enlace->visitas }} visitas @if($enlace->ultima_visita) · última {{ $enlace->ultima_visita->diffForHumans() }} @endif</p>
                            <code class="inline-block mt-2 text-xs bg-gray-50 border border-gray-100 rounded px-2 py-1 text-gray-600 break-all" x-ref="url">{{ $enlace->url }}</code>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 mt-4">
                        <a href="{{ $enlace->url }}" target="_blank"
                           class="px-3 py-1.5 rounded-lg border border-sweetgo-turquoise text-sweetgo-turquoise text-xs font-medium hover:bg-sweetgo-turquoise-light">Ver catálogo</a>

                        <button @click="navigator.clipboard.writeText($refs.url.textContent.trim()); copiado = true; setTimeout(() => copiado = false, 1500)"
                                class="px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 text-xs font-medium hover:bg-gray-50">
                            <span x-show="!copiado">Copiar enlace</span>
                            <span x-show="copiado" x-cloak class="text-green-600">¡Copiado!</span>
                        </button>

                        <a href="https://wa.me/?text={{ urlencode('¡Mira nuestro catálogo Sweet Go! 💖 '.$enlace->url) }}" target="_blank"
                           class="px-3 py-1.5 rounded-lg bg-green-500 text-white text-xs font-medium hover:opacity-90">Compartir por WhatsApp</a>

                        <div class="ml-auto flex items-center gap-2">
                            <form method="POST" action="{{ route('links.enlaces.toggle', $enlace) }}">
                                @csrf @method('PATCH')
                                <button class="px-3 py-1.5 rounded-lg text-xs text-gray-500 hover:bg-gray-50">{{ $enlace->activo ? 'Desactivar' : 'Activar' }}</button>
                            </form>
                            <form method="POST" action="{{ route('links.enlaces.eliminar', $enlace) }}" onsubmit="return confirm('¿Eliminar este enlace? Dejará de funcionar.')">
                                @csrf @method('DELETE')
                                <button class="px-3 py-1.5 rounded-lg text-xs text-red-400 hover:text-red-600">Eliminar</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-xl border border-dashed border-sweetgo-pink-light p-10 text-center text-gray-400">
                    Aún no hay enlaces. Crea el primero para compartir tu catálogo.
                </div>
            @endforelse
        </div>
    </div>
@endsection
