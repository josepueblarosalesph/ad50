@foreach ($educaciones as $index => $educacion)
    @php($esEscolar = in_array($educacion['nivel'], $nivelesEscolares, true))
    @php($esChile = in_array(mb_strtolower(trim($educacion['pais'] ?? '')), ['', 'chile'], true))
    <fieldset class="rounded-[14px] border border-line-2 p-5" wire:key="educacion-{{ $index }}">
        <div class="mb-5 flex items-center justify-between gap-3"><legend class="font-bold">Educación {{ $index + 1 }}</legend>@if (count($educaciones) === 1)<span class="ad-chip ad-chip-orange">Obligatoria</span>@else<button type="button" wire:click="removeEducacion({{ $index }})" class="inline-flex items-center gap-1 text-[13px] font-bold text-[#A93226] dark:text-red-400"><flux:icon.trash class="size-4" />Quitar</button>@endif</div>
        <div class="grid gap-4 md:grid-cols-2">
            <flux:select wire:model.live="educaciones.{{ $index }}.nivel" label="Nivel de estudios *">
                <flux:select.option value="">Selecciona un nivel</flux:select.option>
                @foreach ($nivelesEstudio as $opcion)<flux:select.option :value="$opcion">{{ $opcion }}</flux:select.option>@endforeach
            </flux:select>
            <flux:input wire:model.live.debounce.500ms="educaciones.{{ $index }}.pais" label="País *" placeholder="Chile" />
            @if ($esChile)
                <div x-data="{ otro: false }" wire:key="institucion-wrap-{{ $index }}">
                    <div x-show="!otro">
                        <x-combobox model="educaciones.{{ $index }}.institucion" label="Institución de educación *" :opciones="$instituciones" :valor="$educacion['institucion'] ?? ''" error="educaciones.{{ $index }}.institucion" placeholder="Escribe para buscar" />
                        <button type="button" x-on:click="otro = true" class="mt-1.5 text-[12px] font-semibold text-orange-600 transition hover:text-orange-500">¿No encuentras tu institución? Regístrala aquí</button>
                    </div>
                    <div x-show="otro" x-cloak>
                        <flux:input wire:model="educaciones.{{ $index }}.institucion" label="Institución de educación *" placeholder="Escribe el nombre de tu institución" />
                        <button type="button" x-on:click="otro = false" class="mt-1.5 text-[12px] font-semibold text-gray-500 transition hover:text-ink">Volver a la lista</button>
                    </div>
                </div>
            @else
                <flux:input wire:model="educaciones.{{ $index }}.institucion" label="Institución de educación *" placeholder="Nombre de la institución" />
            @endif

            @if ($educacion['nivel'] !== '' && $esEscolar)
                <flux:select wire:model.live="educaciones.{{ $index }}.situacion" label="Situación">
                    <flux:select.option value="">Selecciona una situación</flux:select.option>
                    @foreach ($situacionesEstudio as $opcion)<flux:select.option :value="$opcion">{{ $opcion }}</flux:select.option>@endforeach
                </flux:select>
                <flux:select wire:model="educaciones.{{ $index }}.egreso_anio" label="Año de egreso{{ ($educacion['situacion'] ?? '') === 'Estudiando' ? '' : ' *' }}">
                    <flux:select.option value="">Selecciona un año</flux:select.option>
                    @foreach (range(now()->year, 1900) as $anio)<flux:select.option :value="$anio">{{ $anio }}</flux:select.option>@endforeach
                </flux:select>
            @elseif ($educacion['nivel'] !== '')
                @if ($esChile)
                    <div x-data="{ otro: false }" wire:key="carrera-wrap-{{ $index }}">
                        <div x-show="!otro">
                            <x-combobox model="educaciones.{{ $index }}.carrera" label="Carrera *" :opciones="$carrerasEstudio" :valor="$educacion['carrera'] ?? ''" error="educaciones.{{ $index }}.carrera" placeholder="Escribe para buscar" />
                            <button type="button" x-on:click="otro = true" class="mt-1.5 text-[12px] font-semibold text-orange-600 transition hover:text-orange-500">¿No encuentras tu carrera? Regístrala aquí</button>
                        </div>
                        <div x-show="otro" x-cloak>
                            <flux:input wire:model="educaciones.{{ $index }}.carrera" label="Carrera *" placeholder="Escribe el nombre de tu carrera" />
                            <button type="button" x-on:click="otro = false" class="mt-1.5 text-[12px] font-semibold text-gray-500 transition hover:text-ink">Volver a la lista</button>
                        </div>
                    </div>
                @else
                    <flux:input wire:model="educaciones.{{ $index }}.carrera" label="Carrera *" placeholder="Nombre de la carrera" />
                @endif
                <flux:input wire:model="educaciones.{{ $index }}.mencion" label="Mención" placeholder="Mención o especialidad" />
                <flux:select wire:model="educaciones.{{ $index }}.modalidad" label="Modalidad de estudios *">
                    <flux:select.option value="">Selecciona una modalidad</flux:select.option>
                    @foreach ($modalidadesEstudio as $opcion)<flux:select.option :value="$opcion">{{ $opcion }}</flux:select.option>@endforeach
                </flux:select>
                <flux:select wire:model.live="educaciones.{{ $index }}.situacion" label="Situación *">
                    <flux:select.option value="">Selecciona una situación</flux:select.option>
                    @foreach ($situacionesEstudio as $opcion)<flux:select.option :value="$opcion">{{ $opcion }}</flux:select.option>@endforeach
                </flux:select>
                <div class="grid grid-cols-2 gap-3 md:col-span-2 md:max-w-md">
                    <flux:select wire:model="educaciones.{{ $index }}.inicio_anio" label="Año de inicio *"><flux:select.option value="">Selecciona</flux:select.option>@foreach (range(now()->year, 1900) as $anio)<flux:select.option :value="$anio">{{ $anio }}</flux:select.option>@endforeach</flux:select>
                    @if (($educacion['situacion'] ?? '') !== 'Estudiando')
                        <flux:select wire:model="educaciones.{{ $index }}.termino_anio" label="Año de término *"><flux:select.option value="">Selecciona</flux:select.option>@foreach (range(now()->year, 1900) as $anio)<flux:select.option :value="$anio">{{ $anio }}</flux:select.option>@endforeach</flux:select>
                    @endif
                </div>
            @endif
        </div>
    </fieldset>
@endforeach
