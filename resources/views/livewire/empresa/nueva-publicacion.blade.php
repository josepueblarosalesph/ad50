<div class="ad-panel">
    <x-slot:context>Empresa</x-slot:context>
    <x-slot:nav><x-nav-empresa activo="publicaciones" /></x-slot:nav>

    <x-slot:sidebar><x-nav-publicaciones :activo="$editando ? 'editar' : 'nueva'" :publicacion="$publicacion" /></x-slot:sidebar>

    <div class="mx-auto max-w-5xl">
        <div class="mb-6">
            <a wire:navigate href="{{ $editando ? route('empresa.publicaciones.show', $publicacion) : route('empresa.publicaciones.index') }}" class="mb-4 inline-flex items-center gap-2 text-[13px] font-bold text-gray-500 hover:text-ink"><flux:icon.arrow-left class="size-4" />{{ $editando ? 'Volver a la publicación' : 'Volver a publicaciones' }}</a>
            <span class="ad-eyebrow">{{ $editando ? 'Editar oportunidad' : 'Nueva oportunidad' }}</span>
            <h1 class="mt-3 text-[30px] font-extrabold">{{ $editando ? 'Editar oferta laboral' : 'Publicar oferta laboral' }}</h1>
            <p class="mt-2 text-[14px] text-gray-500">{{ $editando ? 'Los cambios se reflejan de inmediato en el portal de postulantes.' : 'La oferta quedará visible en el portal de postulantes durante el período seleccionado.' }}</p>
        </div>

        <form wire:submit="guardar" class="space-y-6">
            {{-- Los id anclan los botones "Editar" del detalle a la tarjeta correspondiente. --}}
            <section id="descripcion-general" class="ad-card scroll-mt-6">
                <div class="ad-card-head"><div><h2 class="text-[19px] font-extrabold">Descripción general</h2><p class="mt-1 text-[13px] text-gray-500">Información principal que verá el postulante.</p></div></div>
                <div class="grid gap-4 p-6 md:grid-cols-2">
                    <flux:input wire:model="cargo" label="Nombre del cargo *" maxlength="100" />
                    <flux:select wire:model="tipoCargo" label="Tipo de cargo *">
                        <flux:select.option value="">Seleccionar</flux:select.option>
                        @foreach ($tiposCargo as $opcion)<flux:select.option :value="$opcion">{{ $opcion }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:input wire:model="vacantes" type="number" min="1" max="100" label="Cantidad de vacantes *" />
                    <flux:input value="{{ auth()->user()->empresa->razon_social }}" label="Nombre de la empresa" readonly />
                    <div class="md:col-span-2">
                        <flux:textarea wire:model="descripcion" label="Descripción de la vacante *" rows="8" maxlength="8000" placeholder="Detalla el rol, sus funciones, desafíos y la propuesta de valor de la oportunidad." />
                        <p class="mt-1 text-[12px] text-gray-400">Mínimo 150 y máximo 8.000 caracteres.</p>
                    </div>
                    <flux:select wire:model="modalidad" label="Modalidad de trabajo *">
                        <flux:select.option value="">Seleccionar</flux:select.option>
                        @foreach (['Presencial', 'Híbrida', 'Remota'] as $opcion)<flux:select.option :value="$opcion">{{ $opcion }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:input wire:model="pais" label="País *" />
                    <flux:input wire:model="comuna" label="Comuna *" />
                    <flux:select wire:model="actividadEmpresa" label="Actividad de la empresa *">
                        <flux:select.option value="">Seleccionar</flux:select.option>
                        @foreach ($actividades as $opcion)<flux:select.option :value="$opcion">{{ $opcion }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="jerarquia" label="Jerarquía *">
                        <flux:select.option value="">Seleccionar</flux:select.option>
                        @foreach ($jerarquias as $opcion)<flux:select.option :value="$opcion">{{ $opcion }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:input wire:model="sueldo" type="number" min="100000" step="50000" label="Sueldo líquido mensual aproximado (CLP)" />
                    <div class="flex items-end pb-2"><flux:switch wire:model="mostrarSueldo" label="Mostrar sueldo en la oferta" /></div>
                </div>
            </section>

            <section id="requisitos" class="ad-card scroll-mt-6">
                <div class="ad-card-head"><div><h2 class="text-[19px] font-extrabold">Requisitos</h2><p class="mt-1 text-[13px] text-gray-500">Características mínimas del perfil requerido.</p></div></div>
                <div class="grid gap-4 p-6 md:grid-cols-2">
                    <div class="md:col-span-2"><flux:textarea wire:model="requisitos" label="Descripción de requisitos *" rows="6" maxlength="1000" /></div>
                    <flux:select wire:model="experienciaLaboral" label="Experiencia laboral *">
                        <flux:select.option value="">Seleccionar</flux:select.option>
                        @foreach ($experiencias as $opcion)<flux:select.option :value="$opcion">{{ $opcion }}</flux:select.option>@endforeach
                    </flux:select>
                    <div></div>
                    <flux:select wire:model="estudiosMinimos" label="Estudios mínimos *">
                        <flux:select.option value="">Seleccionar</flux:select.option>
                        @foreach ($estudios as $opcion)<flux:select.option :value="$opcion">{{ $opcion }}</flux:select.option>@endforeach
                    </flux:select>
                    <flux:select wire:model="situacionAcademica" label="Situación académica *">
                        <flux:select.option value="">Seleccionar</flux:select.option>
                        @foreach ($situacionesAcademicas as $opcion)<flux:select.option :value="$opcion">{{ $opcion }}</flux:select.option>@endforeach
                    </flux:select>
                    <div class="md:col-span-2"><flux:input wire:model="competenciasTexto" label="Competencias / habilidades" placeholder="Ej. Liderazgo, Excel, gestión de proyectos" description="Separa las competencias con comas." /></div>
                    <div class="md:col-span-2">
                        <flux:label>Idiomas</flux:label>
                        <div class="mt-2 grid gap-2 rounded-[14px] border border-line-2 p-4 sm:grid-cols-2 md:grid-cols-3">
                            @foreach ($idiomasDisponibles as $idioma)
                                <flux:checkbox wire:key="idioma-{{ $idioma }}" wire:model="idiomas" :value="$idioma" :label="$idioma" />
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            <section id="preguntas" class="ad-card scroll-mt-6">
                <div class="ad-card-head flex-wrap gap-3">
                    <div><h2 class="text-[19px] font-extrabold">Preguntas generales</h2><p class="mt-1 text-[13px] text-gray-500">Los postulantes deberán responderlas antes de enviar su postulación.</p></div>
                    <button type="button" wire:click="agregarPregunta" class="ad-btn-ghost ad-btn-sm"><flux:icon.plus class="size-4" />Agregar pregunta</button>
                </div>
                <div class="space-y-3 p-6">
                    @forelse ($preguntas as $index => $pregunta)
                        <div wire:key="pregunta-{{ $index }}" class="flex items-start gap-3">
                            <flux:input wire:model="preguntas.{{ $index }}" class="flex-1" label="Pregunta {{ $index + 1 }}" maxlength="300" />
                            <button type="button" wire:click="quitarPregunta({{ $index }})" class="mt-7 rounded-lg p-2 text-red-600 hover:bg-red-50" aria-label="Eliminar pregunta {{ $index + 1 }}"><flux:icon.trash class="size-4" /></button>
                        </div>
                    @empty
                        <p class="text-[13px] text-gray-500">No agregaste preguntas adicionales.</p>
                    @endforelse
                </div>
            </section>

            <section id="configuraciones" class="ad-card scroll-mt-6">
                <div class="ad-card-head"><div><h2 class="text-[19px] font-extrabold">Configuraciones</h2><p class="mt-1 text-[13px] text-gray-500">Controla la publicación y las notificaciones.</p></div></div>
                <div class="grid gap-5 p-6 md:grid-cols-2">
                    <flux:switch wire:model="empleoInclusivo" label="Empleo inclusivo" />
                    <flux:switch wire:model="postulacionFacil" label="Postulación fácil" />
                    <flux:switch wire:model="notificarPostulaciones" label="Recibir notificación de postulación" />
                    <div>
                        <flux:select wire:model="vigenciaDias" label="Vigencia de publicación *">
                            @foreach ([15, 30, 60, 90] as $dias)<flux:select.option :value="$dias">{{ $dias }} días</flux:select.option>@endforeach
                        </flux:select>
                        @if ($editando)
                            <p class="mt-1 text-[12px] text-gray-400">Vigente hasta el {{ $publicacion->vigente_hasta->translatedFormat('d M Y') }}. Si cambias este valor, la vigencia se recalcula desde hoy.</p>
                        @endif
                    </div>
                </div>
            </section>

            <section id="evaluacion" class="ad-card scroll-mt-6">
                <div class="ad-card-head"><div><h2 class="text-[19px] font-extrabold">Evaluación online para postulantes</h2><p class="mt-1 text-[13px] text-gray-500">Configura si esta oferta utilizará evaluación online.</p></div></div>
                <div class="space-y-5 p-6">
                    <flux:switch wire:model.live="evaluacionOnline" label="Activar y configurar las evaluaciones" />
                    @if ($evaluacionOnline)
                        <flux:switch wire:model="evaluacionManual" label="Evaluar manualmente a los postulantes" />
                        <p class="rounded-xl border border-orange-200 bg-orange-50 p-4 text-[13px] leading-relaxed text-gray-700">La configuración específica del test se coordina con tu ejecutivo AD+50.</p>
                    @endif
                </div>
            </section>

            <div class="ad-card flex flex-wrap items-center justify-between gap-4 p-5">
                <p class="max-w-xl text-[13px] text-gray-500">Al publicar declaras que la información es verídica y cumple la legislación laboral vigente.</p>
                <div class="flex gap-3">
                    <a wire:navigate href="{{ $editando ? route('empresa.publicaciones.show', $publicacion) : route('empresa.publicaciones.index') }}" class="ad-btn-ghost ad-btn-sm">Cancelar</a>
                    <button type="submit" class="ad-btn-primary ad-btn-sm" wire:loading.attr="disabled" wire:target="guardar">
                        <span wire:loading.remove wire:target="guardar">{{ $editando ? 'Guardar cambios' : 'Publicar oportunidad' }}</span>
                        <span wire:loading wire:target="guardar">{{ $editando ? 'Guardando…' : 'Publicando…' }}</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
