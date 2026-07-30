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
                <article wire:key="postulacion-{{ $postulacion->id }}" class="ad-card p-3.5 md:p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex min-w-0 flex-1 items-center gap-3">
                            <div class="grid size-10 flex-none place-items-center rounded-[11px] bg-sage-100 text-ink" aria-hidden="true"><flux:icon.user class="size-5" /></div>
                            <div class="min-w-0">
                                {{-- El nombre abre el perfil completo sin salir del listado. Mientras
                                     viaja la petición se marca la fila como ocupada y aparece el
                                     indicador, para que el clic no parezca no haber hecho nada. --}}
                                <button
                                    type="button"
                                    wire:click="verDetalle({{ $postulacion->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="verDetalle({{ $postulacion->id }})"
                                    x-bind:aria-busy="$wire.detalleId === {{ $postulacion->id }} ? 'false' : null"
                                    class="flex max-w-full items-center gap-2 rounded text-left text-[15px] font-extrabold text-ink transition hover:text-orange-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-600 disabled:opacity-60"
                                >
                                    <span class="truncate underline decoration-orange-300 underline-offset-4 group-hover:decoration-orange-600">{{ $postulante?->user?->name ?? 'Postulante' }}</span>
                                    <x-spinner
                                        wire:loading
                                        wire:target="verDetalle({{ $postulacion->id }})"
                                        class="size-4 flex-none text-orange-500"
                                    />
                                </button>
                                <p class="mt-0.5 truncate text-[12.5px] text-gray-500">
                                    {{ collect([
                                        $ultimaExp['cargo'] ?? $postulante?->cargo_actual,
                                        $postulante?->carrera,
                                        $postulante?->anios_experiencia ? $postulante->anios_experiencia.' años' : null,
                                    ])->filter()->implode(' · ') ?: 'Sin experiencia informada' }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-none flex-wrap items-center gap-2">
                            <span class="hidden text-[12px] text-gray-400 sm:inline">{{ $postulacion->created_at->translatedFormat('d M Y') }}</span>

                            @if ($postulante?->cv_ruta)
                                <flux:tooltip content="Descargar CV">
                                    <button type="button" wire:click="descargarCv({{ $postulacion->id }})" wire:loading.attr="disabled" wire:target="descargarCv({{ $postulacion->id }})" class="grid size-9 flex-none place-items-center rounded-lg border border-line-2 bg-white text-gray-500 transition hover:border-orange-300 hover:text-orange-600 disabled:opacity-50 dark:bg-[#2A2D30]" aria-label="Descargar CV de {{ $postulante?->user?->name }}">
                                        <flux:icon.arrow-down-tray class="size-4" />
                                    </button>
                                </flux:tooltip>
                            @endif

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

    {{-- Perfil completo del postulante: lo que antes estiraba cada tarjeta. --}}
    <flux:modal name="detalle-postulante" class="max-w-2xl" wire:close="cerrarDetalle">
        @if ($detalle)
            @php($p = $detalle->postulante)
            <div class="space-y-5">
                <div>
                    <p class="text-[11px] font-extrabold uppercase tracking-[.14em] text-gray-400">{{ $p?->carrera ?: 'Carrera no informada' }}</p>
                    <flux:heading size="lg">{{ $p?->user?->name ?? 'Postulante' }}</flux:heading>
                    <flux:text class="mt-1">
                        {{ collect([$p?->cargo_actual, $p?->empresa_actual, $p?->anios_experiencia ? $p->anios_experiencia.' años de experiencia' : null])->filter()->implode(' · ') ?: 'Sin experiencia informada' }}
                    </flux:text>
                    <p class="mt-1 text-[12px] text-gray-400">Postuló el {{ $detalle->created_at->translatedFormat('d M Y') }}</p>
                </div>

                {{-- Contacto: visible sin desbloquear, por tratarse de una postulación directa. --}}
                <div class="flex flex-wrap gap-x-4 gap-y-1.5 rounded-xl bg-paper p-4 text-[13px] text-gray-600 dark:bg-white/5 dark:text-gray-300">
                    @if ($p?->rut)<span class="inline-flex items-center gap-1.5"><flux:icon.identification class="size-4 text-gray-400" />{{ $p->rut }}</span>@endif
                    @if ($p?->telefono)<span class="inline-flex items-center gap-1.5"><flux:icon.phone class="size-4 text-gray-400" />{{ $p->telefono }}</span>@endif
                    @if ($p?->user?->email)<a href="mailto:{{ $p->user->email }}" class="inline-flex items-center gap-1.5 hover:text-orange-600"><flux:icon.envelope class="size-4 text-gray-400" />{{ $p->user->email }}</a>@endif
                    @if ($p?->linkedin)<a href="{{ $p->linkedin }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 hover:text-orange-600"><flux:icon.link class="size-4 text-gray-400" />LinkedIn</a>@endif
                </div>

                @if ($p?->resumen_profesional)
                    <div>
                        <p class="text-[11px] font-extrabold uppercase tracking-wide text-gray-400">Acerca de</p>
                        <p class="mt-1.5 text-[13.5px] leading-relaxed text-gray-600 dark:text-gray-300">{{ $p->resumen_profesional }}</p>
                    </div>
                @endif

                @if (filled($p?->experiencias))
                    <div>
                        <p class="text-[11px] font-extrabold uppercase tracking-wide text-gray-400">Experiencia</p>
                        <ul class="mt-1.5 space-y-2">
                            @foreach (array_slice($p->experiencias, 0, 4) as $exp)
                                <li class="text-[13.5px] text-gray-600 dark:text-gray-300">
                                    <b class="text-ink">{{ ($exp['cargo'] ?? '') === 'Otros' ? ($exp['cargo_otro'] ?? 'Otros') : ($exp['cargo'] ?? '—') }}</b>
                                    {{ filled($exp['empresa'] ?? null) ? ' · '.$exp['empresa'] : '' }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (filled($p?->habilidades))
                    <div>
                        <p class="text-[11px] font-extrabold uppercase tracking-wide text-gray-400">Habilidades</p>
                        <div class="mt-1.5 flex flex-wrap gap-1.5">
                            @foreach (array_slice($p->habilidades, 0, 12) as $habilidad)<span class="ad-tag">{{ $habilidad }}</span>@endforeach
                        </div>
                    </div>
                @endif

                @if (filled($publicacion->preguntas))
                    <div class="rounded-xl border border-line-2 p-4">
                        <p class="text-[11px] font-extrabold uppercase tracking-wide text-gray-400">Respuestas</p>
                        <div class="mt-2 space-y-3">
                            @foreach ($publicacion->preguntas as $i => $pregunta)
                                <div>
                                    <p class="text-[12.5px] font-bold text-ink">{{ $pregunta }}</p>
                                    <p class="mt-0.5 text-[13px] leading-relaxed text-gray-600 dark:text-gray-300">{{ $detalle->respuestas[$i] ?? '—' }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="flex justify-end gap-2 border-t border-line pt-4">
                    @if ($p?->cv_ruta)
                        <flux:button variant="ghost" wire:click="descargarCv({{ $detalle->id }})" icon="arrow-down-tray">Descargar CV</flux:button>
                    @endif
                    <flux:modal.close><flux:button variant="primary">Cerrar</flux:button></flux:modal.close>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
