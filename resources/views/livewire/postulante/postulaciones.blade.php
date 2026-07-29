<div class="ad-panel">
    <x-slot:context>Postulante</x-slot:context>
    <x-slot:nav><x-nav-postulante activo="postulaciones" /></x-slot:nav>

    <div class="mb-6">
        <h1 class="text-[30px] font-extrabold">Mis postulaciones</h1>
        <p class="mt-2 text-[14px] text-gray-500">
            {{ $totalPostulaciones }} {{ $totalPostulaciones === 1 ? 'postulación enviada' : 'postulaciones enviadas' }}
            desde tu perfil AD+50.
        </p>
    </div>

    {{-- Filtro por estado, con el conteo de cada uno. --}}
    <div class="mb-5 flex flex-wrap gap-2">
        @php($chips = array_merge(['todas' => 'Todas'], $estados))
        @foreach ($chips as $valor => $etiqueta)
            @php($conteo = $valor === 'todas' ? $totalPostulaciones : ($conteoPorEstado[$valor] ?? 0))
            <button type="button" wire:click="mostrar('{{ $valor }}')" @class([
                'inline-flex items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-[13px] font-bold transition',
                'border-orange-300 bg-orange-100 text-orange-700' => $estado === $valor,
                'border-line-2 bg-white text-gray-500 hover:text-ink dark:bg-[#222528]' => $estado !== $valor,
            ]) aria-pressed="{{ $estado === $valor ? 'true' : 'false' }}">
                {{ $etiqueta }} <span class="opacity-70">{{ $conteo }}</span>
            </button>
        @endforeach
    </div>

    <div class="space-y-3">
        @forelse ($postulaciones as $postulacion)
            @php($publicacion = $postulacion->publicacion)
            <article wire:key="postulacion-{{ $postulacion->id }}" class="ad-card p-4 md:p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex min-w-0 items-start gap-4">
                        <div class="grid size-12 flex-none place-items-center rounded-[12px] bg-sage-100 text-ink" aria-hidden="true">
                            <flux:icon.paper-airplane class="size-5" />
                        </div>
                        <div class="min-w-0">
                            <h2 class="truncate text-[16px] font-extrabold text-ink">{{ $publicacion?->cargo ?? 'Publicación no disponible' }}</h2>
                            <p class="mt-1 truncate text-[13px] text-gray-500">
                                {{ collect([
                                    $publicacion?->nombre_empresa,
                                    $publicacion?->comuna,
                                    $publicacion?->modalidad,
                                ])->filter()->implode(' · ') ?: 'Sin datos de la oferta' }}
                            </p>
                            <p class="mt-1 text-[12.5px] text-gray-400">
                                Postulaste el {{ $postulacion->created_at->translatedFormat('d M Y') }}
                                @if ($publicacion?->trashed())
                                    · <span class="font-semibold text-gray-500">la empresa retiró esta publicación</span>
                                @elseif ($publicacion && ! $publicacion->estaVigente())
                                    · <span class="font-semibold text-gray-500">oferta cerrada</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <span @class([
                        'ad-chip flex-none',
                        'ad-chip-green' => $postulacion->estado === 'seleccionada',
                        'ad-chip-orange' => $postulacion->estado === 'en_revision',
                    ])>{{ $postulacion->estadoLabel() }}</span>
                </div>

                @if (filled($postulacion->respuestas))
                    <details class="group mt-4 border-t border-line pt-3">
                        <summary class="cursor-pointer list-none text-[13px] font-bold text-gray-500 hover:text-ink">
                            <span class="inline-flex items-center gap-1.5">
                                <flux:icon.chevron-down class="size-4 transition group-open:rotate-180" />
                                Ver mis respuestas
                            </span>
                        </summary>
                        <ul class="mt-3 space-y-2">
                            @foreach ($postulacion->respuestas as $indice => $respuesta)
                                <li class="rounded-[10px] bg-paper px-4 py-3 dark:bg-white/5">
                                    <p class="text-[12.5px] font-bold text-gray-500">{{ $publicacion->preguntas[$indice] ?? 'Pregunta '.($indice + 1) }}</p>
                                    <p class="mt-1 text-[13.5px] text-ink">{{ $respuesta }}</p>
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @endif
            </article>
        @empty
            <div class="ad-card p-10 text-center">
                <flux:icon.paper-airplane class="mx-auto size-8 text-gray-400" />
                <h2 class="mt-3 font-bold">
                    {{ $estado === 'todas' ? 'Aún no has postulado' : 'No tienes postulaciones en este estado' }}
                </h2>
                <p class="mt-2 text-[13px] text-gray-500">
                    {{ $estado === 'todas'
                        ? 'Cuando postules a una oferta, aquí podrás seguir en qué va.'
                        : 'Prueba con otro estado para ver el resto de tus postulaciones.' }}
                </p>
                @if ($estado === 'todas')
                    <a wire:navigate href="{{ route('postulante.busquedas') }}" class="ad-btn-primary ad-btn-sm mt-4">Ver oportunidades</a>
                @else
                    <button type="button" wire:click="mostrar('todas')" class="ad-btn-ghost ad-btn-sm mt-4">Ver todas</button>
                @endif
            </div>
        @endforelse
    </div>

    @if ($postulaciones->hasPages())
        <div class="mt-6">{{ $postulaciones->links() }}</div>
    @endif
</div>
