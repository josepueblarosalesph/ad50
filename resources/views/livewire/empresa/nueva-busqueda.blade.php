<div>
    <x-slot:context>Empresa</x-slot:context>
    <x-slot:nav>
        <a href="{{ route('empresa.panel') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-gray-500 hover:text-ink">Panel</a>
        <a wire:navigate href="{{ route('empresa.busquedas.index') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-gray-500 hover:text-ink">Búsquedas</a>
        <a wire:navigate href="{{ route('empresa.busquedas.create') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-ink bg-orange-100">Nueva búsqueda</a>
    </x-slot:nav>
    <x-slot:sidebar>
        <div class="text-[10.5px] tracking-[0.12em] uppercase text-gray-400 font-bold px-2.5 mb-2">Búsquedas</div>
        <a wire:navigate href="{{ route('empresa.busquedas.index') }}" class="flex items-center gap-3 text-[14px] font-semibold px-3 py-2.5 rounded-[10px] text-gray-700 hover:bg-paper"><flux:icon.bars-3 class="size-[18px]" />Todas las búsquedas</a>
        <a href="{{ $editando ? route('empresa.busquedas.edit', $busqueda) : route('empresa.busquedas.create') }}" class="flex items-center gap-3 text-[14px] font-semibold px-3 py-2.5 rounded-[10px] bg-orange-100 text-orange-600"><flux:icon.magnifying-glass class="size-[18px]" />{{ $editando ? 'Editar búsqueda' : 'Nueva búsqueda' }}</a>
    </x-slot:sidebar>

    <div class="max-w-4xl">
        <div class="mb-6"><h1 class="text-[27px] font-extrabold">{{ $editando ? 'Editar búsqueda' : 'Nueva búsqueda de perfil' }}</h1><p class="text-[14px] text-gray-500 mt-1.5">Puedes seleccionar varias alternativas. Todo criterio configurado es obligatorio.</p></div>

        <form wire:submit="save" class="ad-card">
            <div class="ad-card-head"><h2 class="text-[16px] font-bold">Datos de la búsqueda</h2><span class="text-[11px] font-bold text-orange-500 uppercase tracking-wider">Criterios</span></div>
            <div class="p-6 space-y-5">
                <flux:input wire:model="titulo" label="Nombre interno de la búsqueda *" placeholder="Subgerente/a de Finanzas — Planta Coronel" />
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="rounded-xl border border-line-2 p-4">
                        <x-multi-combobox model="cargo" label="Cargo" :opciones="$cargosAreas" :seleccion="$cargo" error="cargo" descripcion="Busca y agrega uno o varios cargos." />
                    </div>
                    <div class="rounded-xl border border-line-2 p-4">
                        <x-multi-combobox model="carrera" label="Carrera o título" :opciones="$carreras" :seleccion="$carrera" error="carrera" descripcion="Busca y agrega una o varias carreras." />
                    </div>
                    <div class="rounded-xl border border-line-2 p-4">
                        <x-multi-combobox model="especialidad" label="Especialidad o área" :opciones="$especialidades" :seleccion="$especialidad" error="especialidad" :descripcion="filled($especialidades) ? 'Depende de las carreras elegidas.' : 'Selecciona primero una carrera.'" vacio="Selecciona primero una carrera." />
                    </div>
                    <div class="rounded-xl border border-line-2 p-4">
                        <x-multi-combobox model="industria" label="Industria" :opciones="$industrias" :seleccion="$industria" error="industria" descripcion="Busca y agrega una o varias industrias." />
                    </div>
                    <div class="rounded-xl border border-line-2 p-4">
                        <x-multi-combobox model="ciudad" label="Región" :opciones="$ciudades" :seleccion="$ciudad" error="ciudad" descripcion="Busca y agrega una o varias regiones." />
                    </div>
                    <div class="rounded-xl border border-line-2 p-4">
                        <x-multi-combobox model="habilidad" label="Habilidades" :opciones="$habilidades" :seleccion="$habilidad" error="habilidad" descripcion="Busca y agrega una o varias habilidades." />
                    </div>
                    <div class="flex items-center gap-3 self-start">
                        <span class="flex-none text-[13px] font-bold text-ink">Experiencia mínima</span>
                        <flux:select wire:model="aniosMinimos" class="flex-1" aria-label="Experiencia mínima">
                            @foreach ($rangosExperiencia as $valor => $etiqueta)<flux:select.option :value="$valor">{{ $etiqueta }}</flux:select.option>@endforeach
                        </flux:select>
                    </div>
                    <div class="self-start rounded-xl border border-line-2 p-4">
                        <x-slider-rango-edad :min="$limitesEdad['min']" :max="$limitesEdad['max']" :desde="$edadMin" :hasta="$edadMax" />
                    </div>
                    <div class="space-y-2 rounded-xl border border-line-2 p-4"><x-combobox model="empresa" label="Empresa" :opciones="$empresas" :valor="$empresa" placeholder="Escribe para buscar" /><p class="text-[12px] text-gray-500">Basta que aparezca en alguna de sus experiencias.</p></div>
                    <div class="space-y-2 rounded-xl border border-line-2 p-4"><x-combobox model="institucion" label="Institución de estudio" :opciones="$instituciones" :valor="$institucion" placeholder="Escribe para buscar" /></div>
                    <div class="space-y-2 rounded-xl border border-line-2 p-4 md:col-span-2"><x-palabras-clave :palabras="$palabrasClave" placeholder="Ej. SAP, transformación, planificación" descripcion="Escribe una palabra y presiona Enter. Basta con que el perfil contenga una de ellas." /></div>
                </div>

                <div class="rounded-[12px] border border-orange-200 bg-orange-50 p-4 flex gap-3 text-[13px] text-gray-700"><flux:icon.information-circle class="size-5 text-orange-600 flex-none" /><p><b class="text-ink">Cómo funciona:</b> cada criterio configurado es excluyente. Dentro de un criterio con varias opciones basta cumplir una; entre criterios distintos se deben cumplir todos.</p></div>

                <div class="pt-2 flex justify-end gap-3"><a href="{{ $editando ? route('empresa.resultados', $busqueda) : route('empresa.panel') }}" class="ad-btn-ghost ad-btn-sm">Cancelar</a><button type="submit" class="ad-btn-primary ad-btn-sm">{{ $editando ? 'Guardar y recalcular' : 'Buscar candidatos' }} <flux:icon.arrow-right class="size-4" /></button></div>
            </div>
        </form>
    </div>
</div>
