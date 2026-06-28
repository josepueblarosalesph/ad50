<div>
    <x-slot:context>Empresa</x-slot:context>
    <x-slot:nav>
        <a href="{{ route('empresa.panel') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-gray-500 hover:text-ink">Panel</a>
        <a href="{{ route('empresa.busquedas.create') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-ink bg-orange-100">Búsquedas</a>
        <a href="{{ route('planes') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-gray-500 hover:text-ink">Mi plan</a>
    </x-slot:nav>
    <x-slot:sidebar>
        <div class="text-[10.5px] tracking-[0.12em] uppercase text-gray-400 font-bold px-2.5 mb-2">Búsquedas</div>
        <a href="{{ route('empresa.busquedas.create') }}" class="flex items-center gap-3 text-[14px] font-semibold px-3 py-2.5 rounded-[10px] bg-orange-100 text-orange-600"><flux:icon.magnifying-glass class="size-[18px]" />Nueva búsqueda</a>
        <a href="{{ route('empresa.panel') }}" class="flex items-center gap-3 text-[14px] font-semibold px-3 py-2.5 rounded-[10px] text-gray-700 hover:bg-paper"><flux:icon.bars-3 class="size-[18px]" />Activas</a>
    </x-slot:sidebar>

    <div class="max-w-4xl">
        <div class="mb-6"><h1 class="text-[27px] font-extrabold">Nueva búsqueda de perfil</h1><p class="text-[14px] text-gray-500 mt-1.5">Los campos que completas filtran; los que dejas vacíos no descartan candidatos.</p></div>

        <form wire:submit="save" class="ad-card">
            <div class="ad-card-head"><h2 class="text-[16px] font-bold">Datos de la búsqueda</h2><span class="text-[11px] font-bold text-orange-500 uppercase tracking-wider">Paso 1 · Criterios</span></div>
            <div class="p-6 space-y-5">
                <flux:input wire:model="titulo" label="Nombre interno de la búsqueda *" placeholder="Subgerente/a de Finanzas — Planta Coronel" />
                <div class="grid md:grid-cols-2 gap-4">
                    <flux:input wire:model="cargo" label="Cargo o especialidad" placeholder="Finanzas, control de gestión" />
                    <flux:input wire:model="industria" label="Industria" placeholder="Forestal" />
                    <flux:input wire:model="aniosMinimos" type="number" min="0" max="80" label="Experiencia mínima en años" />
                </div>

                <div class="rounded-[12px] border border-orange-200 bg-orange-50 p-4 flex gap-3 text-[13px] text-gray-700"><flux:icon.exclamation-triangle class="size-5 text-orange-600 flex-none" /><p><b class="text-ink">Importante:</b> la plataforma mostrará solo perfiles que coincidan con los criterios definidos. Nunca entrega acceso a la base completa.</p></div>

                <div class="pt-2 flex justify-end gap-3"><a href="{{ route('empresa.panel') }}" class="ad-btn-ghost ad-btn-sm">Cancelar</a><button type="submit" class="ad-btn-primary ad-btn-sm">Buscar candidatos <flux:icon.arrow-right class="size-4" /></button></div>
            </div>
        </form>
    </div>
</div>
