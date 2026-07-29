@props([
    'publicaciones',
    'asociadas' => [],
])

{{-- Panel para vincular un candidato de Prospección de Candidatos a una o más publicaciones. --}}
<flux:modal name="asociar-publicaciones" class="max-w-lg" wire:close="cerrarAsociacion">
    <div class="space-y-5">
        <div>
            <flux:heading size="lg">Asociar a publicaciones</flux:heading>
            <flux:text class="mt-2">
                Vincula este candidato a las publicaciones donde lo estás considerando. Es opcional,
                puedes elegir más de una y no consume desbloqueos ni cupo de tu plan.
            </flux:text>
        </div>

        <div class="max-h-80 space-y-2 overflow-y-auto">
            @forelse ($publicaciones as $publicacion)
                @php($asociada = in_array($publicacion->id, $asociadas, true))
                <button
                    type="button"
                    wire:key="asociar-{{ $publicacion->id }}"
                    wire:click="toggleAsociacion({{ $publicacion->id }})"
                    wire:loading.attr="disabled"
                    wire:target="toggleAsociacion({{ $publicacion->id }})"
                    @class([
                        'flex w-full items-center gap-3 rounded-xl border px-4 py-3 text-left transition disabled:opacity-50',
                        'border-orange-300 bg-orange-100 dark:bg-orange-100/10' => $asociada,
                        'border-line-2 bg-white hover:border-orange-300 dark:bg-[#2A2D30]' => ! $asociada,
                    ])
                    aria-pressed="{{ $asociada ? 'true' : 'false' }}"
                >
                    <span @class([
                        'grid size-6 flex-none place-items-center rounded-md border transition',
                        'border-orange-500 bg-orange-600 text-white' => $asociada,
                        'border-line-2 text-transparent' => ! $asociada,
                    ])>
                        <flux:icon.check class="size-4" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-[14px] font-bold text-ink">{{ $publicacion->cargo }}</span>
                        <span class="block truncate text-[12px] text-gray-500">
                            {{ $publicacion->comuna }} · {{ $publicacion->estadoLabel() }}
                        </span>
                    </span>
                </button>
            @empty
                <div class="rounded-xl border border-line-2 bg-paper px-4 py-6 text-center">
                    <flux:icon.megaphone class="mx-auto size-7 text-gray-400" />
                    <p class="mt-2 text-[13.5px] font-semibold text-ink">No tienes publicaciones disponibles</p>
                    <p class="mt-1 text-[12.5px] text-gray-500">
                        Crea una publicación para poder asociarle candidatos.
                    </p>
                    <a wire:navigate href="{{ route('empresa.publicaciones.index') }}" class="mt-3 inline-block text-[13px] font-bold text-orange-600 underline underline-offset-2">
                        Ir a Publicaciones
                    </a>
                </div>
            @endforelse
        </div>

        <div class="flex justify-end">
            <flux:button wire:click="cerrarAsociacion" variant="primary">Listo</flux:button>
        </div>
    </div>
</flux:modal>
