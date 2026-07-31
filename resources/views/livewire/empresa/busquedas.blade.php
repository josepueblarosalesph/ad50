<div class="ad-panel">
    <x-slot:context>Empresa</x-slot:context>
    <x-slot:nav><x-nav-empresa activo="busquedas" /></x-slot:nav>

    <x-slot:sidebar>
        <div class="mb-2 px-2.5 text-[12px] font-bold uppercase tracking-[0.12em] text-gray-400">Prospección de Candidatos</div>
        <a wire:navigate href="{{ route('empresa.busquedas.index') }}" class="flex items-center gap-3 rounded-[10px] bg-orange-100 px-3 py-2.5 text-[14px] font-semibold text-orange-600"><flux:icon.bars-3 class="size-[18px]" />Todas las búsquedas</a>
        <a wire:navigate href="{{ route('empresa.busquedas.create') }}" class="flex items-center gap-3 rounded-[10px] px-3 py-2.5 text-[14px] font-semibold text-gray-700 hover:bg-paper"><flux:icon.plus class="size-[18px]" />Nueva búsqueda</a>
    </x-slot:sidebar>

    @if (session('status'))
        <div class="mb-5 rounded-xl border border-[#BFE6CD] bg-match-100 px-4 py-3 text-[13px] font-bold text-match">{{ session('status') }}</div>
    @endif

    @if ($eliminadoId)
        <div class="mb-5 flex flex-wrap items-center justify-between gap-x-4 gap-y-2 rounded-xl border border-line-2 bg-paper px-4 py-3">
            <div class="flex items-center gap-2 text-[13px] text-gray-700">
                <flux:icon.trash class="size-4 flex-none text-gray-400" />
                <span>Eliminaste la búsqueda <b class="text-ink">«{{ $eliminadoTitulo }}»</b>.</span>
                <button type="button" wire:click="restaurar" wire:loading.attr="disabled" wire:target="restaurar" class="font-bold text-orange-600 underline underline-offset-2 hover:text-orange-700">Deshacer</button>
            </div>
            <span class="text-[12px] text-gray-500">Esta búsqueda se eliminará en forma definitiva en los siguientes {{ \App\Models\Busqueda::DIAS_RETENCION_PAPELERA }} días.</span>
        </div>
    @endif

    <div class="mb-6 flex flex-wrap items-start justify-between gap-5">
        <div><h1 class="text-[27px] font-extrabold">Prospección de Candidatos</h1><p class="mt-1.5 text-[14px] text-gray-500">Guarda tus filtros de búsqueda y revisa los candidatos que calzan.</p></div>
        <a wire:navigate href="{{ route('empresa.busquedas.create') }}" class="ad-btn-primary ad-btn-sm"><flux:icon.plus class="size-4" />Nueva búsqueda</a>
    </div>

    <section class="ad-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-[14px]">
                <thead>
                    <tr class="ad-thead-row">
                        @foreach ([
                            'titulo' => 'Búsqueda',
                            'candidatos' => 'Candidatos',
                            'creada' => 'Creada',
                        ] as $campo => $etiqueta)
                            <x-th-ordenable :campo="$campo" :orden="$orden" :direccion="$direccion">{{ $etiqueta }}</x-th-ordenable>
                        @endforeach
                        {{-- El autor no es ordenable: vive en otra tabla y ordenar por él exigiría un join. --}}
                        <th class="p-4">Creada por</th>
                        <th class="p-4"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($busquedas as $busqueda)
                        <tr wire:key="busqueda-{{ $busqueda->id }}" class="border-b border-line last:border-0">
                            <td class="p-4"><a wire:navigate href="{{ route('empresa.resultados', $busqueda) }}" class="block rounded-lg font-bold text-ink underline decoration-orange-300 underline-offset-4 transition hover:text-orange-600 hover:decoration-orange-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-600">{{ $busqueda->titulo }}</a></td>
                            <td class="p-4"><a wire:navigate href="{{ route('empresa.resultados', $busqueda) }}" class="font-bold text-orange-600 underline decoration-orange-200 underline-offset-4">{{ $busqueda->candidatos_count }}</a></td>
                            <td class="p-4 text-gray-600">{{ $busqueda->created_at?->translatedFormat('d M Y') ?? '—' }}</td>
                            <td class="p-4 text-gray-600">{{ $busqueda->creador?->name ?? '—' }}</td>
                            <td class="p-4 text-right"><div class="flex justify-end gap-4"><a wire:navigate href="{{ route('empresa.resultados', $busqueda) }}" class="font-bold text-orange-600 hover:text-orange-700">Ver</a><a wire:navigate href="{{ route('empresa.busquedas.edit', $busqueda) }}" class="font-bold text-gray-500 hover:text-ink">Editar</a><button type="button" wire:click="confirmarBorrado({{ $busqueda->id }})" class="font-bold text-[#A93226] hover:text-red-700 dark:text-red-400">Borrar</button></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-10 text-center"><flux:icon.magnifying-glass class="mx-auto size-8 text-gray-400" /><h2 class="mt-3 font-bold">Aún no has creado búsquedas</h2><a wire:navigate href="{{ route('empresa.busquedas.create') }}" class="ad-btn-primary ad-btn-sm mt-4">Crear el primero</a></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($busquedas->hasPages())
        <div class="mt-6">{{ $busquedas->links() }}</div>
    @endif

    {{-- Confirmación de borrado: reemplaza el confirm nativo del navegador. --}}
    <flux:modal name="borrar-busqueda" class="max-w-lg" wire:close="$set('confirmacionTexto', '')">
        <div class="space-y-4">
            <div class="flex items-start gap-3">
                <span class="grid size-10 flex-none place-items-center rounded-xl bg-red-100 text-[#A93226] dark:bg-red-950/40 dark:text-red-400"><flux:icon.trash class="size-5" /></span>
                <div class="min-w-0">
                    <flux:heading size="lg">Eliminar búsqueda</flux:heading>
                    @if ($borrandoTitulo !== '')
                        <flux:text class="mt-1 truncate">«{{ $borrandoTitulo }}»</flux:text>
                    @endif
                </div>
            </div>

            <flux:text>Al eliminar esta búsqueda se eliminan sus filtros y sus coincidencias. Tus favoritos no se tocan: están guardados en la cuenta, no en la búsqueda.</flux:text>

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
