<div class="ad-panel">
    <x-slot:context>Empresa</x-slot:context>
    <x-slot:nav>
        <a wire:navigate href="{{ route('empresa.panel') }}" class="rounded-lg px-3.5 py-2 text-[13.5px] font-semibold text-gray-500 hover:text-ink">Panel</a>
        <a wire:navigate href="{{ route('empresa.busquedas.index') }}" class="rounded-lg bg-orange-100 px-3.5 py-2 text-[13.5px] font-semibold text-ink">Búsquedas</a>
        <a wire:navigate href="{{ route('empresa.busquedas.create') }}" class="rounded-lg px-3.5 py-2 text-[13.5px] font-semibold text-gray-500 hover:text-ink">Nueva búsqueda</a>
    </x-slot:nav>

    <x-slot:sidebar>
        <div class="mb-2 px-2.5 text-[12px] font-bold uppercase tracking-[0.12em] text-gray-400">Búsquedas</div>
        <a wire:navigate href="{{ route('empresa.busquedas.index') }}" class="flex items-center gap-3 rounded-[10px] bg-orange-100 px-3 py-2.5 text-[14px] font-semibold text-orange-600"><flux:icon.bars-3 class="size-[18px]" />Todas las búsquedas</a>
        <a wire:navigate href="{{ route('empresa.busquedas.create') }}" class="flex items-center gap-3 rounded-[10px] px-3 py-2.5 text-[14px] font-semibold text-gray-700 hover:bg-paper"><flux:icon.magnifying-glass class="size-[18px]" />Nueva búsqueda</a>
    </x-slot:sidebar>

    <div class="mb-6 flex flex-wrap items-start justify-between gap-5">
        <div><h1 class="text-[27px] font-extrabold">Mis búsquedas</h1><p class="mt-1.5 text-[14px] text-gray-500">Revisa y continúa todos tus procesos de selección.</p></div>
        <a wire:navigate href="{{ route('empresa.busquedas.create') }}" class="ad-btn-primary ad-btn-sm"><flux:icon.plus class="size-4" />Nueva búsqueda</a>
    </div>

    <section class="ad-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-[14px]">
                <thead><tr class="border-b border-line text-left text-[12px] uppercase tracking-wider text-gray-400"><th class="p-4">Búsqueda</th><th class="p-4">Candidatos</th><th class="p-4">Favoritos</th><th class="p-4">Estado</th><th class="p-4"></th></tr></thead>
                <tbody>
                    @forelse ($busquedas as $busqueda)
                        <tr wire:key="busqueda-{{ $busqueda->id }}" class="border-b border-line last:border-0">
                            <td class="p-4"><b class="block text-ink">{{ $busqueda->titulo }}</b><span class="mt-1 block text-[12px] text-gray-500">Creada {{ $busqueda->created_at->diffForHumans() }}</span></td>
                            <td class="p-4"><a wire:navigate href="{{ route('empresa.resultados', $busqueda) }}" class="font-bold text-orange-600 underline decoration-orange-200 underline-offset-4">{{ $busqueda->candidatos_count }}</a></td>
                            <td class="p-4 text-gray-600"><span class="inline-flex items-center gap-1.5"><flux:icon.star variant="solid" class="size-4 text-orange-500" />{{ $busqueda->favoritos_count }}</span></td>
                            <td class="p-4"><span @class(['ad-chip', 'ad-chip-green' => $busqueda->estado === 'activa'])>{{ ucfirst($busqueda->estado) }}</span></td>
                            <td class="p-4 text-right"><div class="flex justify-end gap-3"><a wire:navigate href="{{ route('empresa.busquedas.edit', $busqueda) }}" class="font-bold text-gray-500 hover:text-ink">Editar</a><a wire:navigate href="{{ route('empresa.resultados', $busqueda) }}" class="font-bold text-orange-600">Ver resultados</a></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-10 text-center"><flux:icon.magnifying-glass class="mx-auto size-8 text-gray-400" /><h2 class="mt-3 font-bold">Aún no has creado búsquedas</h2><a wire:navigate href="{{ route('empresa.busquedas.create') }}" class="ad-btn-primary ad-btn-sm mt-4">Crear la primera</a></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($busquedas->hasPages())
        <div class="mt-6">{{ $busquedas->links() }}</div>
    @endif
</div>
