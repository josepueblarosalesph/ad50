<label for="cv" class="block cursor-pointer rounded-[14px] border-2 border-dashed border-orange-200 bg-orange-50/60 p-6 text-center transition hover:border-orange-400 hover:bg-orange-100 dark:border-orange-700 dark:bg-[#33251D] dark:hover:border-orange-500 dark:hover:bg-[#3D2B20]">
    <flux:icon.document-arrow-up class="mx-auto size-8 text-orange-700 dark:text-[#F7C59E]" />
    <span class="mt-3 block text-[14px] font-bold text-ink">Selecciona tu CV en PDF</span>
    <span class="mt-1 block text-[12px] text-gray-500">Un archivo de hasta 10 MB</span>
    <span class="ad-btn-ghost ad-btn-sm mt-4">Elegir archivo</span>
    <input id="cv" type="file" wire:model="cv" accept="application/pdf,.pdf" class="sr-only" />
</label>

<div wire:loading wire:target="cv" class="rounded-[10px] border border-blue-200 bg-blue-50 px-4 py-3 text-[13px] font-semibold text-blue-700 dark:border-blue-800 dark:bg-blue-950/40 dark:text-blue-300" role="status">Cargando el archivo…</div>

@error('cv') <div class="rounded-[10px] border border-red-200 bg-red-50 px-4 py-3 text-[13px] font-semibold text-red-700" role="alert">{{ $message }}</div> @enderror

@if ($cv)
    <div class="flex items-center gap-3 rounded-[10px] border border-blue-200 bg-blue-50 p-3 dark:border-blue-800 dark:bg-blue-950/40">
        <flux:icon.document class="size-5 flex-none text-blue-600 dark:text-blue-300" />
        <div class="min-w-0"><b class="block truncate text-[13px] text-blue-800 dark:text-blue-200">{{ $cv->getClientOriginalName() }}</b><span class="text-[12px] text-blue-700 dark:text-blue-300">Listo para guardar</span></div>
    </div>
@elseif ($cvRutaExistente)
    <div class="flex items-center gap-3 rounded-[10px] border border-[#BFE6CD] bg-match-100 p-3">
        <flux:icon.check-circle class="size-5 flex-none text-match" />
        <div><b class="block text-[13px] text-match">CV guardado</b><span class="text-[12px] text-gray-600">Al elegir otro PDF reemplazarás el archivo actual.</span></div>
    </div>
@endif

<p class="text-[13px] leading-relaxed text-gray-500">Las empresas podrán acceder al CV cuando tengan acceso autorizado a tu perfil.</p>
