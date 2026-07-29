<div class="ad-panel">
    <x-slot:context>Postulante</x-slot:context>
    <x-slot:nav><x-nav-postulante activo="busquedas" /></x-slot:nav>

    <div class="mx-auto max-w-4xl">
        <a wire:navigate href="{{ route('postulante.busquedas') }}" class="mb-4 inline-flex items-center gap-2 text-[13px] font-bold text-gray-500 hover:text-ink">
            <flux:icon.arrow-left class="size-4" />Volver a oportunidades
        </a>

        @if (session('status'))
            <div class="mb-5 rounded-xl border border-[#BFE6CD] bg-match-100 px-4 py-3 text-[13px] font-bold text-match">{{ session('status') }}</div>
        @endif

        {{-- Encabezado con la acción principal --}}
        <div class="mb-6 flex flex-wrap items-start justify-between gap-5">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="ad-chip ad-chip-sm ad-chip-orange">{{ $publicacion->modalidad }}</span>
                    @if ($publicacion->empleo_inclusivo)<span class="ad-chip ad-chip-sm ad-chip-green">Empleo inclusivo</span>@endif
                    <span class="text-[12px] text-gray-400">Publicado {{ $publicacion->created_at->diffForHumans() }}</span>
                </div>
                <h1 class="mt-3 text-[28px] font-extrabold">{{ $publicacion->cargo }}</h1>
                <p class="mt-1.5 text-[14px] font-semibold text-gray-600">
                    {{ $publicacion->nombre_empresa }} · {{ $publicacion->comuna }}, {{ $publicacion->pais }}
                </p>
            </div>

            @if ($yaPostulo)
                <span class="ad-chip ad-chip-green flex-none"><flux:icon.check class="size-4" />Postulación enviada</span>
            @else
                <button type="button" wire:click="abrirPostulacion({{ $publicacion->id }})" class="ad-btn-primary ad-btn-sm flex-none">Postular</button>
            @endif
        </div>

        {{-- Resumen --}}
        <section class="ad-card mb-5 grid gap-5 p-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['Jornada', $publicacion->tipo_cargo],
                ['Nivel del cargo', $publicacion->jerarquia],
                ['Experiencia', $publicacion->experiencia_laboral],
                ['Vacantes', $publicacion->vacantes],
            ] as [$titulo, $valor])
                <div>
                    <p class="text-[12px] font-bold uppercase tracking-[0.1em] text-gray-400">{{ $titulo }}</p>
                    <p class="mt-1 text-[14px] font-semibold text-ink">{{ $valor }}</p>
                </div>
            @endforeach
        </section>

        <section class="ad-card mb-5">
            <div class="ad-card-head"><h2 class="text-[16px] font-bold">Descripción del cargo</h2></div>
            <div class="p-6"><p class="whitespace-pre-line text-[14px] leading-relaxed text-gray-700">{{ $publicacion->descripcion }}</p></div>
        </section>

        <section class="ad-card mb-5">
            <div class="ad-card-head"><h2 class="text-[16px] font-bold">Requisitos</h2></div>
            <div class="p-6">
                <p class="whitespace-pre-line text-[14px] leading-relaxed text-gray-700">{{ $publicacion->requisitos }}</p>
                <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-[12px] font-bold uppercase tracking-[0.1em] text-gray-400">Estudios mínimos</dt>
                        <dd class="mt-1 text-[14px] text-ink">{{ $publicacion->estudios_minimos }}</dd>
                    </div>
                    <div>
                        <dt class="text-[12px] font-bold uppercase tracking-[0.1em] text-gray-400">Situación académica</dt>
                        <dd class="mt-1 text-[14px] text-ink">{{ $publicacion->situacion_academica }}</dd>
                    </div>
                </dl>
            </div>
        </section>

        @if (filled($publicacion->competencias) || filled($publicacion->idiomas))
            <section class="ad-card mb-5">
                <div class="ad-card-head"><h2 class="text-[16px] font-bold">Competencias e idiomas</h2></div>
                <div class="flex flex-wrap gap-1.5 p-6">
                    @foreach ($publicacion->competencias ?? [] as $competencia)
                        <span class="ad-chip ad-chip-sm">{{ $competencia }}</span>
                    @endforeach
                    @foreach ($publicacion->idiomas ?? [] as $idioma)
                        <span class="ad-chip ad-chip-sm ad-chip-orange">{{ $idioma }}</span>
                    @endforeach
                </div>
            </section>
        @endif

        @if (filled($publicacion->preguntas))
            <section class="ad-card mb-5">
                <div class="ad-card-head"><h2 class="text-[16px] font-bold">Preguntas de la postulación</h2></div>
                <div class="p-6">
                    <p class="mb-3 text-[13px] text-gray-500">Al postular tendrás que responder:</p>
                    <ul class="list-inside list-disc space-y-1.5 text-[14px] text-gray-700">
                        @foreach ($publicacion->preguntas as $pregunta)<li>{{ $pregunta }}</li>@endforeach
                    </ul>
                </div>
            </section>
        @endif

        @unless ($yaPostulo)
            <div class="flex justify-end">
                <button type="button" wire:click="abrirPostulacion({{ $publicacion->id }})" class="ad-btn-primary ad-btn-sm">Postular a esta oferta</button>
            </div>
        @endunless
    </div>

    <x-postular-modal :publicacion="$publicacionSeleccionada" />
</div>
