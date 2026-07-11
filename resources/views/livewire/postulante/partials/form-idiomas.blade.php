@foreach ($idiomas as $index => $idioma)
    <fieldset class="rounded-[14px] border border-line-2 p-5" wire:key="idioma-{{ $index }}">
        <div class="mb-4 flex items-center justify-between gap-3"><legend class="font-bold">Idioma {{ $index + 1 }}</legend>@if (count($idiomas) === 1)<span class="ad-chip ad-chip-orange">Obligatorio</span>@else<button type="button" wire:click="removeIdioma({{ $index }})" class="inline-flex items-center gap-1 text-[13px] font-bold text-[#A93226] dark:text-red-400"><flux:icon.trash class="size-4" />Quitar</button>@endif</div>
        <div class="grid gap-4 md:grid-cols-2">
            <flux:select wire:model="idiomas.{{ $index }}.idioma" label="Idioma *">
                <flux:select.option value="">Selecciona un idioma</flux:select.option>
                @foreach ($idiomasDisponibles as $opcion)<flux:select.option :value="$opcion">{{ $opcion }}</flux:select.option>@endforeach
            </flux:select>
            <flux:select wire:model="idiomas.{{ $index }}.nivel" label="Nivel *">
                <flux:select.option value="">Selecciona un nivel</flux:select.option>
                @foreach ($nivelesIdioma as $opcion)<flux:select.option :value="$opcion">{{ $opcion }}</flux:select.option>@endforeach
            </flux:select>
        </div>
    </fieldset>
@endforeach
