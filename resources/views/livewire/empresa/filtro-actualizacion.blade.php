<div>
    <x-filtro-acordeon titulo="Actualización de la ficha" :count="$actualizacion !== 'todas' ? 1 : 0">
        <div class="relative">
            <select
                wire:model.live="actualizacion"
                aria-label="Filtrar por antigüedad de la actualización de la ficha"
                class="w-full cursor-pointer appearance-none rounded-lg border border-line-2 bg-white py-2 pl-3 pr-8 text-[13px] font-semibold text-ink transition focus:border-orange-400 focus:outline-none dark:bg-[#222528]"
            >
                <option value="todas">Todas las fechas</option>
                <option value="mes">Actualizada hasta 1 mes</option>
                <option value="1a3">Entre 1 y 3 meses</option>
                <option value="3a6">Entre 3 y 6 meses</option>
                <option value="mas6">Más de 6 meses</option>
            </select>
            <flux:icon.chevron-down class="pointer-events-none absolute inset-y-0 right-2.5 my-auto size-4 text-gray-400" />
        </div>
        <p class="mt-2 text-[11.5px] leading-relaxed text-gray-500">Acota los resultados por cuán reciente es la ficha. No modifica el proceso.</p>
    </x-filtro-acordeon>
</div>
