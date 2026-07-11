    <div class="grid gap-4 md:grid-cols-2">
        <div class="md:col-span-2">
            <flux:input wire:model="titular" label="Titular" maxlength="100" placeholder="Ej. Gerente de Finanzas con experiencia en transformación y crecimiento" description="Opcional, pero recomendado: mejora cómo apareces en las búsquedas. Máximo 100 caracteres." />
        </div>
        <div class="md:col-span-2">
            <flux:textarea wire:model="resumenProfesional" label="Escribe una breve presentación" maxlength="900" rows="5" placeholder="Resume el valor que aporta el conjunto de tu trayectoria." description="Máximo 900 caracteres." />
        </div>

        <div class="md:col-span-2">
            <x-selector-habilidades
                :habilidades="$habilidades"
                :sugerencias="$this->habilidadesSugeridas()"
                :buscar="$buscarHabilidad"
            />
        </div>

        <x-selector-colapsable
            titulo="Regiones de interés"
            descripcion="Marca hasta 5 alternativas ({{ count($regionesInteres) }} de 5)."
            :seleccion="$regionesInteres"
            error="regionesInteres"
        >
            <flux:checkbox.group wire:model.live="regionesInteres">
                <div class="max-h-44 space-y-1.5 overflow-y-auto pr-2">
                    @foreach ($regiones as $opcion)<flux:checkbox wire:key="region-{{ $loop->index }}" :value="$opcion" :label="$opcion" :disabled="count($regionesInteres) >= 5 && ! in_array($opcion, $regionesInteres, true)" />@endforeach
                </div>
            </flux:checkbox.group>
        </x-selector-colapsable>

        <x-selector-colapsable
            titulo="Industrias de interés"
            descripcion="Marca hasta 5 alternativas ({{ count($industriasInteres) }} de 5)."
            :seleccion="$industriasInteres"
            error="industriasInteres"
        >
            <flux:checkbox.group wire:model.live="industriasInteres">
                <div class="max-h-44 space-y-1.5 overflow-y-auto pr-2">
                    @foreach ($industrias as $opcion)<flux:checkbox wire:key="industria-{{ $loop->index }}" :value="$opcion" :label="$opcion" :disabled="count($industriasInteres) >= 5 && ! in_array($opcion, $industriasInteres, true)" />@endforeach
                </div>
            </flux:checkbox.group>
        </x-selector-colapsable>

        <x-selector-colapsable
            class="md:col-span-2"
            titulo="Modalidad preferida"
            descripcion="Puedes marcar varias alternativas."
            :seleccion="$modalidadesTrabajo"
            error="modalidadesTrabajo"
        >
            <flux:checkbox.group wire:model.live="modalidadesTrabajo">
                <div class="space-y-2">
                    @foreach ($modalidadesTrabajoPreferidas as $opcion)<flux:checkbox wire:key="modalidad-{{ $loop->index }}" :value="$opcion" :label="$opcion" />@endforeach
                </div>
            </flux:checkbox.group>
        </x-selector-colapsable>

        <flux:select wire:model="situacionLaboral" label="Situación laboral">
            <flux:select.option value="">Selecciona una situación</flux:select.option>
            @foreach ($situacionesLaborales as $opcion)<flux:select.option :value="$opcion">{{ $opcion }}</flux:select.option>@endforeach
        </flux:select>
        <flux:input wire:model="expectativaRenta" type="number" min="0" step="1" label="Expectativa de renta" placeholder="Ej. 2500000" description="Monto en CLP — renta líquida mensual." />
    </div>
