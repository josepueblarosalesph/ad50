{{-- Popup para crear una carpeta, abierto desde el "+" de la cabecera del panel.

     Va aquí y no dentro del parcial de carpetas porque ese se incluye dos veces (barra
     lateral y plegable de móvil) y el modal quedaría duplicado en el DOM. --}}
<flux:modal name="nueva-carpeta" class="max-w-sm" wire:close="cerrarNuevaCarpeta">
    <form wire:submit="crearCarpeta" class="space-y-5">
        <div>
            <flux:heading size="lg">Nueva carpeta</flux:heading>
            <flux:text class="mt-1.5">Agrupa a tus candidatos guardados como te sirva. Solo tú ves tus carpetas.</flux:text>
        </div>

        <div>
            <label for="nueva-carpeta-nombre" class="mb-1.5 block text-[13px] font-bold text-gray-700 dark:text-gray-300">Nombre</label>
            <input
                type="text"
                id="nueva-carpeta-nombre"
                wire:model="nuevaCarpeta"
                maxlength="40"
                autofocus
                placeholder="Ej. Finanzas senior"
                class="w-full rounded-lg border border-line-2 bg-white px-3 py-2.5 text-[14px] font-semibold text-ink placeholder:font-normal placeholder:text-gray-400 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500 dark:bg-[#222528]"
            />
            @error('nuevaCarpeta')<p class="mt-1.5 text-[12.5px] font-semibold text-[#A93226]">{{ $message }}</p>@enderror
        </div>

        <div class="flex justify-end gap-2">
            <flux:button type="button" wire:click="cerrarNuevaCarpeta" variant="ghost">Cancelar</flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="crearCarpeta">Crear carpeta</flux:button>
        </div>
    </form>
</flux:modal>
