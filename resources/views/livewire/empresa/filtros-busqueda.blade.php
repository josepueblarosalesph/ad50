<div id="filtros-busqueda" class="pb-5 pr-1">
    <div class="mb-3 flex items-center justify-between gap-2 px-1">
        <span class="text-[11px] font-bold uppercase tracking-[0.12em] text-gray-400">Filtros de búsqueda</span>
        <span wire:loading class="text-[11px] font-bold text-orange-600">Actualizando…</span>
    </div>
    <div class="space-y-2.5">
        @foreach ($grupos as [$label, $model, $opciones])
            <details class="group rounded-xl border border-line-2 bg-white transition-colors dark:bg-[#222528]" @if ($model === 'cargo') open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between gap-2 px-3 py-2.5 text-[13px] font-bold text-ink"><span>{{ $label }}</span><span class="flex items-center gap-1.5 text-[11px] text-gray-500">{{ count($this->{$model}) }} <flux:icon.chevron-down class="size-3.5 transition group-open:rotate-180" /></span></summary>
                <div class="max-h-40 space-y-2 overflow-y-auto border-t border-line px-3 py-3">
                    @forelse ($opciones as $opcion)
                        @if ($model === 'carrera')
                            <flux:checkbox wire:model.live="carrera" :value="$opcion" :label="$opcion" />
                        @else
                            <flux:checkbox wire:model.live="{{ $model }}" :value="$opcion" :label="$opcion" />
                        @endif
                    @empty
                        <p class="text-[12px] text-gray-500">Selecciona primero una carrera.</p>
                    @endforelse
                </div>
            </details>
        @endforeach

        <div class="rounded-xl border border-line-2 bg-white p-3 transition-colors dark:bg-[#222528]"><x-combobox model="institucion" label="Institución de estudio" :opciones="$instituciones" :valor="$institucion" placeholder="Escribe para buscar" /></div>
        <div class="rounded-xl border border-line-2 bg-white p-3 transition-colors dark:bg-[#222528]"><flux:input wire:model.live.debounce.500ms="empresa" label="Empresa" placeholder="Ej. Codelco" /></div>

        <div class="rounded-xl border border-line-2 bg-white p-3 transition-colors dark:bg-[#222528]">
            <label for="anios-minimos" class="flex items-center justify-between gap-2 text-[13px] font-bold text-ink">
                Experiencia mínima
                <span @class(['text-[12px] font-bold', 'text-orange-600' => $aniosMinimos > 0, 'text-gray-500' => $aniosMinimos === 0])>{{ $aniosMinimos > 0 ? $aniosMinimos.' años o más' : 'Sin filtrar' }}</span>
            </label>
            <input
                id="anios-minimos"
                type="range"
                wire:model.live.debounce.400ms="aniosMinimos"
                min="{{ $minimoExperiencia }}"
                max="{{ $maximoExperiencia }}"
                step="5"
                class="mt-3 w-full accent-orange-500"
            />
            <div class="mt-1 flex justify-between text-[10.5px] font-bold text-gray-400"><span>Sin filtrar</span><span>{{ $maximoExperiencia }} años</span></div>
        </div>
        <div class="rounded-xl border border-line-2 bg-white p-3 transition-colors dark:bg-[#222528]"><x-palabras-clave :palabras="$palabrasClave" /></div>
        <p class="px-1 text-[11.5px] leading-relaxed text-gray-500">Los resultados se actualizan a medida que cambias los filtros.</p>
    </div>
</div>
