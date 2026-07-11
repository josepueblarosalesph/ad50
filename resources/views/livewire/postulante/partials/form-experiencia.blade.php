@foreach ($experiencias as $index => $experiencia)
    <fieldset class="rounded-[14px] border border-line-2 p-5" wire:key="experiencia-{{ $index }}">
        <div class="mb-5 flex items-center justify-between gap-3"><legend class="font-bold">Experiencia {{ $index + 1 }}</legend>@if (count($experiencias) === 1)<span class="ad-chip ad-chip-orange">Obligatoria</span>@else<button type="button" wire:click="removeExperiencia({{ $index }})" class="inline-flex items-center gap-1 text-[13px] font-bold text-[#A93226] dark:text-red-400"><flux:icon.trash class="size-4" />Quitar</button>@endif</div>
        <div class="grid md:grid-cols-2 gap-4">
            <x-combobox model="experiencias.{{ $index }}.cargo" label="Cargo u ocupación *" :opciones="$cargos" :valor="$experiencia['cargo'] ?? ''" error="experiencias.{{ $index }}.cargo" placeholder="Escribe para buscar" />
            <flux:select wire:model="experiencias.{{ $index }}.tipo_trabajo" label="Tipo de trabajo *">
                @foreach ($tiposTrabajo as $opcion)<flux:select.option :value="$opcion">{{ $opcion }}</flux:select.option>@endforeach
            </flux:select>
            <x-combobox model="experiencias.{{ $index }}.empresa" label="Empresa *" :opciones="$empresas" :valor="$experiencia['empresa'] ?? ''" error="experiencias.{{ $index }}.empresa" placeholder="Escribe para buscar" />
            <flux:select wire:model="experiencias.{{ $index }}.jerarquia" label="Jerarquía *">
                <flux:select.option value="">Selecciona una jerarquía</flux:select.option>
                @foreach ($jerarquias as $opcion)<flux:select.option :value="$opcion">{{ $opcion }}</flux:select.option>@endforeach
            </flux:select>
            <flux:select wire:model="experiencias.{{ $index }}.actividad_empresa" label="Actividad de la empresa *">
                <flux:select.option value="">Selecciona una actividad</flux:select.option>
                @foreach ($industrias as $opcion)<flux:select.option :value="$opcion">{{ $opcion }}</flux:select.option>@endforeach
            </flux:select>
            <div class="space-y-2">
                <div class="text-[13px] font-medium">Fecha de inicio <span class="text-red-600">*</span></div>
                <div class="grid grid-cols-[1fr_110px] gap-2">
                    <flux:select wire:model="experiencias.{{ $index }}.inicio_mes" aria-label="Mes de inicio">
                        <flux:select.option value="">Mes</flux:select.option>
                        @foreach ($meses as $numero => $mes)<flux:select.option :value="$numero">{{ $mes }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="experiencias.{{ $index }}.inicio_anio" aria-label="Año de inicio">
                        <flux:select.option value="">Año</flux:select.option>
                        @foreach (range(now()->year, 1950) as $anio)<flux:select.option :value="$anio">{{ $anio }}</flux:select.option>@endforeach
                    </flux:select>
                </div>
                <flux:error name="experiencias.{{ $index }}.inicio_mes" />
                <flux:error name="experiencias.{{ $index }}.inicio_anio" />
            </div>
            <div class="md:col-start-2 space-y-3">
                <flux:checkbox wire:model.live="experiencias.{{ $index }}.actualmente" label="Actualmente trabajando" />
                @unless ($experiencia['actualmente'])
                    <div class="space-y-2">
                        <div class="text-[13px] font-medium">Fecha de término <span class="text-red-600">*</span></div>
                        <div class="grid grid-cols-[1fr_110px] gap-2">
                            <flux:select wire:model="experiencias.{{ $index }}.fin_mes" aria-label="Mes de término"><flux:select.option value="">Mes</flux:select.option>@foreach ($meses as $numero => $mes)<flux:select.option :value="$numero">{{ $mes }}</flux:select.option>@endforeach</flux:select>
                            <flux:select wire:model="experiencias.{{ $index }}.fin_anio" aria-label="Año de término"><flux:select.option value="">Año</flux:select.option>@foreach (range(now()->year, 1950) as $anio)<flux:select.option :value="$anio">{{ $anio }}</flux:select.option>@endforeach</flux:select>
                        </div>
                        <flux:error name="experiencias.{{ $index }}.fin_mes" />
                        <flux:error name="experiencias.{{ $index }}.fin_anio" />
                    </div>
                @endunless
            </div>
            <div class="md:col-span-2">
                <flux:textarea wire:model="experiencias.{{ $index }}.responsabilidades" label="Responsabilidades y logros en el cargo *" placeholder="Describe tus principales responsabilidades, resultados y logros." rows="5" />
                <p class="mt-2 text-[13px] leading-relaxed text-gray-500">Prioriza logros concretos y actividades relacionadas con tu foco laboral actual.</p>
            </div>
        </div>
    </fieldset>
@endforeach
