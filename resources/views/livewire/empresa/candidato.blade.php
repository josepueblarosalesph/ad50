<div>
    <x-slot:context>Empresa</x-slot:context>
    <x-slot:nav>
        <a href="{{ route('empresa.panel') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-gray-500 hover:text-ink">Panel</a>
        <a wire:navigate href="{{ route('empresa.busquedas.index') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-ink bg-orange-100">Procesos</a>
        <a wire:navigate href="{{ route('empresa.busquedas.create') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-gray-500 hover:text-ink">Nuevo proceso</a>
    </x-slot:nav>
    <x-slot:sidebar>
        <div class="text-[10.5px] tracking-[0.12em] uppercase text-gray-400 font-bold px-2.5 mb-2">Candidato</div>
        <a href="{{ route('empresa.resultados', ['busqueda' => $match->busqueda, 'filtro' => $filtro, 'criterios' => $criterios]) }}" class="flex items-center gap-3 text-[14px] font-semibold px-3 py-2.5 rounded-[10px] text-gray-700 hover:bg-paper"><flux:icon.arrow-left class="size-[18px]" />Volver a resultados</a>
        <a href="#contacto" class="ad-candidate-sidebar-active flex items-center gap-3 rounded-[10px] px-3 py-2.5 text-[14px] font-semibold"><flux:icon.user class="size-[18px]" />Perfil profesional</a>
    </x-slot:sidebar>

    @php($postulante = $match->postulante)
    <div class="ad-candidate-toolbar mb-5 flex flex-wrap items-center justify-between gap-3 rounded-2xl border p-2.5 shadow-sm">
        <a wire:navigate href="{{ route('empresa.resultados', ['busqueda' => $match->busqueda, 'filtro' => $filtro, 'criterios' => $criterios]) }}" class="inline-flex items-center gap-2 px-2 text-[13px] font-semibold text-gray-500 hover:text-ink"><flux:icon.arrow-left class="size-4" />Volver a {{ $filtro === 'favoritos' ? 'favoritos' : 'resultados' }}</a>
        <div class="flex items-center gap-2" aria-label="Navegación entre candidatos">
            <div class="mr-1 inline-flex rounded-full border border-line-2 bg-white p-0.5 text-[12px] font-bold dark:bg-[#222528]" aria-label="Filtrar candidatos">
                <button type="button" wire:click="cambiarFiltro('todos')" @class(['rounded-full px-3 py-1.5 transition', 'bg-ink text-white dark:bg-orange-600' => $filtro === 'todos', 'text-gray-500 hover:text-ink' => $filtro !== 'todos'])>Todos</button>
                <button type="button" wire:click="cambiarFiltro('favoritos')" @class(['inline-flex items-center gap-1 rounded-full px-3 py-1.5 transition', 'bg-orange-600 text-white' => $filtro === 'favoritos', 'text-gray-500 hover:text-orange-600' => $filtro !== 'favoritos'])><flux:icon.star variant="solid" class="size-3.5" />Favoritos</button>
            </div>
            @if ($criterios !== [])
                <flux:modal.trigger name="filtros-activos">
                    <button type="button" class="mr-1 inline-flex items-center gap-1.5 rounded-full bg-sage-100 px-3 py-1.5 text-[12px] font-bold text-ink transition hover:bg-sage-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-600" aria-label="Ver filtros activos"><flux:icon.funnel class="size-4" /><span class="hidden sm:inline">{{ count($criterios) }} {{ count($criterios) === 1 ? 'filtro activo' : 'filtros activos' }}</span></button>
                </flux:modal.trigger>
            @endif
            @if ($anteriorId)
                <a wire:navigate href="{{ route('empresa.candidatos.show', ['match' => $anteriorId, 'filtro' => $filtro, 'criterios' => $criterios]) }}" class="grid size-10 place-items-center rounded-xl border border-line-2 text-ink transition hover:border-orange-300 hover:text-orange-600 dark:hover:text-orange-400" aria-label="Ver candidato anterior"><flux:icon.chevron-left class="size-5" /></a>
            @else
                <span class="grid size-10 place-items-center rounded-xl border border-line text-gray-300 opacity-70 dark:border-white/10 dark:text-gray-600" aria-hidden="true"><flux:icon.chevron-left class="size-5" /></span>
            @endif
            <span class="min-w-20 text-center text-[13px] font-bold text-gray-600"><span class="text-ink">{{ $posicion }}</span> de {{ $totalCandidatos }}</span>
            @if ($siguienteId)
                <a wire:navigate href="{{ route('empresa.candidatos.show', ['match' => $siguienteId, 'filtro' => $filtro, 'criterios' => $criterios]) }}" class="grid size-10 place-items-center rounded-xl border border-line-2 text-ink transition hover:border-orange-300 hover:text-orange-600 dark:hover:text-orange-400" aria-label="Ver candidato siguiente"><flux:icon.chevron-right class="size-5" /></a>
            @else
                <span class="grid size-10 place-items-center rounded-xl border border-line text-gray-300 opacity-70 dark:border-white/10 dark:text-gray-600" aria-hidden="true"><flux:icon.chevron-right class="size-5" /></span>
            @endif
        </div>
    </div>

    <div class="flex items-start justify-between gap-5 mb-6 flex-wrap">
        <div class="flex items-start gap-4">
            <div class="size-16 flex-none rounded-full bg-orange-100 text-orange-600 grid place-items-center" aria-hidden="true"><flux:icon.user class="size-7" /></div>
            <div class="min-w-0">
                <h1 class="text-[24px] font-extrabold">{{ $desbloqueado ? $postulante->user->name : ($postulante->user->nombres ?: \Illuminate\Support\Str::before($postulante->user->name, ' ')) }}</h1>
                <p class="text-[14px] text-gray-500 mt-1">{{ $postulante->titular ?: ($postulante->carrera ?: 'Titular profesional no informado') }}</p>
                @if (filled($postulante->habilidades))
                    <div class="mt-2.5 flex flex-wrap gap-1.5">
                        @foreach ($postulante->habilidades as $habilidad)
                            <span class="ad-chip ad-chip-orange px-2.5 py-0.5 text-[12px]">{{ $habilidad }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        <button type="button" wire:click="toggleFavorito" wire:loading.attr="disabled" @class(['ad-favorite-button ad-btn-sm inline-flex items-center gap-2 rounded-xl border font-bold transition disabled:opacity-50', 'is-active' => $match->favorito]) aria-pressed="{{ $match->favorito ? 'true' : 'false' }}"><flux:icon.star variant="solid" class="size-5" />{{ $match->favorito ? 'Guardado en favoritos' : 'Guardar como favorito' }}</button>
    </div>

    <div class="grid lg:grid-cols-[1.4fr_0.8fr] gap-5 items-start">
        <div class="space-y-5">
            @if ($postulante->resumen_profesional)
                <section class="ad-card"><div class="ad-card-head"><h2 class="text-[16px] font-bold">Acerca de mí</h2></div><div class="p-6"><p class="text-[14px] leading-relaxed text-gray-700">{{ $postulante->resumen_profesional }}</p></div></section>
            @endif

            <section class="ad-card"><div class="ad-card-head flex-wrap gap-3"><h2 class="text-[16px] font-bold">Experiencia laboral</h2><span class="ad-candidate-value"><flux:icon.briefcase />{{ $postulante->anios_experiencia }} años de experiencia</span></div><div class="divide-y divide-line px-6">@forelse ($postulante->experiencias ?? [] as $experiencia)<div class="py-4"><b class="text-[14px]">{{ ($experiencia['cargo'] ?? '') === 'Otros' ? ($experiencia['cargo_otro'] ?? 'Otros') : $experiencia['cargo'] }}</b><p class="mt-1 text-[13px] text-gray-500">{{ collect([(($experiencia['empresa'] ?? '') === 'Otros' ? ($experiencia['empresa_otro'] ?? null) : ($experiencia['empresa'] ?? null)), $experiencia['jerarquia'] ?? null, $experiencia['actividad_empresa'] ?? $experiencia['area'] ?? null])->filter()->implode(' · ') }}</p><p class="mt-1 text-[13px] text-gray-500">{{ $meses[$experiencia['inicio_mes'] ?? 1] ?? '' }} {{ $experiencia['inicio_anio'] ?? $experiencia['inicio'] ?? '' }} – {{ ($experiencia['actualmente'] ?? empty($experiencia['fin'])) ? 'Actualidad' : (($meses[$experiencia['fin_mes'] ?? 12] ?? '').' '.($experiencia['fin_anio'] ?? $experiencia['fin'] ?? '')) }}</p>@if (filled($experiencia['responsabilidades'] ?? null))<p class="mt-3 text-[13px] leading-relaxed text-gray-700">{{ $experiencia['responsabilidades'] }}</p>@endif</div>@empty<div class="p-6 text-[13px] text-gray-500">Sin experiencia laboral informada.</div>@endforelse</div></section>

            <section class="ad-card"><div class="ad-card-head"><h2 class="text-[16px] font-bold">Educación</h2></div><div class="divide-y divide-line px-6">@forelse ($postulante->educaciones ?? [] as $educacion)<div class="py-4"><b class="text-[14px]">{{ $educacion['nivel'] }}{{ filled($educacion['carrera'] ?? null) ? ' · '.$educacion['carrera'] : '' }}</b><p class="mt-1 text-[13px] text-gray-500">{{ collect([$educacion['institucion'] ?? null, $educacion['pais'] ?? null, $educacion['mencion'] ?? null])->filter()->implode(' · ') }}</p><p class="mt-1 text-[13px] text-gray-500">@if (filled($educacion['egreso_anio'] ?? null))Egreso {{ $educacion['egreso_anio'] }}@elseif (filled($educacion['inicio_anio'] ?? null)){{ $educacion['inicio_anio'] }}–{{ $educacion['termino_anio'] ?? 'actualidad' }} · {{ $educacion['situacion'] ?? '' }}@endif</p></div>@empty<div class="p-6"><b class="text-[14px]">{{ $postulante->carrera ?: 'Sin título informado' }}</b><p class="mt-1 text-[13px] text-gray-500">{{ collect([$postulante->especialidad, $postulante->universidad])->filter()->implode(' · ') }}</p></div>@endforelse</div></section>

            @if (filled($postulante->idiomas))
                <section class="ad-card"><div class="ad-card-head"><h2 class="text-[16px] font-bold">Idiomas</h2></div><div class="flex flex-wrap gap-2 p-6">@foreach ($postulante->idiomas as $idioma)<span class="ad-chip ad-chip-orange">{{ $idioma['idioma'] }} · {{ $idioma['nivel'] }}</span>@endforeach</div></section>
            @endif

            <section class="ad-card"><div class="ad-card-head"><h2 class="text-[16px] font-bold">Información adicional</h2></div><div class="space-y-5 p-6">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div><h3 class="mb-1.5 text-[12px] font-extrabold uppercase tracking-wide text-gray-400">Situación laboral</h3><p class="text-[14px] text-ink">{{ $postulante->situacion_laboral ?: 'No informada' }}</p></div>
                    <div><h3 class="mb-1.5 text-[12px] font-extrabold uppercase tracking-wide text-gray-400">Expectativa de renta</h3><p class="text-[14px] text-ink">{{ $postulante->expectativa_renta ? '$'.number_format($postulante->expectativa_renta, 0, ',', '.') : 'No informada' }}</p></div>
                </div>
                <div><h3 class="mb-2 text-[12px] font-extrabold uppercase tracking-wide text-gray-400">Regiones de interés</h3><div class="flex flex-wrap gap-2">@forelse ($postulante->regiones_interes ?? [] as $region)<span class="ad-candidate-value"><flux:icon.map-pin />{{ $region }}</span>@empty<span class="text-[13px] text-gray-500">Sin regiones informadas</span>@endforelse</div></div>
                <div><h3 class="mb-2 text-[12px] font-extrabold uppercase tracking-wide text-gray-400">Industrias de interés</h3><div class="flex flex-wrap gap-2">@forelse ($postulante->industrias_interes ?? [] as $industria)<span class="ad-candidate-value"><flux:icon.building-office-2 />{{ $industria }}</span>@empty<span class="text-[13px] text-gray-500">Sin industrias informadas</span>@endforelse</div></div>
                <div><h3 class="mb-2 text-[12px] font-extrabold uppercase tracking-wide text-gray-400">Modalidad de trabajo</h3><div class="flex flex-wrap gap-2">@forelse ($postulante->modalidad_trabajo ?? [] as $modalidad)<span class="ad-candidate-value"><flux:icon.clock />{{ $modalidad }}</span>@empty<span class="text-[13px] text-gray-500">Sin modalidad informada</span>@endforelse</div></div>
            </div></section>
        </div>

        <div class="space-y-5">
        <section class="ad-card border-orange-200">
            <div class="ad-card-head bg-orange-50/60 dark:bg-orange-50"><div><h2 class="text-[16px] font-bold text-orange-700 dark:text-orange-600 flex items-center gap-2"><flux:icon.pencil-square class="size-4" />Notas privadas</h2><p class="mt-1 text-[13px] text-gray-500">Solo tu empresa puede verlas. El postulante no las ve.</p></div></div>
            <div class="p-5">
                <flux:textarea wire:model.blur="nota" rows="4" maxlength="2000" placeholder="Anota aquí tus comentarios sobre este candidato…" />
                <div class="mt-3 flex items-center justify-between gap-3">
                    @if ($notaGuardada)
                        <span class="inline-flex items-center gap-1 text-[12px] font-bold text-match"><flux:icon.check class="size-4" /> Nota guardada</span>
                    @else
                        <span></span>
                    @endif
                    <button type="button" wire:click="guardarNota" class="ad-btn-primary ad-btn-sm" wire:loading.attr="disabled" wire:target="guardarNota"><span wire:loading.remove wire:target="guardarNota">Guardar nota</span><span wire:loading wire:target="guardarNota">Guardando…</span></button>
                </div>
            </div>
        </section>

        <aside id="contacto" @class(['ad-card overflow-hidden', 'border-[#BFE6CD]' => $puedeVerContacto, 'border-line-2' => ! $puedeVerContacto])><div @class(['ad-card-head', 'bg-match-100/50' => $puedeVerContacto, 'bg-paper' => ! $puedeVerContacto])><h2 class="text-[16px] font-bold flex items-center gap-2"><flux:icon :name="$puedeVerContacto ? 'lock-open' : 'lock-closed'" class="{{ $puedeVerContacto ? 'size-4 text-match' : 'size-4 text-gray-500' }}" />Datos de contacto</h2><span @class(['ad-chip ad-chip-dot', 'ad-chip-green' => $puedeVerContacto, 'ad-chip-gray' => ! $puedeVerContacto])>{{ $puedeVerContacto ? 'Visible' : 'Restringido' }}</span></div><div class="p-5 space-y-3">
            @if ($puedeVerContacto)
            <div class="ad-toggle-row"><flux:icon.identification class="size-4 text-gray-500" /><span class="flex-1 text-[13px]"><b>{{ $postulante->rut ?: 'Sin RUT informado' }}</b></span></div>
            <div class="ad-toggle-row"><flux:icon.phone class="size-4 text-gray-500" /><span class="flex-1 text-[13px]"><b>{{ $postulante->telefono ?: 'Sin teléfono informado' }}</b></span></div>
            <div class="ad-toggle-row"><flux:icon.envelope class="size-4 text-gray-500" /><span class="flex-1 text-[13px]"><b>{{ $postulante->user->email }}</b></span></div>
            <div class="ad-toggle-row"><flux:icon.map-pin class="size-4 text-gray-500" /><span class="flex-1 text-[13px]"><b>{{ $postulante->ciudad ?: 'Chile' }}</b></span></div>
            @if (filled($postulante->linkedin))
                <a href="{{ $postulante->linkedin }}" target="_blank" rel="noopener noreferrer" class="ad-toggle-row transition hover:border-orange-300"><flux:icon.link class="size-4 text-gray-500" /><span class="flex-1 text-[13px] font-bold text-orange-600">Ver perfil de LinkedIn</span><flux:icon.arrow-top-right-on-square class="size-4 text-gray-400" /></a>
            @endif
            @if ($cvDisponible)
                <button type="button" wire:click="descargarCv" wire:loading.attr="disabled" wire:target="descargarCv" class="ad-btn-primary ad-btn-sm w-full justify-center disabled:opacity-60">
                    <flux:icon.arrow-down-tray class="size-4" />
                    <span wire:loading.remove wire:target="descargarCv">Descargar CV en PDF</span>
                    <span wire:loading wire:target="descargarCv">Preparando descarga…</span>
                </button>
            @else
                <div class="rounded-[10px] border border-line-2 bg-paper px-4 py-3 text-[13px] text-gray-500"><b class="text-gray-700">CV no disponible</b><span class="mt-1 block">El postulante aún no ha adjuntado un currículum.</span></div>
            @endif
            <p class="flex gap-2 text-[13px] leading-relaxed text-gray-500"><flux:icon.information-circle class="mt-0.5 size-4 flex-none" />El acceso queda registrado para fines de privacidad y auditoría.</p>
            @else
            <div class="text-center">
                <span class="mx-auto grid size-12 place-items-center rounded-full bg-orange-100 text-orange-600"><flux:icon.lock-closed class="size-6" /></span>
                <p class="mt-3 text-[13px] leading-relaxed text-gray-600">Desbloquea este perfil para ver el nombre completo, los datos de contacto (RUT, teléfono, correo, LinkedIn) y descargar su CV.</p>
                @if ($planVigente)
                    <p class="mt-3 text-[12px] font-bold text-orange-600">{{ $desbloqueosDisponibles }} {{ $desbloqueosDisponibles === 1 ? 'desbloqueo disponible' : 'desbloqueos disponibles' }}</p>
                    <button type="button" wire:click="desbloquear" wire:confirm="Desbloquear este perfil descontará 1 desbloqueo de tu plan. ¿Continuar?" @disabled($desbloqueosDisponibles < 1) class="ad-btn-primary ad-btn-sm mt-3 w-full justify-center disabled:opacity-60"><flux:icon.lock-open class="size-4" />Desbloquear perfil</button>
                    @if ($desbloqueosDisponibles < 1)
                        <p class="mt-2 text-[12px] text-gray-500">No te quedan desbloqueos en tu plan. Revisa tus planes para ampliar el cupo.</p>
                    @endif
                @else
                    <p class="mt-3 text-[12px] text-gray-500">Necesitas una suscripción de empresa activa para desbloquear perfiles.</p>
                @endif
                @error('desbloqueo')<p class="mt-2 text-[12px] font-semibold text-[#A93226] dark:text-red-400">{{ $message }}</p>@enderror
            </div>
            @endif
        </div></aside>
        </div>
    </div>

    @if ($criterios !== [])
        <flux:modal name="filtros-activos" class="max-w-lg">
            <div class="flex items-start gap-3">
                <span class="grid size-10 flex-none place-items-center rounded-xl bg-sage-100 text-ink"><flux:icon.funnel class="size-5" /></span>
                <div><flux:heading size="lg">Filtros activos</flux:heading><flux:text class="mt-1">Estás navegando solo entre candidatos que cumplen todos estos criterios.</flux:text></div>
            </div>
            <div class="mt-5 space-y-2">
                @foreach ($criteriosActivos as $criterio)
                    <div class="flex items-start gap-3 rounded-xl border border-line-2 bg-paper p-3.5">
                        <span class="mt-0.5 grid size-6 flex-none place-items-center rounded-full bg-match-100 text-match"><flux:icon.check class="size-4" /></span>
                        <div><p class="text-[12px] font-bold uppercase tracking-wide text-gray-400">{{ $criterio['etiqueta'] }}</p><p class="mt-0.5 text-[14px] font-bold text-ink">{{ $criterio['valor'] }}</p></div>
                    </div>
                @endforeach
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Cerrar</flux:button></flux:modal.close>
                <a wire:navigate href="{{ route('empresa.resultados', ['busqueda' => $match->busqueda, 'filtro' => $filtro, 'criterios' => $criterios]) }}" class="ad-btn-primary ad-btn-sm">Volver a resultados</a>
            </div>
        </flux:modal>
    @endif
</div>
