<div class="ad-panel">
    <x-slot:context>Postulante</x-slot:context>
    <x-slot:nav>
        <a wire:navigate href="{{ route('postulante.panel') }}" class="rounded-lg px-3.5 py-2 text-[13.5px] font-semibold text-gray-500 hover:text-ink">Mi panel</a>
        <a wire:navigate href="{{ route('postulante.ficha') }}" class="rounded-lg px-3.5 py-2 text-[13.5px] font-semibold text-gray-500 hover:text-ink">Mi perfil</a>
        <a wire:navigate href="{{ route('postulante.busquedas') }}" class="rounded-lg bg-orange-100 px-3.5 py-2 text-[13.5px] font-semibold text-ink">Oportunidades</a>
    </x-slot:nav>

    @if (session('status'))
        <div class="mb-5 rounded-xl border border-[#BFE6CD] bg-match-100 px-4 py-3 text-[13px] font-bold text-match">{{ session('status') }}</div>
    @endif

    <div class="mb-6">
        <span class="ad-eyebrow">Portal laboral</span>
        <h1 class="mt-3 text-[30px] font-extrabold">Oportunidades para tu experiencia</h1>
        <p class="mt-2 text-[14px] text-gray-500">Explora publicaciones vigentes y postula directamente con tu perfil AD+50.</p>
    </div>

    <section class="ad-card mb-6 p-5">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <flux:input wire:model.live.debounce.300ms="buscar" label="Buscar" placeholder="Cargo o palabra clave" icon="magnifying-glass" />
            <flux:select wire:model.live="modalidad" label="Modalidad">
                <flux:select.option value="">Todas</flux:select.option>
                @foreach (['Presencial', 'Híbrida', 'Remota'] as $opcion)<flux:select.option :value="$opcion">{{ $opcion }}</flux:select.option>@endforeach
            </flux:select>
            <flux:input wire:model.live.debounce.300ms="comuna" label="Comuna" placeholder="Ej. Concepción" />
            <flux:select wire:model.live="actividad" label="Actividad">
                <flux:select.option value="">Todas</flux:select.option>
                @foreach ($actividades as $opcion)<flux:select.option :value="$opcion">{{ $opcion }}</flux:select.option>@endforeach
            </flux:select>
        </div>
    </section>

    <div class="space-y-4">
        @forelse ($publicaciones as $publicacion)
            <article wire:key="publicacion-{{ $publicacion->id }}" class="ad-card p-5 md:p-6">
                <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-start">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="ad-chip ad-chip-orange">{{ $publicacion->modalidad }}</span>
                            @if ($publicacion->empleo_inclusivo)<span class="ad-chip ad-chip-green">Empleo inclusivo</span>@endif
                            <span class="text-[12px] text-gray-400">Publicado {{ $publicacion->created_at->diffForHumans() }}</span>
                        </div>
                        <h2 class="mt-3 text-[22px] font-extrabold text-ink">{{ $publicacion->cargo }}</h2>
                        <p class="mt-1 text-[14px] font-semibold text-gray-600">{{ $publicacion->nombre_empresa }} · {{ $publicacion->comuna }}, {{ $publicacion->pais }}</p>
                        <p class="mt-4 line-clamp-3 text-[14px] leading-relaxed text-gray-600">{{ $publicacion->descripcion }}</p>
                        <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-[13px] text-gray-500">
                            <span><b class="text-ink">{{ $publicacion->tipo_cargo }}</b></span>
                            <span>{{ $publicacion->jerarquia }}</span>
                            <span>{{ $publicacion->experiencia_laboral }}</span>
                            @if ($publicacion->mostrar_sueldo && $publicacion->sueldo)
                                <span class="font-bold text-match">${{ number_format($publicacion->sueldo, 0, ',', '.') }} líquidos aprox.</span>
                            @endif
                        </div>
                        @if ($publicacion->competencias)
                            <div class="mt-4 flex flex-wrap gap-2">
                                @foreach ($publicacion->competencias as $competencia)<span wire:key="competencia-{{ $publicacion->id }}-{{ $loop->index }}" class="ad-chip">{{ $competencia }}</span>@endforeach
                            </div>
                        @endif
                    </div>
                    <div class="flex min-w-44 flex-col items-stretch gap-2 lg:items-end">
                        @if ($publicacion->postulada)
                            <span class="ad-chip ad-chip-green justify-center"><flux:icon.check class="size-4" />Postulación enviada</span>
                        @else
                            <button type="button" wire:click="abrirPostulacion({{ $publicacion->id }})" class="ad-btn-primary ad-btn-sm justify-center">Postular</button>
                        @endif
                        <span class="text-center text-[12px] text-gray-400 lg:text-right">Vigente hasta {{ $publicacion->vigente_hasta->translatedFormat('d M Y') }}</span>
                    </div>
                </div>
            </article>
        @empty
            <div class="ad-card p-10 text-center">
                <flux:icon.magnifying-glass class="mx-auto size-8 text-gray-400" />
                <h2 class="mt-3 font-bold">No encontramos publicaciones</h2>
                <p class="mt-2 text-[13px] text-gray-500">Prueba quitando algunos filtros o vuelve más adelante.</p>
            </div>
        @endforelse
    </div>

    @if ($publicaciones->hasPages())
        <div class="mt-6">{{ $publicaciones->links() }}</div>
    @endif

    <flux:modal name="postular-publicacion" class="max-w-2xl" wire:close="$set('postulandoId', null)">
        @if ($publicacionSeleccionada)
            <form wire:submit="postular" class="space-y-5">
                <div>
                    <flux:heading size="lg">Postular a {{ $publicacionSeleccionada->cargo }}</flux:heading>
                    <flux:text class="mt-1">{{ $publicacionSeleccionada->nombre_empresa }} · {{ $publicacionSeleccionada->comuna }}</flux:text>
                </div>

                <div class="rounded-xl bg-paper p-4">
                    <p class="text-[13px] font-bold text-ink">Requisitos principales</p>
                    <p class="mt-2 whitespace-pre-line text-[13px] leading-relaxed text-gray-600">{{ $publicacionSeleccionada->requisitos }}</p>
                </div>

                @foreach ($publicacionSeleccionada->preguntas ?? [] as $index => $pregunta)
                    <flux:textarea wire:key="respuesta-{{ $index }}" wire:model="respuestas.{{ $index }}" :label="$pregunta.' *'" rows="3" maxlength="1000" />
                @endforeach

                @if (empty($publicacionSeleccionada->preguntas))
                    <flux:text>Tu perfil profesional y currículum serán enviados a la empresa.</flux:text>
                @endif

                <div class="flex justify-end gap-3">
                    <flux:modal.close><flux:button variant="ghost">Cancelar</flux:button></flux:modal.close>
                    <button type="submit" class="ad-btn-primary ad-btn-sm" wire:loading.attr="disabled" wire:target="postular">
                        <span wire:loading.remove wire:target="postular">Enviar postulación</span>
                        <span wire:loading wire:target="postular">Enviando…</span>
                    </button>
                </div>
            </form>
        @endif
    </flux:modal>
</div>
