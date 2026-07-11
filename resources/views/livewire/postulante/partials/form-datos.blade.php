<fieldset class="rounded-[14px] border border-line-2 p-5">
    <div class="mb-4"><legend class="text-[14px] font-extrabold text-ink">Datos personales</legend></div>
    <div class="grid gap-4 md:grid-cols-2">
        <flux:input wire:model="nombres" label="Nombres *" maxlength="50" />
        <flux:input wire:model="apellidos" label="Apellidos *" maxlength="50" />
        <flux:field class="md:col-span-2 md:max-w-md">
            <flux:label>{{ $tipoDocumento === 'pasaporte' ? 'Pasaporte *' : 'RUN *' }}</flux:label>
            <div class="flex items-start gap-2">
                <div class="w-[130px] shrink-0">
                    <flux:select wire:model.live="tipoDocumento">
                        <flux:select.option value="rut">RUN</flux:select.option>
                        <flux:select.option value="pasaporte">Pasaporte</flux:select.option>
                    </flux:select>
                </div>
                <flux:input
                    wire:model.blur.live="rut"
                    class="flex-1"
                    placeholder="{{ $tipoDocumento === 'pasaporte' ? 'Ej. AB1234567' : '12.345.678-5' }}"
                    inputmode="text"
                    autocomplete="off"
                />
            </div>
            @if ($tipoDocumento === 'rut')
                <flux:description>Puedes escribirlo sin puntos ni guion; lo formatearemos automáticamente.</flux:description>
            @endif
            <flux:error name="rut" />
        </flux:field>
    </div>
</fieldset>

<fieldset class="rounded-[14px] border border-line-2 p-5">
    <div class="mb-4"><legend class="text-[14px] font-extrabold text-ink">Datos de contacto</legend></div>
    <div class="grid gap-4 md:grid-cols-2">
        <flux:input wire:model="email" type="email" label="Email *" />
        <x-input-telefono wire:model="telefono" label="Teléfono *" />
        <flux:input wire:model="linkedin" type="url" label="LinkedIn" maxlength="100" placeholder="https://linkedin.com/in/..." />
        <flux:input wire:model="sitioWeb" type="url" label="Web / portafolio" maxlength="100" placeholder="https://..." />
    </div>
</fieldset>

<fieldset class="rounded-[14px] border border-line-2 p-5">
    <div class="mb-4"><legend class="text-[14px] font-extrabold text-ink">Información adicional</legend></div>
    <div class="grid gap-4 md:grid-cols-2">
        <flux:select wire:model="nacionalidad" label="Nacionalidad *">
            @foreach ($nacionalidades as $opcion)<flux:select.option :value="$opcion">{{ $opcion }}</flux:select.option>@endforeach
        </flux:select>
        <flux:input wire:model="anioNacimiento" type="number" min="1900" max="{{ now()->year }}" label="Año de nacimiento *" />
        <flux:input wire:model="aniosExperiencia" type="number" min="0" max="80" step="1" label="Años de experiencia *" />
        <flux:select wire:model="genero" label="Género *">
            <flux:select.option value="">Selecciona una opción</flux:select.option>
            @foreach ($generos as $opcion)<flux:select.option :value="$opcion">{{ $opcion }}</flux:select.option>@endforeach
        </flux:select>
        <flux:select wire:model="ciudad" label="Región *">
            <flux:select.option value="">Selecciona una región</flux:select.option>
            @foreach ($regiones as $opcion)<flux:select.option :value="$opcion">{{ $opcion }}</flux:select.option>@endforeach
        </flux:select>
    </div>
</fieldset>
