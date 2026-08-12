<div class="ad-panel">
    <x-slot:context>Empresa</x-slot:context>
    <x-slot:nav><x-nav-empresa activo="favoritos" /></x-slot:nav>
    {{-- La barra lateral apila las carpetas (navegación) sobre el panel de criterios, que
         es el mismo componente que usan Prospección y el listado de una publicación: un
         solo formulario de filtros para las tres pantallas. --}}
    <x-slot:sidebar>
        <div class="sticky top-24 max-h-[calc(100vh-7rem)] space-y-3 overflow-y-auto pb-4">
            @if ($carpetasVisibles)
                <livewire:empresa.carpetas-favoritos :activa="$carpeta" wire:key="carpetas-escritorio" />
            @endif
            <livewire:empresa.filtros-postulaciones wire:key="filtros-favoritos-escritorio" />
        </div>
    </x-slot:sidebar>

    {{-- En móvil el sidebar del layout se oculta: los criterios van plegados. --}}
    <details class="group mb-4 rounded-xl border border-line-2 bg-white dark:bg-[#222528] md:hidden">
        <summary class="flex cursor-pointer list-none items-center justify-between gap-2 px-4 py-3 text-[14px] font-bold text-ink">
            <span class="inline-flex items-center gap-2"><flux:icon.funnel class="size-4 text-orange-500" />Filtrar por perfil</span>
            <flux:icon.chevron-down class="size-4 text-gray-400 transition group-open:rotate-180" />
        </summary>
        <div class="border-t border-line px-3 pb-3 pt-3">
            <livewire:empresa.filtros-postulaciones lazy wire:key="filtros-favoritos-movil" />
        </div>
    </details>

    @if ($carpetasVisibles)

        {{-- El sidebar del layout se oculta bajo md: en móvil las carpetas van plegadas. --}}
        <details class="group mb-4 rounded-xl border border-line-2 bg-white dark:bg-[#222528] md:hidden">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-2 px-4 py-3 text-[14px] font-bold text-ink">
                <span class="inline-flex items-center gap-2">
                    <flux:icon.folder class="size-4 text-orange-500" />
                    {{ $carpetaActiva?->nombre ?? ($carpeta === 'sin' ? 'Sin carpeta' : 'Mis carpetas') }}
                </span>
                <flux:icon.chevron-down class="size-4 text-gray-400 transition group-open:rotate-180" />
            </summary>
            <div class="border-t border-line px-3 pb-3 pt-3">
                <livewire:empresa.carpetas-favoritos :activa="$carpeta" wire:key="carpetas-movil" />
            </div>
        </details>
    @endif

    {{-- Título y filtros en la misma fila: los desplegables van a la derecha en vez de
         ocupar una tarjeta propia debajo. Bajo `sm` se envuelven y quedan bajo el título. --}}
    <div class="mb-5 flex flex-wrap items-end justify-between gap-x-4 gap-y-3">
        <div class="min-w-0">
            <h1 class="text-[30px] font-extrabold">{{ $carpetaActiva?->nombre ?? 'Mis favoritos' }}</h1>
            <p class="mt-2 text-[14px] text-gray-500">
                @if ($carpetaActiva)
                    {{ $carpetaActiva->favoritos_count }}
                    {{ $carpetaActiva->favoritos_count === 1 ? 'candidato agrupado' : 'candidatos agrupados' }}
                    en esta carpeta, de {{ $totalFavoritos }} guardados en tu cuenta.
                @elseif ($carpeta === 'sin')
                    {{ $sinCarpeta }} {{ $sinCarpeta === 1 ? 'candidato guardado aún sin carpeta' : 'candidatos guardados aún sin carpeta' }},
                    de {{ $totalFavoritos }} en tu cuenta.
                @else
                    {{ $totalFavoritos }} {{ $totalFavoritos === 1 ? 'candidato guardado' : 'candidatos guardados' }}
                    en tu cuenta.
                @endif
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
            <div class="flex items-center gap-2">
                <label for="filtro-publicacion" class="text-[12px] font-bold text-gray-500">Publicación</label>
                <select id="filtro-publicacion" wire:model.live="publicacion" @class([
                    'rounded-lg border px-2.5 py-1.5 text-[13px] font-bold focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500',
                    'border-orange-300 bg-orange-100 text-orange-700' => $publicacion !== 'todas',
                    'border-line-2 bg-white text-gray-600 dark:bg-[#222528]' => $publicacion === 'todas',
                ])>
                    <option value="todas">Todas</option>
                    <option value="sin">Sin publicación asociada</option>
                    @foreach ($publicacionesDisponibles as $id => $cargo)
                        <option value="{{ $id }}">{{ $cargo }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <label for="filtro-desbloqueo" class="text-[12px] font-bold text-gray-500">Perfil</label>
                <select id="filtro-desbloqueo" wire:model.live="desbloqueo" @class([
                    'rounded-lg border px-2.5 py-1.5 text-[13px] font-bold focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500',
                    'border-orange-300 bg-orange-100 text-orange-700' => $desbloqueo !== 'todos',
                    'border-line-2 bg-white text-gray-600 dark:bg-[#222528]' => $desbloqueo === 'todos',
                ])>
                    <option value="todos">Todos</option>
                    <option value="desbloqueados">Desbloqueados</option>
                    <option value="bloqueados">Sin desbloquear</option>
                </select>
            </div>
        </div>
    </div>

    @if (session('desbloqueo_error'))
        <div class="mb-4 flex items-center gap-2 rounded-xl border border-[#E7B6AE] bg-[#FBEDEA] px-4 py-3 text-[13px] font-semibold text-[#A93226] dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">
            <flux:icon.exclamation-triangle class="size-4 flex-none" />{{ session('desbloqueo_error') }}
        </div>
    @endif

    {{-- Solo el resumen de lo filtrado: los desplegables subieron a la fila del título. --}}
    @if ($hayFiltros)
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-line-2 bg-paper px-4 py-2.5 dark:bg-[#222528]">
            <p class="text-[13px] text-gray-500">
                Mostrando <b class="text-ink">{{ $candidatos->total() }}</b> de {{ $totalFavoritos }} favoritos.
            </p>
            <button type="button" wire:click="limpiarFiltros" class="ad-btn-ghost ad-btn-sm">Limpiar filtros</button>
        </div>
    @endif

    {{-- Listado: una fila por candidato --}}
    <div class="space-y-3">
        @forelse ($candidatos as $candidato)
            @php($desbloqueado = in_array($candidato->id, $postulantesDesbloqueados, true))
            @php($ultimaExp = $candidato->ultimaExperiencia())
            @php($enCarpetas = $carpetasPorCandidato[$candidato->id] ?? [])
            @php($nota = $notasPorCandidato[$candidato->id] ?? null)
            @php($nombreCandidato = $desbloqueado ? $candidato->user->name : ($candidato->user->nombres ?: \Illuminate\Support\Str::before($candidato->user->name, ' ')))
            @php($perfilUrl = $candidato->match_visible_id ? route('empresa.candidatos.show', ['match' => $candidato->match_visible_id, 'origen' => 'favoritos']) : null)

            {{-- Misma tarjeta que Prospección de Candidatos: franja lateral, ficha a la
                 izquierda y columna de acciones a la derecha. Se repite el diseño para que
                 revisar candidatos se sienta igual en las dos pantallas. --}}
            <article wire:key="favorito-{{ $candidato->id }}" class="ad-card relative overflow-hidden p-4 md:p-5">
                <div class="absolute inset-y-0 left-0 w-1 bg-orange-500"></div>
                <div class="grid items-stretch gap-4 md:grid-cols-[minmax(0,1fr)_auto] md:gap-0">
                    <div class="flex min-w-0 items-center gap-4 md:pr-6">
                        <div class="grid size-12 flex-none place-items-center rounded-[12px] bg-sage-100 text-ink" aria-hidden="true"><flux:icon.user class="size-5" /></div>
                        <div class="min-w-0">
                            <p class="truncate text-[11px] font-extrabold uppercase tracking-[.14em] text-gray-400">{{ $candidato->carrera ?: 'Carrera no informada' }}</p>
                            <h2 class="mt-0.5 truncate text-[20px] font-extrabold text-ink">
                                @if ($perfilUrl)
                                    <a wire:navigate href="{{ $perfilUrl }}" class="rounded decoration-orange-300 decoration-2 underline-offset-4 transition hover:text-orange-600 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500">{{ $nombreCandidato }}</a>
                                @else
                                    {{ $nombreCandidato }}
                                @endif
                            </h2>
                            {{-- Se cae a `cargo_actual` cuando la ficha no trae experiencias
                                 detalladas: si no, esos perfiles quedarían sin cargo a la vista. --}}
                            @php($cargoVisible = $ultimaExp['cargo'] ?? ($candidato->cargo_actual ?: null))
                            @if ($cargoVisible)
                                <p class="mt-1 flex items-center gap-1.5 text-[13px] text-gray-600 dark:text-gray-300">
                                    <flux:icon.briefcase class="size-3.5 flex-none text-gray-400" />
                                    <span class="truncate"><span class="font-semibold text-ink">{{ $cargoVisible }}</span>@if ($ultimaExp['empresa'] ?? null) · {{ $ultimaExp['empresa'] }}@endif@if ($ultimaExp['duracion'] ?? null) · {{ $ultimaExp['duracion'] }}@endif</span>
                                </p>
                            @endif
                            <p class="mt-2 max-w-4xl text-[13px] leading-relaxed text-gray-500">{{ \Illuminate\Support\Str::limit($candidato->resumen_profesional ?: 'Sin descripción profesional disponible.', 100, '…') }}</p>
                            @if ($candidato->updated_at)
                                <p class="mt-1.5 flex items-center gap-1.5 text-[11.5px] text-gray-400">
                                    <flux:icon.clock class="size-3.5 flex-none" />
                                    Ficha actualizada el {{ $candidato->updated_at->translatedFormat('d M Y') }}
                                </p>
                            @endif

                            @if ($nota)
                                <button
                                    type="button"
                                    wire:click="abrirNotas({{ $candidato->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="abrirNotas({{ $candidato->id }})"
                                    class="mt-2.5 block w-full rounded-[10px] border border-line-2 bg-paper px-3 py-2 text-start transition hover:border-orange-300 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500 disabled:opacity-50 dark:bg-[#222528]"
                                    aria-label="Ver todas las notas de este candidato"
                                >
                                    <span class="flex items-start gap-2">
                                        <flux:icon.chat-bubble-left-ellipsis class="mt-[3px] size-4 flex-none text-gray-400" />
                                        @php($pieNota = collect([
                                            $nota['autor'],
                                            $nota['privada'] ? 'Privada' : null,
                                            $nota['otras'] > 0 ? '+'.$nota['otras'].' más' : null,
                                        ])->filter()->implode(' · '))
                                        <span class="min-w-0 flex-1">
                                            <span class="line-clamp-3 text-[13px] leading-relaxed text-gray-600 dark:text-gray-300">{{ $nota['contenido'] }}</span>
                                            @if ($pieNota !== '')
                                                <span class="mt-1 block truncate text-[10.5px] font-bold uppercase tracking-[.08em] text-gray-400">{{ $pieNota }}</span>
                                            @endif
                                        </span>
                                    </span>
                                </button>
                            @endif

                            @if (($carpetasVisibles && $enCarpetas !== []) || $candidato->publicacionesAsociadas->isNotEmpty())
                                <div class="mt-2.5 flex flex-wrap items-center gap-1.5">
                                    @if ($carpetasVisibles)
                                        @foreach ($enCarpetas as $nombreCarpeta)
                                            <span class="ad-chip ad-chip-sm"><flux:icon.folder class="size-3.5" />{{ $nombreCarpeta }}</span>
                                        @endforeach
                                    @endif
                                    @foreach ($candidato->publicacionesAsociadas as $publicacionAsociada)
                                        <a wire:navigate href="{{ route('empresa.publicaciones.show', $publicacionAsociada) }}" class="ad-chip ad-chip-sm hover:border-orange-300 hover:text-orange-600">
                                            <flux:icon.megaphone class="size-3.5" />{{ $publicacionAsociada->cargo }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 border-t border-line pt-3 md:min-w-44 md:flex-col md:items-end md:justify-center md:gap-2.5 md:border-l md:border-t-0 md:pl-4 md:pt-0">
                        <div class="flex items-center gap-2">
                            @if ($desbloqueado)
                                <flux:tooltip content="Perfil desbloqueado">
                                    <span class="grid size-10 flex-none place-items-center rounded-xl border border-[#BFE6CD] bg-match-100 text-match" aria-label="Perfil desbloqueado"><flux:icon.lock-open class="size-5" /></span>
                                </flux:tooltip>
                            @elseif ($planVigente && $desbloqueosDisponibles > 0)
                                <flux:tooltip content="Desbloquear perfil (usa 1 desbloqueo)">
                                    <button type="button" wire:click="desbloquear({{ $candidato->id }})" wire:confirm="Desbloquear este perfil descontará 1 desbloqueo de tu plan. ¿Continuar?" wire:loading.attr="disabled" wire:target="desbloquear({{ $candidato->id }})" class="grid size-10 flex-none place-items-center rounded-xl border border-line-2 bg-white text-gray-400 transition hover:border-orange-300 hover:text-orange-600 disabled:opacity-50 dark:bg-[#2A2D30]" aria-label="Desbloquear perfil de {{ $nombreCandidato }}"><flux:icon.lock-closed class="size-5" /></button>
                                </flux:tooltip>
                            @else
                                <flux:tooltip :content="$planVigente ? 'Sin desbloqueos disponibles en tu plan' : 'Necesitas una suscripción activa para desbloquear'">
                                    <span class="grid size-10 flex-none place-items-center rounded-xl border border-line-2 bg-paper text-gray-300 dark:bg-[#222528]" aria-label="Perfil sin desbloquear"><flux:icon.lock-closed class="size-5" /></span>
                                </flux:tooltip>
                            @endif

                            @if ($carpetasVisibles)
                                <flux:tooltip :content="$enCarpetas !== [] ? 'En '.count($enCarpetas).' carpeta(s)' : 'Agrupar en una carpeta'">
                                    <button type="button" wire:click="abrirCarpetas({{ $candidato->id }})" wire:loading.attr="disabled" wire:target="abrirCarpetas({{ $candidato->id }})" @class(['relative grid size-10 flex-none place-items-center rounded-xl border transition disabled:opacity-50', 'border-orange-300 bg-orange-100 text-orange-600' => $enCarpetas !== [], 'border-line-2 bg-white text-gray-400 hover:border-orange-300 hover:text-orange-600 dark:bg-[#2A2D30]' => $enCarpetas === []]) aria-label="Agrupar a {{ $nombreCandidato }} en carpetas">
                                        <flux:icon.folder class="size-5" />
                                        @if ($enCarpetas !== [])
                                            <span class="absolute -right-1 -top-1 grid min-w-[18px] place-items-center rounded-full bg-orange-600 px-1 text-[10px] font-bold text-white">{{ count($enCarpetas) }}</span>
                                        @endif
                                    </button>
                                </flux:tooltip>
                            @endif

                            @php($asociadas = $candidato->publicacionesAsociadas->count())
                            <flux:tooltip :content="$asociadas > 0 ? 'Asociado a '.$asociadas.' publicación(es)' : 'Asociar a una publicación'">
                                <button type="button" wire:click="abrirAsociacion({{ $candidato->id }})" wire:loading.attr="disabled" wire:target="abrirAsociacion({{ $candidato->id }})" @class(['relative grid size-10 flex-none place-items-center rounded-xl border transition disabled:opacity-50', 'border-orange-300 bg-orange-100 text-orange-600' => $asociadas > 0, 'border-line-2 bg-white text-gray-400 hover:border-orange-300 hover:text-orange-600 dark:bg-[#2A2D30]' => $asociadas === 0]) aria-label="Asociar a {{ $nombreCandidato }} a una publicación">
                                    <flux:icon.megaphone class="size-5" />
                                    @if ($asociadas > 0)
                                        <span class="absolute -right-1 -top-1 grid min-w-[18px] place-items-center rounded-full bg-orange-600 px-1 text-[10px] font-bold text-white">{{ $asociadas }}</span>
                                    @endif
                                </button>
                            </flux:tooltip>

                            <flux:tooltip :content="$nota ? 'Ver notas de este candidato' : 'Sin notas todavía'">
                                <button type="button" wire:click="abrirNotas({{ $candidato->id }})" wire:loading.attr="disabled" wire:target="abrirNotas({{ $candidato->id }})" @class(['grid size-10 flex-none place-items-center rounded-xl border transition disabled:opacity-50', 'border-orange-300 bg-orange-100 text-orange-600' => $nota !== null, 'border-line-2 bg-white text-gray-400 hover:border-orange-300 hover:text-orange-600 dark:bg-[#2A2D30]' => $nota === null]) aria-label="Ver notas de {{ $nombreCandidato }}">
                                    <flux:icon.pencil-square class="size-5" />
                                </button>
                            </flux:tooltip>

                            <flux:tooltip content="Quitar de favoritos">
                                <button type="button" wire:click="quitarFavorito({{ $candidato->id }})" wire:loading.attr="disabled" wire:target="quitarFavorito({{ $candidato->id }})" class="grid size-10 flex-none place-items-center rounded-xl border border-orange-300 bg-orange-100 text-orange-600 transition hover:border-[#E7B6AE] hover:bg-[#FBEDEA] hover:text-[#A93226] disabled:opacity-50" aria-label="Quitar a {{ $nombreCandidato }} de favoritos" aria-pressed="true">
                                    <flux:icon.star variant="solid" class="size-5" />
                                </button>
                            </flux:tooltip>
                        </div>

                        @if ($perfilUrl)
                            <a wire:navigate href="{{ $perfilUrl }}" class="ad-btn-primary ad-btn-sm whitespace-nowrap">Ver perfil</a>
                        @endif
                    </div>
                </div>
            </article>

        @empty
            <div class="ad-card p-10 text-center">
                @if ($carpetaActiva && ! $hayFiltros)
                    {{-- Carpeta recién creada: se llena desde la propia lista, no desde las búsquedas. --}}
                    <flux:icon.folder-open class="mx-auto size-8 text-gray-400" />
                    <h2 class="mt-3 font-bold">La carpeta «{{ $carpetaActiva->nombre }}» está vacía</h2>
                    <p class="mt-2 text-[13px] text-gray-500">Abre «Carpetas» en cualquier candidato guardado para agruparlo aquí.</p>
                    <button type="button" wire:click="verCarpeta('todas')" class="ad-btn-primary ad-btn-sm mt-4">Ver todos mis favoritos</button>
                @else
                    <flux:icon.star class="mx-auto size-8 text-gray-400" />
                    <h2 class="mt-3 font-bold">{{ $hayFiltros ? 'Ningún favorito cumple estos filtros' : 'Aún no guardas favoritos' }}</h2>
                    <p class="mt-2 text-[13px] text-gray-500">
                        {{ $hayFiltros
                            ? 'Prueba ampliando los filtros para ver más candidatos.'
                            : 'Marca candidatos con la estrella desde los resultados de una búsqueda y aparecerán aquí.' }}
                    </p>
                    @if ($hayFiltros)
                        <button type="button" wire:click="limpiarFiltros" class="ad-btn-ghost ad-btn-sm mt-4">Limpiar filtros</button>
                    @else
                        <a wire:navigate href="{{ route('empresa.busquedas.index') }}" class="ad-btn-primary ad-btn-sm mt-4">Ir a Prospección de Candidatos</a>
                    @endif
                @endif
            </div>
        @endforelse
    </div>

    @if ($candidatos->hasPages())
        <div class="mt-6">{{ $candidatos->links() }}</div>
    @endif

    <x-asociar-publicaciones-modal :publicaciones="$publicacionesAsociables" :asociadas="$publicacionesDelCandidato" />
    <x-notas-candidato-modal :notas="$notasDelCandidato" :perfil-url="$notasPerfilUrl" />
    @if ($carpetasVisibles)
        <x-organizar-carpetas-modal :carpetas="$carpetas" :asignadas="$carpetasDelCandidato" />
        <x-nueva-carpeta-modal />
    @endif
</div>
