<div class="ad-panel">
    <x-slot:context>Empresa</x-slot:context>
    <x-slot:nav>
        <a wire:navigate href="{{ route('empresa.panel') }}" class="rounded-lg px-3.5 py-2 text-[13.5px] font-semibold text-gray-500 hover:text-ink">Mi Panel</a>
        <a wire:navigate href="{{ route('empresa.busquedas.index') }}" class="rounded-lg bg-orange-100 px-3.5 py-2 text-[13.5px] font-semibold text-ink">Mis Procesos</a>
        <a wire:navigate href="{{ route('empresa.planes') }}" class="rounded-lg px-3.5 py-2 text-[13.5px] font-semibold text-gray-500 hover:text-ink">Planes</a>
        @if (auth()->user()->esPrincipalEmpresa())
            <a wire:navigate href="{{ route('empresa.equipo') }}" class="rounded-lg px-3.5 py-2 text-[13.5px] font-semibold text-gray-500 hover:text-ink">Equipo</a>
        @endif
    </x-slot:nav>

    <x-slot:sidebar>
        <div class="mb-2 px-2.5 text-[12px] font-bold uppercase tracking-[0.12em] text-gray-400">Procesos</div>
        <a wire:navigate href="{{ route('empresa.busquedas.index') }}" class="flex items-center gap-3 rounded-[10px] bg-orange-100 px-3 py-2.5 text-[14px] font-semibold text-orange-600"><flux:icon.bars-3 class="size-[18px]" />Todos los procesos</a>
        <a wire:navigate href="{{ route('empresa.busquedas.create') }}" class="flex items-center gap-3 rounded-[10px] px-3 py-2.5 text-[14px] font-semibold text-gray-700 hover:bg-paper"><flux:icon.magnifying-glass class="size-[18px]" />Nuevo proceso</a>
    </x-slot:sidebar>

    @if (session('status'))
        <div class="mb-5 rounded-xl border border-[#BFE6CD] bg-match-100 px-4 py-3 text-[13px] font-bold text-match">{{ session('status') }}</div>
    @endif

    @if ($eliminadoId)
        <div class="mb-5 flex flex-wrap items-center justify-between gap-x-4 gap-y-2 rounded-xl border border-line-2 bg-paper px-4 py-3">
            <div class="flex items-center gap-2 text-[13px] text-gray-700">
                <flux:icon.trash class="size-4 flex-none text-gray-400" />
                <span>Eliminaste el proceso <b class="text-ink">«{{ $eliminadoTitulo }}»</b>.</span>
                <button type="button" wire:click="restaurar" wire:loading.attr="disabled" wire:target="restaurar" class="font-bold text-orange-600 underline underline-offset-2 hover:text-orange-700">Deshacer</button>
            </div>
            <span class="text-[12px] text-gray-500">Este proceso se eliminará en forma definitiva en los siguientes {{ \App\Models\Busqueda::DIAS_RETENCION_PAPELERA }} días.</span>
        </div>
    @endif

    <div class="mb-6 flex flex-wrap items-start justify-between gap-5">
        <div><h1 class="text-[27px] font-extrabold">Mis procesos</h1><p class="mt-1.5 text-[14px] text-gray-500">Revisa y continúa todos tus procesos de selección.</p></div>
        <a wire:navigate href="{{ route('empresa.busquedas.create') }}" class="ad-btn-primary ad-btn-sm"><flux:icon.plus class="size-4" />Nuevo proceso</a>
    </div>

    <section class="ad-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-[14px]">
                <thead><tr class="ad-thead-row"><th class="p-4">Proceso</th><th class="p-4">Candidatos</th><th class="p-4">Favoritos</th><th class="p-4">Fecha de creación</th><th class="p-4">Estado</th><th class="p-4"></th></tr></thead>
                <tbody>
                    @forelse ($busquedas as $busqueda)
                        <tr wire:key="busqueda-{{ $busqueda->id }}" class="border-b border-line last:border-0">
                            <td class="p-4"><a wire:navigate href="{{ route('empresa.resultados', $busqueda) }}" class="block rounded-lg font-bold text-ink underline decoration-orange-300 underline-offset-4 transition hover:text-orange-600 hover:decoration-orange-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-600">{{ $busqueda->titulo }}</a></td>
                            <td class="p-4"><a wire:navigate href="{{ route('empresa.resultados', $busqueda) }}" class="font-bold text-orange-600 underline decoration-orange-200 underline-offset-4">{{ $busqueda->candidatos_count }}</a></td>
                            <td class="p-4 text-gray-600"><span class="inline-flex items-center gap-1.5"><flux:icon.star variant="solid" class="size-4 text-orange-500" />{{ $busqueda->favoritos_count }}</span></td>
                            <td class="p-4 text-gray-600">{{ $busqueda->created_at->translatedFormat('d M Y') }}</td>
                            <td class="p-4">
                                <select
                                    wire:key="estado-{{ $busqueda->id }}"
                                    wire:change="cambiarEstado({{ $busqueda->id }}, $event.target.value)"
                                    aria-label="Estado del proceso {{ $busqueda->titulo }}"
                                    @class([
                                        'rounded-lg border px-2.5 py-1.5 text-[13px] font-bold focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500',
                                        'border-[#BFE6CD] bg-match-100 text-match' => $busqueda->estaVigente(),
                                        'border-line-2 bg-paper text-gray-600' => ! $busqueda->estaVigente(),
                                    ])
                                >
                                    @foreach ($estados as $valor => $etiqueta)
                                        <option value="{{ $valor }}" @selected($busqueda->estado === $valor)>{{ $etiqueta }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="p-4 text-right"><div class="flex justify-end gap-4"><a wire:navigate href="{{ route('empresa.resultados', $busqueda) }}" class="font-bold text-orange-600 hover:text-orange-700">Ver</a><a wire:navigate href="{{ route('empresa.busquedas.edit', $busqueda) }}" class="font-bold text-gray-500 hover:text-ink">Editar</a><button type="button" wire:click="confirmarBorrado({{ $busqueda->id }})" class="font-bold text-[#A93226] hover:text-red-700 dark:text-red-400">Borrar</button></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-10 text-center"><flux:icon.magnifying-glass class="mx-auto size-8 text-gray-400" /><h2 class="mt-3 font-bold">Aún no has creado procesos</h2><a wire:navigate href="{{ route('empresa.busquedas.create') }}" class="ad-btn-primary ad-btn-sm mt-4">Crear el primero</a></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($busquedas->hasPages())
        <div class="mt-6">{{ $busquedas->links() }}</div>
    @endif

    {{-- Confirmación de borrado: reemplaza el confirm nativo del navegador. --}}
    <flux:modal name="borrar-proceso" class="max-w-lg" wire:close="$set('confirmacionTexto', '')">
        <div class="space-y-4">
            <div class="flex items-start gap-3">
                <span class="grid size-10 flex-none place-items-center rounded-xl bg-red-100 text-[#A93226] dark:bg-red-950/40 dark:text-red-400"><flux:icon.trash class="size-5" /></span>
                <div class="min-w-0">
                    <flux:heading size="lg">Eliminar proceso</flux:heading>
                    @if ($borrandoTitulo !== '')
                        <flux:text class="mt-1 truncate">«{{ $borrandoTitulo }}»</flux:text>
                    @endif
                </div>
            </div>

            <flux:text>Al eliminar este proceso, se eliminarán los filtros de búsqueda y los candidatos marcados como favoritos (salvo los que están como favoritos en otros procesos).</flux:text>

            <flux:text>Para confirmar, escribe <strong class="font-bold text-ink">ELIMINAR</strong> en el siguiente cuadro y haz clic en Aceptar.</flux:text>

            <flux:input wire:model.live.debounce.200ms="confirmacionTexto" placeholder="ELIMINAR" autocomplete="off" autofocus />
            @error('confirmacionTexto')<flux:text class="text-[#A93226] dark:text-red-400">{{ $message }}</flux:text>@enderror

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="ghost">Cancelar</flux:button></flux:modal.close>
                <flux:button variant="danger" wire:click="borrar" wire:loading.attr="disabled" wire:target="borrar" :disabled="mb_strtoupper(trim($confirmacionTexto)) !== 'ELIMINAR'">Aceptar</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
