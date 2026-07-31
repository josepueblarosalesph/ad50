@props([
    'publicaciones',
    'asociadas' => [],
])

@php
    // El buscador filtra en el cliente sobre este mismo texto, así que se arma una sola vez.
    $etiquetas = $publicaciones->mapWithKeys(fn ($publicacion) => [
        $publicacion->id => $publicacion->cargo.' '.$publicacion->comuna.' '.$publicacion->estadoLabel(),
    ]);
@endphp

{{-- Panel para vincular un candidato de Prospección de Candidatos a una o más publicaciones.
     La selección va en un desplegable con casillas: ocupa una línea y no crece con el
     número de publicaciones que tenga la empresa. --}}
<flux:modal name="asociar-publicaciones" class="max-w-md" wire:close="cerrarAsociacion">
    <div class="space-y-4">
        <div>
            <flux:heading size="lg">Asociar a publicaciones</flux:heading>
            <flux:text class="mt-1.5">
                Vincula este candidato a las publicaciones donde lo estás considerando. Puedes elegir
                más de una y no consume desbloqueos ni cupo de tu plan.
            </flux:text>
        </div>

        @if ($publicaciones->isEmpty())
            <div class="rounded-xl border border-line-2 bg-paper px-4 py-6 text-center">
                <flux:icon.megaphone class="mx-auto size-7 text-gray-400" />
                <p class="mt-2 text-[13.5px] font-semibold text-ink">No tienes publicaciones disponibles</p>
                <p class="mt-1 text-[12.5px] text-gray-500">Crea una publicación para poder asociarle candidatos.</p>
                <a wire:navigate href="{{ route('empresa.publicaciones.index') }}" class="mt-3 inline-block text-[13px] font-bold text-orange-600 underline underline-offset-2">
                    Ir a Publicaciones
                </a>
            </div>
        @else
            {{-- wire:ignore.self: al marcar una casilla el morph reescribiría x-data, Alpine
                 reiniciaría el scope y el desplegable se cerraría solo en cada clic. --}}
            <div
                wire:ignore.self
                class="relative"
                x-data="{
                    abierto: false,
                    consulta: '',
                    etiquetas: @js($etiquetas->values()),
                    normalizar(texto) {
                        return texto.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase().trim()
                    },
                    coincide(texto) {
                        return this.consulta === '' || this.normalizar(texto).includes(this.normalizar(this.consulta))
                    },
                    get sinCoincidencias() {
                        return ! this.etiquetas.some((etiqueta) => this.coincide(etiqueta))
                    },
                }"
                x-on:click.outside="abierto = false"
                x-on:keydown.escape.stop="abierto = false"
            >
                <button
                    type="button"
                    x-on:click="abierto = ! abierto"
                    x-bind:aria-expanded="abierto"
                    class="flex w-full items-center justify-between gap-2 rounded-lg border border-line-2 bg-white px-3 py-2.5 text-left text-[14px] font-semibold text-ink transition hover:border-orange-300 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500 dark:bg-[#222528]"
                >
                    <span class="truncate">
                        @if ($asociadas === [])
                            <span class="font-normal text-gray-400">Elige una o más publicaciones</span>
                        @else
                            {{ count($asociadas) }} {{ count($asociadas) === 1 ? 'publicación seleccionada' : 'publicaciones seleccionadas' }}
                        @endif
                    </span>
                    <flux:icon.chevron-down class="size-4 flex-none text-gray-400 transition" x-bind:class="abierto && 'rotate-180'" />
                </button>

                <div
                    x-show="abierto"
                    x-cloak
                    class="absolute inset-x-0 z-30 mt-1 overflow-hidden rounded-xl border border-line-2 bg-white shadow-xl dark:border-[#5A5F64] dark:bg-[#222528]"
                >
                    <div class="border-b border-line p-2">
                        <input
                            type="text"
                            x-model="consulta"
                            placeholder="Buscar publicación"
                            autocomplete="off"
                            aria-label="Buscar publicación"
                            class="w-full rounded-lg border border-line-2 bg-white px-3 py-1.5 text-[13px] text-ink placeholder:text-gray-400 focus:border-orange-400 focus:outline-none dark:bg-[#1D2022]"
                        />
                    </div>

                    <ul class="max-h-56 overflow-y-auto py-1">
                        @foreach ($publicaciones as $publicacion)
                            @php($asociada = in_array($publicacion->id, $asociadas, true))
                            <li wire:key="asociar-{{ $publicacion->id }}" x-show="coincide(@js($etiquetas[$publicacion->id]))">
                                <label class="flex cursor-pointer items-start gap-2.5 px-3 py-2 transition hover:bg-paper dark:hover:bg-white/5">
                                    <input
                                        type="checkbox"
                                        wire:click="toggleAsociacion({{ $publicacion->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="toggleAsociacion({{ $publicacion->id }})"
                                        @checked($asociada)
                                        class="mt-0.5 size-4 flex-none rounded border-line-2 accent-orange-600"
                                    />
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-[13.5px] font-bold text-ink">{{ $publicacion->cargo }}</span>
                                        <span class="block truncate text-[12px] text-gray-500">{{ $publicacion->comuna }} · {{ $publicacion->estadoLabel() }}</span>
                                    </span>
                                </label>
                            </li>
                        @endforeach
                        <li x-show="sinCoincidencias" x-cloak class="px-3 py-2 text-[13px] text-gray-500">Sin coincidencias.</li>
                    </ul>
                </div>
            </div>

            {{-- Lo elegido queda a la vista sin abrir el desplegable; cada ficha lo desasocia. --}}
            @if ($asociadas !== [])
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($publicaciones->whereIn('id', $asociadas) as $publicacion)
                        <button
                            type="button"
                            wire:key="chip-asociada-{{ $publicacion->id }}"
                            wire:click="toggleAsociacion({{ $publicacion->id }})"
                            wire:loading.attr="disabled"
                            wire:target="toggleAsociacion({{ $publicacion->id }})"
                            class="inline-flex max-w-full items-center gap-1.5 rounded-full border border-orange-300 bg-orange-100 px-3 py-1 text-[12.5px] font-bold text-orange-700 transition hover:bg-orange-200 disabled:opacity-50"
                            aria-label="Quitar {{ $publicacion->cargo }}"
                        >
                            <span class="truncate">{{ $publicacion->cargo }}</span>
                            <flux:icon.x-mark class="size-3.5 flex-none" />
                        </button>
                    @endforeach
                </div>
            @endif
        @endif

        <div class="flex justify-end">
            <flux:button wire:click="cerrarAsociacion" variant="primary">Listo</flux:button>
        </div>
    </div>
</flux:modal>
