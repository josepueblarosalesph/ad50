@props([
    'carpetas',
    'asignadas' => [],
])

{{-- Panel para agrupar un candidato guardado en una o más carpetas propias.

     A diferencia de "Asociar a publicaciones", aquí la lista va a la vista y no dentro
     de un desplegable: las carpetas son pocas por diseño (tope por usuario) y lo que se
     necesita es ver de un vistazo en cuáles está y en cuáles no. --}}
<flux:modal name="organizar-carpetas" class="max-w-md" wire:close="cerrarCarpetas">
    <div class="space-y-4">
        <div>
            <flux:heading size="lg">Agrupar en carpetas</flux:heading>
            <flux:text class="mt-1.5">
                Un mismo candidato puede estar en varias carpetas. Sacarlo de todas no lo quita de
                tus favoritos, y tus carpetas solo las ves tú.
            </flux:text>
        </div>

        @if ($carpetas->isEmpty())
            <div class="rounded-xl border border-line-2 bg-paper px-4 py-6 text-center">
                <flux:icon.folder class="mx-auto size-7 text-gray-400" />
                <p class="mt-2 text-[13.5px] font-semibold text-ink">Todavía no tienes carpetas</p>
                <p class="mt-1 text-[12.5px] text-gray-500">Créala aquí abajo y podrás agrupar este candidato de inmediato.</p>
            </div>
        @else
            <ul class="max-h-64 space-y-1 overflow-y-auto">
                @foreach ($carpetas as $carpetaDisponible)
                    @php($asignada = in_array($carpetaDisponible->id, $asignadas, true))
                    <li wire:key="asignar-carpeta-{{ $carpetaDisponible->id }}">
                        <label @class([
                            'flex cursor-pointer items-center gap-2.5 rounded-[10px] border px-3 py-2.5 transition',
                            'border-orange-300 bg-orange-100 dark:border-orange-500 dark:bg-[#33251D]' => $asignada,
                            'border-line-2 bg-white hover:border-orange-200 dark:bg-[#222528]' => ! $asignada,
                        ])>
                            <input
                                type="checkbox"
                                wire:click="alternarCarpeta({{ $carpetaDisponible->id }})"
                                wire:loading.attr="disabled"
                                wire:target="alternarCarpeta({{ $carpetaDisponible->id }})"
                                @checked($asignada)
                                class="size-4 flex-none rounded border-line-2 accent-orange-600"
                            />
                            <span class="min-w-0 flex-1 truncate text-[13.5px] font-bold text-ink">{{ $carpetaDisponible->nombre }}</span>
                            <span class="flex-none text-[12px] font-bold tabular-nums text-gray-400">{{ $carpetaDisponible->favoritos_count }}</span>
                        </label>
                    </li>
                @endforeach
            </ul>
        @endif

        {{-- Crear sin salir del panel: al volver de una búsqueda es habitual descubrir que
             falta la carpeta justo cuando se está guardando al candidato. --}}
        <form wire:submit="crearCarpeta" class="border-t border-line pt-4">
            <label for="carpeta-desde-modal" class="mb-1.5 block text-[12px] font-bold text-gray-600 dark:text-gray-300">Crear una carpeta nueva</label>
            <div class="flex gap-2">
                <input
                    type="text"
                    id="carpeta-desde-modal"
                    wire:model="nuevaCarpeta"
                    maxlength="40"
                    placeholder="Ej. Proceso Gerente TI"
                    class="min-w-0 flex-1 rounded-lg border border-line-2 bg-white px-3 py-2 text-[13.5px] font-semibold text-ink placeholder:font-normal placeholder:text-gray-400 focus:border-orange-400 focus:outline-none dark:bg-[#222528]"
                />
                <flux:button type="submit" variant="ghost" wire:loading.attr="disabled" wire:target="crearCarpeta">Crear</flux:button>
            </div>
            @error('nuevaCarpeta')<p class="mt-1.5 text-[12px] font-semibold text-[#A93226]">{{ $message }}</p>@enderror
        </form>

        <div class="flex justify-end">
            <flux:button wire:click="cerrarCarpetas" variant="primary">Listo</flux:button>
        </div>
    </div>
</flux:modal>
