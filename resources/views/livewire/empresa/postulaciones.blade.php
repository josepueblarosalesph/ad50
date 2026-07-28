<div>
    <x-slot:context>Empresa</x-slot:context>
    <x-slot:nav><x-nav-empresa activo="publicaciones" /></x-slot:nav>
    <x-slot:sidebar>
        <div class="sticky top-24">
            <livewire:empresa.filtros-postulaciones wire:key="filtros-postulaciones-desktop" />
        </div>
    </x-slot:sidebar>

    <div>
        <a wire:navigate href="{{ route('empresa.publicaciones.index') }}" class="ad-btn-ghost ad-btn-sm mb-4 inline-flex items-center gap-2">
            <flux:icon.arrow-left class="size-4" />
            Volver a Publicaciones
        </a>

        {{-- Filtros en móvil --}}
        <details class="group mb-4 rounded-xl border border-line-2 bg-white dark:bg-[#222528] md:hidden">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-2 px-4 py-3 text-[14px] font-bold text-ink">
                <span class="inline-flex items-center gap-2"><flux:icon.funnel class="size-4 text-orange-500" />Filtrar postulantes</span>
                <flux:icon.chevron-down class="size-4 text-gray-400 transition group-open:rotate-180" />
            </summary>
            <div class="border-t border-line px-3 pb-3 pt-3">
                <livewire:empresa.filtros-postulaciones lazy wire:key="filtros-postulaciones-movil" />
            </div>
        </details>

        <div class="mb-5">
            <h1 class="text-[25px] font-extrabold">{{ $publicacion->cargo }}</h1>
            <p class="mt-1.5 text-[14px] text-gray-500">{{ $totalPostulaciones }} {{ $totalPostulaciones === 1 ? 'persona postuló' : 'personas postularon' }} a esta publicación.</p>
        </div>

        {{-- Filtro por estado --}}
        <div class="mb-5 flex flex-wrap gap-2">
            @php($estadoChips = array_merge(['todas' => 'Todas'], $estados))
            @foreach ($estadoChips as $valor => $etiqueta)
                @php($conteo = $valor === 'todas' ? $totalPostulaciones : (int) ($conteoPorEstado[$valor] ?? 0))
                <button type="button" wire:click="mostrarEstado('{{ $valor }}')" @class([
                    'inline-flex items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-[13px] font-bold transition',
                    'border-orange-300 bg-orange-100 text-orange-700' => $estado === $valor,
                    'border-line-2 bg-white text-gray-500 hover:text-ink dark:bg-[#222528]' => $estado !== $valor,
                ])>
                    {{ $etiqueta }} <span class="opacity-70">{{ $conteo }}</span>
                </button>
            @endforeach
        </div>

        @if ($criterios !== null && $totalFiltradas < $totalPostulaciones)
            <p class="mb-4 text-[13px] text-gray-500">Mostrando <b class="text-ink">{{ $totalFiltradas }}</b> que cumplen los filtros seleccionados.</p>
        @endif

        <div class="space-y-3">
            @forelse ($postulaciones as $postulacion)
                @php($postulante = $postulacion->postulante)
                @php($ultimaExp = $postulante?->ultimaExperiencia())
                <article wire:key="postulacion-{{ $postulacion->id }}" class="ad-card overflow-hidden p-4 md:p-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="flex min-w-0 items-start gap-4">
                            <div class="grid size-12 flex-none place-items-center rounded-[12px] bg-sage-100 text-ink" aria-hidden="true"><flux:icon.user class="size-5" /></div>
                            <div class="min-w-0">
                                <p class="truncate text-[11px] font-extrabold uppercase tracking-[.14em] text-gray-400">{{ $postulante?->carrera ?: 'Carrera no informada' }}</p>
                                <h2 class="mt-0.5 truncate text-[19px] font-extrabold text-ink">{{ $postulante?->user?->name ?? 'Postulante' }}</h2>
                                @if ($ultimaExp)
                                    <p class="mt-1 flex items-center gap-1.5 text-[13px] text-gray-600 dark:text-gray-300">
                                        <flux:icon.briefcase class="size-3.5 flex-none text-gray-400" />
                                        <span class="truncate"><span class="font-semibold text-ink">{{ $ultimaExp['cargo'] }}</span>@if ($ultimaExp['empresa']) · {{ $ultimaExp['empresa'] }}@endif</span>
                                    </p>
                                @endif
                                <p class="mt-1 text-[11.5px] text-gray-400">Postuló el {{ $postulacion->created_at->translatedFormat('d M Y') }}</p>
                            </div>
                        </div>

                        {{-- Estado de la postulación --}}
                        <select
                            wire:key="estado-{{ $postulacion->id }}"
                            wire:change="cambiarEstado({{ $postulacion->id }}, $event.target.value)"
                            aria-label="Estado de la postulación de {{ $postulante?->user?->name }}"
                            @class([
                                'rounded-lg border px-2.5 py-1.5 text-[13px] font-bold focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500',
                                'border-[#BFE6CD] bg-match-100 text-match' => $postulacion->estado === 'seleccionada',
                                'border-[#E7B6AE] bg-[#FBEDEA] text-[#A93226]' => $postulacion->estado === 'descartada',
                                'border-line-2 bg-paper text-gray-600' => ! in_array($postulacion->estado, ['seleccionada', 'descartada'], true),
                            ])
                        >
                            @foreach ($estados as $valor => $etiqueta)
                                <option value="{{ $valor }}" @selected($postulacion->estado === $valor)>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if ($postulante?->resumen_profesional)
                        <p class="mt-3 max-w-4xl text-[13px] leading-relaxed text-gray-500">{{ Str::limit($postulante->resumen_profesional, 220, '…') }}</p>
                    @endif

                    {{-- Contacto (visible sin desbloquear, por ser postulación directa) --}}
                    <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1.5 text-[12.5px] text-gray-600 dark:text-gray-300">
                        @if ($postulante?->rut)<span class="inline-flex items-center gap-1.5"><flux:icon.identification class="size-4 text-gray-400" />{{ $postulante->rut }}</span>@endif
                        @if ($postulante?->telefono)<span class="inline-flex items-center gap-1.5"><flux:icon.phone class="size-4 text-gray-400" />{{ $postulante->telefono }}</span>@endif
                        @if ($postulante?->user?->email)<span class="inline-flex items-center gap-1.5"><flux:icon.envelope class="size-4 text-gray-400" />{{ $postulante->user->email }}</span>@endif
                        @if ($postulante?->linkedin)<a href="{{ $postulante->linkedin }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 font-semibold text-orange-600 hover:text-orange-700"><flux:icon.link class="size-4" />LinkedIn</a>@endif
                    </div>

                    {{-- Respuestas al cuestionario --}}
                    @if (filled($publicacion->preguntas))
                        <div class="mt-4 space-y-2.5 rounded-xl border border-line-2 bg-paper p-4 dark:bg-[#222528]">
                            <p class="text-[11px] font-extrabold uppercase tracking-wide text-gray-400">Respuestas</p>
                            @foreach ($publicacion->preguntas as $i => $pregunta)
                                <div>
                                    <p class="text-[12.5px] font-bold text-ink">{{ $pregunta }}</p>
                                    <p class="mt-0.5 text-[13px] leading-relaxed text-gray-600 dark:text-gray-300">{{ $postulacion->respuestas[$i] ?? '—' }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-line pt-3">
                        @if ($postulante?->cv_ruta)
                            <button type="button" wire:click="descargarCv({{ $postulacion->id }})" wire:loading.attr="disabled" wire:target="descargarCv({{ $postulacion->id }})" class="ad-btn-primary ad-btn-sm">
                                <flux:icon.arrow-down-tray class="size-4" />
                                <span wire:loading.remove wire:target="descargarCv({{ $postulacion->id }})">Descargar CV</span>
                                <span wire:loading wire:target="descargarCv({{ $postulacion->id }})">Preparando…</span>
                            </button>
                        @else
                            <span class="text-[12.5px] text-gray-400">Sin CV adjunto</span>
                        @endif
                    </div>
                </article>
            @empty
                <div class="ad-card p-10 text-center">
                    <flux:icon.users class="mx-auto size-8 text-gray-400" />
                    <h2 class="mt-3 font-bold">{{ ($criterios !== null || $estado !== 'todas') ? 'Ninguna postulación cumple este filtro' : 'Aún no hay postulaciones' }}</h2>
                    <p class="mt-2 text-[13px] text-gray-500">{{ ($criterios !== null || $estado !== 'todas') ? 'Ajusta los filtros para ver más postulantes.' : 'Cuando alguien postule a esta publicación, aparecerá aquí.' }}</p>
                </div>
            @endforelse
        </div>

        @if ($postulaciones->hasPages())
            <div class="mt-6">{{ $postulaciones->links() }}</div>
        @endif
    </div>
</div>
