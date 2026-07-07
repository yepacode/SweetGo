@props([
    'name' => 'archivo',
    'accept' => 'image/*',
    'multiple' => false,
    'label' => 'Elegir archivo',
    'hint' => null,
    'required' => false,
])

<div x-data="{ nombre: '{{ $multiple ? 'Ningún archivo seleccionado' : 'Ninguno seleccionado' }}', tamano: null }" class="w-full">
    <label class="inline-flex items-center gap-3 cursor-pointer">
        <span class="px-4 py-2 rounded-lg bg-sweetgo-pink text-white text-sm font-medium hover:opacity-90 whitespace-nowrap">{{ $label }}</span>
        <input type="file"
               name="{{ $name }}{{ $multiple ? '[]' : '' }}"
               accept="{{ $accept }}"
               {{ $multiple ? 'multiple' : '' }}
               {{ $required ? 'required' : '' }}
               class="sr-only"
               @change="
                   const files = $event.target.files;
                   if (!files || !files.length) { nombre = '{{ $multiple ? 'Ningún archivo seleccionado' : 'Ninguno seleccionado' }}'; tamano = null; return; }
                   if (files.length === 1) { nombre = files[0].name; tamano = Math.round(files[0].size/1024); }
                   else { nombre = files.length + ' archivos seleccionados'; tamano = null; }
               ">
        <span class="text-sm text-gray-500 truncate max-w-[16rem]" x-text="nombre"></span>
        <span class="text-xs text-gray-400" x-show="tamano" x-cloak x-text="'· ' + tamano + ' KB'"></span>
    </label>
    @if ($hint)
        <p class="mt-1 text-xs text-gray-400">{{ $hint }}</p>
    @endif
</div>
