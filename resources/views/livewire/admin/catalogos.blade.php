<div class="ad-panel">
    <x-slot:context>Administración</x-slot:context>
    <x-slot:status>Acceso interno</x-slot:status>
    <x-slot:nav><x-nav-admin activo="catalogos" /></x-slot:nav>

    @if (session('status'))
        <div class="mb-5 rounded-xl border border-[#BFE6CD] bg-match-100 px-4 py-3 text-[13px] font-bold text-match">{{ session('status') }}</div>
    @endif

    <div class="mb-6">
        <h1 class="text-[27px] font-extrabold">Catálogos</h1>
        <p class="mt-1.5 text-[14px] text-gray-500">
            Administra los valores que ofrecen los formularios y el motor de calce.
            Un término que ya está en uso no se puede editar ni eliminar.
        </p>
    </div>

    <section class="ad-card mb-5 p-4 md:p-5">
        <div class="grid gap-4 md:grid-cols-[1fr_1fr_auto] md:items-end">
            <div>
                <label for="catalogo" class="mb-1.5 block text-[12px] font-bold text-gray-600 dark:text-gray-300">Catálogo</label>
                <select id="catalogo" wire:model.live="catalogo" class="w-full rounded-lg border border-line-2 bg-white px-3 py-2 text-[13.5px] font-semibold text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500 dark:bg-[#2A2D30]">
                    @foreach ($catalogos as $clave => $etiqueta)
                        <option value="{{ $clave }}">{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>

            <flux:input wire:model.live.debounce.300ms="buscar" label="Buscar término" placeholder="Escribe para filtrar" icon="magnifying-glass" />

            <button type="button" wire:click="abrirNuevo" class="ad-btn-primary ad-btn-sm justify-center whitespace-nowrap">
                <flux:icon.plus class="size-4" />Agregar término
            </button>
        </div>

        <p class="mt-3 border-t border-line pt-3 text-[13px] text-gray-500">
            <b class="text-ink">{{ $definicion['etiqueta'] }}</b> · {{ $totalTerminos }}
            {{ $totalTerminos === 1 ? 'término' : 'términos' }}
            @if ($buscar !== '') · {{ $terminos->total() }} coinciden con la búsqueda @endif
        </p>
    </section>

    <section class="ad-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-[14px]">
                <thead><tr class="ad-thead-row"><th class="p-4">Término</th><th class="p-4">Agregado</th><th class="p-4"></th></tr></thead>
                <tbody>
                    @forelse ($terminos as $termino)
                        <tr wire:key="termino-{{ $termino->id }}" class="border-b border-line last:border-0">
                            <td class="p-4 font-semibold text-ink">{{ $termino->valor }}</td>
                            <td class="p-4 text-gray-600">{{ $termino->created_at?->translatedFormat('d M Y') ?? '—' }}</td>
                            <td class="p-4 text-right">
                                <div class="flex justify-end gap-4">
                                    <button type="button" wire:click="abrirEdicion({{ $termino->id }})" class="font-bold text-gray-500 hover:text-ink">Editar</button>
                                    <button type="button" wire:click="confirmarBorrado({{ $termino->id }})" class="font-bold text-[#A93226] hover:text-red-700 dark:text-red-400">Eliminar</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="p-10 text-center">
                            <flux:icon.rectangle-stack class="mx-auto size-8 text-gray-400" />
                            <h2 class="mt-3 font-bold">{{ $buscar !== '' ? 'Ningún término coincide' : 'Este catálogo está vacío' }}</h2>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($terminos->hasPages())
        <div class="mt-6">{{ $terminos->links() }}</div>
    @endif

    {{-- Alta y edición. Con el término en uso, el formulario queda solo de lectura. --}}
    <flux:modal name="termino" class="max-w-lg" wire:close="cerrarFormularios">
        <form wire:submit="guardar" class="space-y-5">
            <flux:heading size="lg">{{ $editandoId ? 'Editar término' : 'Agregar término' }}</flux:heading>

            @if ($bloqueo !== '')
                <div class="rounded-xl border border-[#F5C6C0] bg-[#FDECEA] px-4 py-3 text-[13px] font-bold text-[#A93226] dark:bg-[#3A2523]">
                    {{ $bloqueo }}
                </div>
                <flux:text>Para cambiarlo, primero hay que dejar de usarlo en esos registros.</flux:text>
            @endif

            <flux:input wire:model="valor" label="Término" :disabled="$bloqueo !== ''" autocomplete="off" />
            @error('valor')<flux:text class="text-[#A93226] dark:text-red-400">{{ $message }}</flux:text>@enderror

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="ghost" type="button">Cancelar</flux:button></flux:modal.close>
                @if ($bloqueo === '')
                    <flux:button variant="primary" type="submit" wire:loading.attr="disabled" wire:target="guardar">Guardar</flux:button>
                @endif
            </div>
        </form>
    </flux:modal>

    {{-- Confirmación de borrado: en uso lo impide; sin uso pide confirmar igualmente. --}}
    <flux:modal name="borrar-termino" class="max-w-lg" wire:close="cerrarFormularios">
        <div class="space-y-4">
            <div class="flex items-start gap-3">
                <span class="grid size-10 flex-none place-items-center rounded-xl bg-red-100 text-[#A93226] dark:bg-red-950/40 dark:text-red-400"><flux:icon.trash class="size-5" /></span>
                <div class="min-w-0">
                    <flux:heading size="lg">Eliminar término</flux:heading>
                    <flux:text class="mt-1 truncate">«{{ $borrandoValor }}»</flux:text>
                </div>
            </div>

            @if ($bloqueo !== '')
                <div class="rounded-xl border border-[#F5C6C0] bg-[#FDECEA] px-4 py-3 text-[13px] font-bold text-[#A93226] dark:bg-[#3A2523]">
                    {{ $bloqueo }}
                </div>
                <flux:text>Si lo eliminas, esos registros quedarían apuntando a un valor que ya no existe.</flux:text>
                <div class="flex justify-end pt-2">
                    <flux:modal.close><flux:button variant="ghost">Entendido</flux:button></flux:modal.close>
                </div>
            @else
                <flux:text>
                    Este término no está siendo usado por ninguna ficha, publicación ni búsqueda.
                    <b class="text-ink">¿Estás seguro de que deseas eliminarlo?</b>
                </flux:text>
                <div class="flex justify-end gap-2 pt-2">
                    <flux:modal.close><flux:button variant="ghost">Cancelar</flux:button></flux:modal.close>
                    <flux:button variant="danger" wire:click="borrar" wire:loading.attr="disabled" wire:target="borrar">Eliminar</flux:button>
                </div>
            @endif
        </div>
    </flux:modal>
</div>
