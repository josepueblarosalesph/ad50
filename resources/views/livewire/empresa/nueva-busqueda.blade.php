<div>
    <x-slot:context>Empresa</x-slot:context>
    <x-slot:nav>
        <a href="{{ route('empresa.panel') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-gray-500 hover:text-ink">Mi Panel</a>
        <a wire:navigate href="{{ route('empresa.busquedas.index') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-ink bg-orange-100">Mis Procesos</a>
    </x-slot:nav>
    <x-slot:sidebar>
        <div class="text-[10.5px] tracking-[0.12em] uppercase text-gray-400 font-bold px-2.5 mb-2">Procesos</div>
        <a wire:navigate href="{{ route('empresa.busquedas.index') }}" class="flex items-center gap-3 text-[14px] font-semibold px-3 py-2.5 rounded-[10px] text-gray-700 hover:bg-paper"><flux:icon.bars-3 class="size-[18px]" />Todos los procesos</a>
        <a href="{{ $editando ? route('empresa.busquedas.edit', $busqueda) : route('empresa.busquedas.create') }}" class="flex items-center gap-3 text-[14px] font-semibold px-3 py-2.5 rounded-[10px] bg-orange-100 text-orange-600"><flux:icon.magnifying-glass class="size-[18px]" />{{ $editando ? 'Editar proceso' : 'Nuevo proceso' }}</a>
    </x-slot:sidebar>

    <div class="max-w-4xl">
        <div class="mb-6"><h1 class="text-[27px] font-extrabold">{{ $editando ? 'Editar proceso' : 'Nuevo proceso de selección' }}</h1><p class="text-[14px] text-gray-500 mt-1.5">Puedes seleccionar varias alternativas. Todo criterio configurado es obligatorio.</p></div>

        <form wire:submit="save" class="ad-card">
            <div class="ad-card-head"><h2 class="text-[16px] font-bold">Datos del proceso</h2></div>
            <div class="p-6 space-y-5">
                <flux:input wire:model="titulo" label="Nombre de la postulación *" placeholder="Subgerente/a de Finanzas — Planta Coronel" />
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="rounded-xl border border-line-2 p-4">
                        <livewire:empresa.selector-criterio :criterios="$criteriosActuales" wire:model="cargo" campo="cargo" etiqueta="Cargo" descripcion="Busca y agrega uno o varios cargos." wire:key="sel-cargo" />
                    </div>
                    <div class="rounded-xl border border-line-2 p-4">
                        <livewire:empresa.selector-criterio :criterios="$criteriosActuales" wire:model="carrera" campo="carrera" etiqueta="Carrera o título" descripcion="Busca y agrega una o varias carreras." wire:key="sel-carrera" />
                    </div>
                    <div class="rounded-xl border border-line-2 p-4">
                        <flux:input wire:model="especialidad" label="Especialidad o mención" placeholder="Ej. Finanzas corporativas" description="Coincide si aparece en la mención del postulante." />
                    </div>
                    <div class="rounded-xl border border-line-2 p-4">
                        <livewire:empresa.selector-criterio :criterios="$criteriosActuales" wire:model="industria" campo="industria" etiqueta="Industria" descripcion="Busca y agrega una o varias industrias." wire:key="sel-industria" />
                    </div>
                    <div class="rounded-xl border border-line-2 p-4">
                        <livewire:empresa.selector-criterio :criterios="$criteriosActuales" wire:model="ciudad" campo="ciudad" etiqueta="Región" descripcion="Busca y agrega una o varias regiones." wire:key="sel-ciudad" />
                    </div>
                    <div class="rounded-xl border border-line-2 p-4">
                        <livewire:empresa.selector-criterio :criterios="$criteriosActuales" wire:model="habilidad" campo="habilidad" etiqueta="Habilidades" descripcion="Busca y agrega una o varias habilidades." wire:key="sel-habilidad" />
                    </div>
                    <div class="rounded-xl border border-line-2 p-4">
                        <livewire:empresa.selector-criterio :criterios="$criteriosActuales" wire:model="situacionLaboral" campo="situacion_laboral" etiqueta="Situación laboral" wire:key="sel-situacion-laboral" />
                    </div>
                    <div class="rounded-xl border border-line-2 p-4">
                        <livewire:empresa.selector-criterio :criterios="$criteriosActuales" wire:model="genero" campo="genero" etiqueta="Género" wire:key="sel-genero" />
                    </div>
                    <div class="rounded-xl border border-line-2 p-4">
                        <livewire:empresa.selector-criterio :criterios="$criteriosActuales" wire:model="nivelEstudios" campo="nivel_estudios" etiqueta="Nivel de estudios" wire:key="sel-nivel-estudios" />
                    </div>
                    <div class="rounded-xl border border-line-2 p-4">
                        <livewire:empresa.selector-criterio :criterios="$criteriosActuales" wire:model="situacionEstudios" campo="situacion_estudios" etiqueta="Situación de estudios" wire:key="sel-situacion-estudios" />
                    </div>
                    <div class="rounded-xl border border-line-2 p-4">
                        <livewire:empresa.selector-criterio :criterios="$criteriosActuales" wire:model="idioma" campo="idioma" etiqueta="Idioma" wire:key="sel-idioma" />
                    </div>
                    <div class="rounded-xl border border-line-2 p-4">
                        <livewire:empresa.selector-criterio :criterios="$criteriosActuales" wire:model="actividadEconomica" campo="actividad_economica" etiqueta="Actividad económica" wire:key="sel-actividad-economica" />
                    </div>
                    <div class="self-start rounded-xl border border-line-2 p-4">
                        <label for="renta-max-form" class="flex items-center justify-between gap-2 text-[13px] font-bold text-ink">
                            Expectativa de renta
                            <span @class(['text-[12px] font-bold', 'text-orange-600' => $rentaMax > 0, 'text-gray-500' => $rentaMax === 0])>{{ $rentaMax > 0 ? '$'.number_format($rentaMax, 0, ',', '.').' o menos' : 'Sin filtrar' }}</span>
                        </label>
                        <input id="renta-max-form" type="range" wire:model.live="rentaMax" min="0" max="8000000" step="200000" class="mt-3 w-full accent-orange-500" />
                        <div class="mt-1 flex justify-between text-[10.5px] font-bold text-gray-400"><span>Sin filtrar</span><span>$8.000.000</span></div>
                    </div>
                    <div class="self-start rounded-xl border border-line-2 p-4">
                        <x-slider-rango-edad label="Años de experiencia" :min="$limitesExperiencia['min']" :max="$limitesExperiencia['max']" :desde="$expMin" :hasta="$expMax" model-desde="expMin" model-hasta="expMax" />
                    </div>
                    <div class="self-start rounded-xl border border-line-2 p-4">
                        <x-slider-rango-edad :min="$limitesEdad['min']" :max="$limitesEdad['max']" :desde="$edadMin" :hasta="$edadMax" />
                    </div>
                    <div class="space-y-2 rounded-xl border border-line-2 p-4"><x-combobox model="empresa" label="Empresa" :opciones="$empresas" :valor="$empresa" placeholder="Escribe para buscar" /><p class="text-[12px] text-gray-500">Basta que aparezca en alguna de sus experiencias.</p></div>
                    <div class="space-y-2 rounded-xl border border-line-2 p-4"><x-combobox model="institucion" label="Institución de estudio" :opciones="$instituciones" :valor="$institucion" placeholder="Escribe para buscar" /></div>
                    <div class="space-y-2 rounded-xl border border-line-2 p-4 md:col-span-2"><x-palabras-clave :palabras="$palabrasClave" placeholder="Ej. SAP, transformación, planificación" descripcion="Escribe una palabra y presiona Enter. Basta con que el perfil contenga una de ellas." /></div>
                </div>

                <div class="pt-2 flex justify-end gap-3"><a href="{{ $editando ? route('empresa.resultados', $busqueda) : route('empresa.panel') }}" class="ad-btn-ghost ad-btn-sm">Cancelar</a><button type="submit" class="ad-btn-primary ad-btn-sm">{{ $editando ? 'Guardar' : 'Buscar candidatos' }} <flux:icon.arrow-right class="size-4" /></button></div>
            </div>
        </form>
    </div>
</div>
