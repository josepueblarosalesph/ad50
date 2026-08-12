{{--
    Panel de carpetas de favoritos. Se incluye dos veces (barra lateral en escritorio y
    desplegable en móvil), así que todo id o wire:key lleva $prefijo para no repetirse.

    Las carpetas son de cada usuario del equipo, no de la empresa: lo que se ve aquí es
    la organización propia sobre los favoritos compartidos de la cuenta.
--}}
@php($carpetaActiva = $carpeta)

<div class="ad-card p-3">
    <div class="mb-2 flex items-center gap-2 px-1.5">
        <flux:icon.folder class="size-4 flex-none text-orange-500" />
        <h2 class="text-[11px] font-bold uppercase tracking-[.12em] text-gray-400">Mis carpetas</h2>
        {{-- Crear vive en un popup y no en un formulario fijo al pie: se usa de vez en
             cuando y ocupaba sitio permanente por debajo de la lista. --}}
        <button
            type="button"
            wire:click="abrirNuevaCarpeta"
            class="ms-auto grid size-6 flex-none place-items-center rounded-lg border border-line-2 text-gray-400 transition hover:border-orange-300 hover:text-orange-600 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-orange-500"
            aria-label="Crear una carpeta nueva"
            title="Crear una carpeta nueva"
        >
            <flux:icon.plus class="size-3.5" />
        </button>
    </div>

    <div class="space-y-1">
        {{-- Las dos vistas que siempre existen: todo y lo que aún no se ha agrupado. --}}
        @foreach ([['todas', 'Todos los favoritos', $totalFavoritos], ['sin', 'Sin carpeta', $sinCarpeta]] as [$clave, $etiqueta, $total])
            <button
                type="button"
                wire:key="{{ $prefijo }}-carpeta-{{ $clave }}"
                wire:click="verCarpeta('{{ $clave }}')"
                @class([
                    'flex w-full items-center gap-2 rounded-[10px] border px-2.5 py-2 text-left text-[13.5px] font-bold transition',
                    'border-orange-300 bg-orange-100 text-orange-700 dark:border-orange-500 dark:bg-[#33251D] dark:text-[#F7C59E]' => $carpetaActiva === $clave,
                    'border-transparent text-gray-600 hover:bg-paper dark:text-gray-300 dark:hover:bg-white/5' => $carpetaActiva !== $clave,
                ])
                @if ($carpetaActiva === $clave) aria-current="true" @endif
            >
                <flux:icon name="{{ $clave === 'todas' ? 'star' : 'inbox' }}" class="size-4 flex-none" />
                <span class="min-w-0 flex-1 truncate">{{ $etiqueta }}</span>
                <span class="flex-none text-[12px] font-bold tabular-nums text-gray-400">{{ $total }}</span>
            </button>
        @endforeach
    </div>

    {{-- Las carpetas propias van plegadas por defecto: el panel comparte la barra lateral
         con los criterios de perfil, y una lista larga los empujaría fuera de la vista.
         Se abre solo si la carpeta activa es una de ellas, para no esconder dónde estás. --}}
    @if ($carpetas->isNotEmpty())
        <div
            class="mt-2 border-t border-line pt-2"
            x-data="{ abierto: @js(! in_array($carpetaActiva, ['todas', 'sin'], true)) }"
        >
            <button
                type="button"
                x-on:click="abierto = ! abierto"
                x-bind:aria-expanded="abierto ? 'true' : 'false'"
                aria-controls="{{ $prefijo }}-lista-carpetas"
                class="flex w-full items-center gap-2 rounded-[10px] px-2.5 py-1.5 text-left text-[12px] font-bold text-gray-500 transition hover:bg-paper focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-orange-500 dark:hover:bg-white/5"
            >
                <flux:icon.chevron-right class="size-3.5 flex-none transition" x-bind:class="abierto && 'rotate-90'" />
                <span class="min-w-0 flex-1 truncate">{{ $carpetas->count() }} {{ $carpetas->count() === 1 ? 'carpeta' : 'carpetas' }}</span>
            </button>

            <div id="{{ $prefijo }}-lista-carpetas" class="mt-1 space-y-1" x-show="abierto" x-cloak>

            @foreach ($carpetas as $unaCarpeta)
                @if ($carpetaEnEdicionId === $unaCarpeta->id)
                    {{-- Renombrar: reemplaza la fila para no abrir un modal por algo de un campo. --}}
                    <form wire:key="{{ $prefijo }}-editando-{{ $unaCarpeta->id }}" wire:submit="renombrarCarpeta" class="rounded-[10px] border border-orange-300 bg-orange-50 p-2 dark:bg-[#33251D]">
                        <input
                            type="text"
                            wire:model="nombreEnEdicion"
                            maxlength="40"
                            autofocus
                            aria-label="Nuevo nombre de la carpeta"
                            class="w-full rounded-lg border border-line-2 bg-white px-2.5 py-1.5 text-[13px] font-semibold text-ink focus:border-orange-400 focus:outline-none dark:bg-[#222528]"
                        />
                        @error('nombreEnEdicion')<p class="mt-1 text-[11.5px] font-semibold text-[#A93226]">{{ $message }}</p>@enderror
                        <div class="mt-2 flex justify-end gap-1.5">
                            <button type="button" wire:click="cancelarEdicionCarpeta" class="rounded-lg px-2 py-1 text-[12px] font-bold text-gray-500 hover:text-ink">Cancelar</button>
                            <button type="submit" class="ad-btn-primary ad-btn-sm" wire:loading.attr="disabled" wire:target="renombrarCarpeta">Guardar</button>
                        </div>
                    </form>
                @else
                    <div wire:key="{{ $prefijo }}-carpeta-{{ $unaCarpeta->id }}" class="group flex items-center gap-0.5">
                        <button
                            type="button"
                            wire:click="verCarpeta('{{ $unaCarpeta->id }}')"
                            @class([
                                'flex min-w-0 flex-1 items-center gap-2 rounded-[10px] border px-2.5 py-2 text-left text-[13.5px] font-bold transition',
                                'border-orange-300 bg-orange-100 text-orange-700 dark:border-orange-500 dark:bg-[#33251D] dark:text-[#F7C59E]' => $carpetaActiva === (string) $unaCarpeta->id,
                                'border-transparent text-gray-600 hover:bg-paper dark:text-gray-300 dark:hover:bg-white/5' => $carpetaActiva !== (string) $unaCarpeta->id,
                            ])
                            @if ($carpetaActiva === (string) $unaCarpeta->id) aria-current="true" @endif
                        >
                            <flux:icon name="{{ $carpetaActiva === (string) $unaCarpeta->id ? 'folder-open' : 'folder' }}" class="size-4 flex-none" />
                            <span class="min-w-0 flex-1 truncate">{{ $unaCarpeta->nombre }}</span>
                            <span class="flex-none text-[12px] font-bold tabular-nums text-gray-400">{{ $unaCarpeta->favoritos_count }}</span>
                        </button>

                        {{-- Siempre en el DOM (no solo en hover) para que lleguen por teclado. --}}
                        <button type="button" wire:click="editarCarpeta({{ $unaCarpeta->id }})" class="grid size-7 flex-none place-items-center rounded-lg text-gray-400 transition hover:bg-paper hover:text-orange-600 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-orange-500 dark:hover:bg-white/5" aria-label="Renombrar carpeta {{ $unaCarpeta->nombre }}">
                            <flux:icon.pencil class="size-3.5" />
                        </button>
                        <button
                            type="button"
                            wire:click="eliminarCarpeta({{ $unaCarpeta->id }})"
                            wire:confirm="Se eliminará la carpeta «{{ $unaCarpeta->nombre }}». Los candidatos seguirán en tus favoritos."
                            wire:loading.attr="disabled"
                            wire:target="eliminarCarpeta({{ $unaCarpeta->id }})"
                            class="grid size-7 flex-none place-items-center rounded-lg text-gray-400 transition hover:bg-paper hover:text-[#A93226] focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-orange-500 disabled:opacity-50 dark:hover:bg-white/5"
                            aria-label="Eliminar carpeta {{ $unaCarpeta->nombre }}"
                        >
                            <flux:icon.trash class="size-3.5" />
                        </button>
                    </div>
                @endif
                @endforeach
            </div>
        </div>
    @endif
</div>
