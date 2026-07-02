<div id="filtros-busqueda" class="pb-5 pr-1">
    <div class="mb-3 px-1 text-[11px] font-bold uppercase tracking-[0.12em] text-gray-400">Filtros de búsqueda</div>
    <form wire:submit="guardar" class="space-y-2.5">
        @foreach ($grupos as [$label, $model, $opciones])
            <details class="group rounded-xl border border-line-2 bg-white" @if ($model === 'cargo') open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between gap-2 px-3 py-2.5 text-[13px] font-bold text-ink"><span>{{ $label }}</span><span class="flex items-center gap-1.5 text-[11px] text-gray-500">{{ count($this->{$model}) }} <flux:icon.chevron-down class="size-3.5 transition group-open:rotate-180" /></span></summary>
                <div class="max-h-40 space-y-2 overflow-y-auto border-t border-line px-3 py-3">
                    @forelse ($opciones as $opcion)
                        @if ($model === 'carrera')
                            <flux:checkbox wire:model.live="carrera" :value="$opcion" :label="$opcion" />
                        @else
                            <flux:checkbox wire:model="{{ $model }}" :value="$opcion" :label="$opcion" />
                        @endif
                    @empty
                        <p class="text-[12px] text-gray-500">Selecciona primero una carrera.</p>
                    @endforelse
                </div>
            </details>
        @endforeach

        <div class="rounded-xl border border-line-2 bg-white p-3"><flux:input wire:model="aniosMinimos" type="number" min="0" max="80" label="Experiencia mínima" /></div>
        <div class="rounded-xl border border-line-2 bg-white p-3"><flux:input wire:model="palabraClave" label="Palabra clave" /></div>
        @if (session('status'))<p class="rounded-lg bg-match-100 px-3 py-2 text-[12px] font-bold text-match">{{ session('status') }}</p>@endif
        <button type="submit" class="ad-btn-primary ad-btn-sm w-full" wire:loading.attr="disabled" wire:target="guardar"><span wire:loading.remove wire:target="guardar">Guardar y recalcular</span><span wire:loading wire:target="guardar">Recalculando…</span></button>
    </form>
</div>
