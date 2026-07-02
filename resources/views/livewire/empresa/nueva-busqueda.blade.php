<div>
    <x-slot:context>Empresa</x-slot:context>
    <x-slot:nav>
        <a href="{{ route('empresa.panel') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-gray-500 hover:text-ink">Panel</a>
        <a wire:navigate href="{{ route('empresa.busquedas.index') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-gray-500 hover:text-ink">Búsquedas</a>
        <a wire:navigate href="{{ route('empresa.busquedas.create') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-ink bg-orange-100">Nueva búsqueda</a>
        <a href="{{ route('planes') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-gray-500 hover:text-ink">Mi plan</a>
    </x-slot:nav>
    <x-slot:sidebar>
        <div class="text-[10.5px] tracking-[0.12em] uppercase text-gray-400 font-bold px-2.5 mb-2">Búsquedas</div>
        <a wire:navigate href="{{ route('empresa.busquedas.index') }}" class="flex items-center gap-3 text-[14px] font-semibold px-3 py-2.5 rounded-[10px] text-gray-700 hover:bg-paper"><flux:icon.bars-3 class="size-[18px]" />Todas las búsquedas</a>
        <a href="{{ $editando ? route('empresa.busquedas.edit', $busqueda) : route('empresa.busquedas.create') }}" class="flex items-center gap-3 text-[14px] font-semibold px-3 py-2.5 rounded-[10px] bg-orange-100 text-orange-600"><flux:icon.magnifying-glass class="size-[18px]" />{{ $editando ? 'Editar búsqueda' : 'Nueva búsqueda' }}</a>
        <a href="{{ route('empresa.panel') }}" class="flex items-center gap-3 text-[14px] font-semibold px-3 py-2.5 rounded-[10px] text-gray-700 hover:bg-paper"><flux:icon.bars-3 class="size-[18px]" />Activas</a>
    </x-slot:sidebar>

    <div class="max-w-4xl">
        <div class="mb-6"><h1 class="text-[27px] font-extrabold">{{ $editando ? 'Editar búsqueda' : 'Nueva búsqueda de perfil' }}</h1><p class="text-[14px] text-gray-500 mt-1.5">Puedes seleccionar varias alternativas. Todo criterio configurado es obligatorio.</p></div>

        <form wire:submit="save" class="ad-card">
            <div class="ad-card-head"><h2 class="text-[16px] font-bold">Datos de la búsqueda</h2><span class="text-[11px] font-bold text-orange-500 uppercase tracking-wider">Paso 1 · Criterios</span></div>
            <div class="p-6 space-y-5">
                <flux:input wire:model="titulo" label="Nombre interno de la búsqueda *" placeholder="Subgerente/a de Finanzas — Planta Coronel" />
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="rounded-xl border border-line-2 p-4"><flux:checkbox.group wire:model="cargo" label="Cargo o especialidad" description="Puedes marcar varias alternativas."><div class="mt-3 max-h-48 space-y-2 overflow-y-auto pr-2">@foreach ($cargosAreas as $opcion)<flux:checkbox wire:key="cargo-{{ $loop->index }}" :value="$opcion" :label="$opcion" />@endforeach</div></flux:checkbox.group></div>
                    <div class="rounded-xl border border-line-2 p-4"><flux:checkbox.group wire:model.live="carrera" label="Carrera o título" description="Puedes marcar varias alternativas."><div class="mt-3 max-h-48 space-y-2 overflow-y-auto pr-2">@foreach ($carreras as $opcion)<flux:checkbox wire:key="carrera-{{ $loop->index }}" :value="$opcion" :label="$opcion" />@endforeach</div></flux:checkbox.group></div>
                    <div class="rounded-xl border border-line-2 p-4"><flux:checkbox.group wire:model="especialidad" label="Especialidad o área" description="Depende de las carreras elegidas."><div class="mt-3 max-h-48 space-y-2 overflow-y-auto pr-2">@forelse ($especialidades as $opcion)<flux:checkbox wire:key="especialidad-{{ $loop->index }}" :value="$opcion" :label="$opcion" />@empty<p class="text-[13px] text-gray-500">Selecciona primero una carrera.</p>@endforelse</div></flux:checkbox.group></div>
                    <div class="rounded-xl border border-line-2 p-4"><flux:checkbox.group wire:model="industria" label="Industria" description="Puedes marcar varias alternativas."><div class="mt-3 max-h-48 space-y-2 overflow-y-auto pr-2">@foreach ($industrias as $opcion)<flux:checkbox wire:key="industria-{{ $loop->index }}" :value="$opcion" :label="$opcion" />@endforeach</div></flux:checkbox.group></div>
                    <div class="rounded-xl border border-line-2 p-4"><flux:checkbox.group wire:model="ciudad" label="Ciudad o región" description="Puedes marcar varias alternativas."><div class="mt-3 max-h-48 space-y-2 overflow-y-auto pr-2">@foreach ($ciudades as $opcion)<flux:checkbox wire:key="ciudad-{{ $loop->index }}" :value="$opcion" :label="$opcion" />@endforeach</div></flux:checkbox.group></div>
                    <div class="space-y-2 rounded-xl border border-line-2 p-4"><flux:input wire:model="aniosMinimos" type="number" min="0" max="80" label="Experiencia mínima en años" description="Si es mayor que cero, se exigirá como mínimo." /></div>
                    <div class="space-y-2 rounded-xl border border-line-2 p-4 md:col-span-2"><flux:input wire:model="palabraClave" label="Palabra clave" placeholder="Ej. SAP, transformación, planificación" description="Si la completas, el perfil deberá contener esta palabra." /></div>
                </div>

                <div class="rounded-[12px] border border-orange-200 bg-orange-50 p-4 flex gap-3 text-[13px] text-gray-700"><flux:icon.information-circle class="size-5 text-orange-600 flex-none" /><p><b class="text-ink">Cómo funciona:</b> cada criterio configurado es excluyente. Dentro de un criterio con varias opciones basta cumplir una; entre criterios distintos se deben cumplir todos.</p></div>

                <div class="pt-2 flex justify-end gap-3"><a href="{{ $editando ? route('empresa.resultados', $busqueda) : route('empresa.panel') }}" class="ad-btn-ghost ad-btn-sm">Cancelar</a><button type="submit" class="ad-btn-primary ad-btn-sm">{{ $editando ? 'Guardar y recalcular' : 'Buscar candidatos' }} <flux:icon.arrow-right class="size-4" /></button></div>
            </div>
        </form>
    </div>
</div>
