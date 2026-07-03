<div>
    <x-slot:context>Empresa</x-slot:context>
    <x-slot:nav>
        <a href="{{ route('empresa.panel') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-gray-500 hover:text-ink">Panel</a>
        <a wire:navigate href="{{ route('empresa.busquedas.index') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-ink bg-orange-100">Búsquedas</a>
        <a wire:navigate href="{{ route('empresa.busquedas.create') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-gray-500 hover:text-ink">Nueva búsqueda</a>
        <a href="{{ route('planes') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-gray-500 hover:text-ink">Mi plan</a>
    </x-slot:nav>
    <x-slot:sidebar>
        <div class="text-[10.5px] tracking-[0.12em] uppercase text-gray-400 font-bold px-2.5 mb-2">Candidato</div>
        <a href="{{ route('empresa.resultados', ['busqueda' => $match->busqueda, 'filtro' => $filtro, 'criterios' => $criterios]) }}" class="flex items-center gap-3 text-[14px] font-semibold px-3 py-2.5 rounded-[10px] text-gray-700 hover:bg-paper"><flux:icon.arrow-left class="size-[18px]" />Volver a resultados</a>
        <a href="#contacto" class="ad-candidate-sidebar-active flex items-center gap-3 rounded-[10px] px-3 py-2.5 text-[14px] font-semibold"><flux:icon.user class="size-[18px]" />Ficha profesional</a>
    </x-slot:sidebar>

    @php($postulante = $match->postulante)
    <div class="ad-candidate-toolbar mb-5 flex flex-wrap items-center justify-between gap-3 rounded-2xl border p-2.5 shadow-sm">
        <a wire:navigate href="{{ route('empresa.resultados', ['busqueda' => $match->busqueda, 'filtro' => $filtro, 'criterios' => $criterios]) }}" class="inline-flex items-center gap-2 px-2 text-[13px] font-semibold text-gray-500 hover:text-ink"><flux:icon.arrow-left class="size-4" />Volver a {{ $filtro === 'favoritos' ? 'favoritos' : 'resultados' }}</a>
        <div class="flex items-center gap-2" aria-label="Navegación entre candidatos">
            @if ($filtro === 'favoritos')
                <span class="mr-1 hidden items-center gap-1.5 rounded-full bg-orange-100 px-3 py-1.5 text-[12px] font-bold text-orange-600 sm:inline-flex"><flux:icon.star variant="solid" class="size-4" />Revisando favoritos</span>
            @endif
            @if ($criterios !== [])
                <flux:modal.trigger name="filtros-activos">
                    <button type="button" class="mr-1 inline-flex items-center gap-1.5 rounded-full bg-sage-100 px-3 py-1.5 text-[12px] font-bold text-ink transition hover:bg-sage-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-600" aria-label="Ver filtros activos"><flux:icon.funnel class="size-4" /><span class="hidden sm:inline">{{ count($criterios) }} {{ count($criterios) === 1 ? 'filtro activo' : 'filtros activos' }}</span></button>
                </flux:modal.trigger>
            @endif
            @if ($anteriorId)
                <a wire:navigate href="{{ route('empresa.candidatos.show', ['match' => $anteriorId, 'filtro' => $filtro, 'criterios' => $criterios]) }}" class="grid size-10 place-items-center rounded-xl border border-line-2 text-gray-600 transition hover:border-orange-300 hover:text-orange-600" aria-label="Ver candidato anterior"><flux:icon.chevron-left class="size-5" /></a>
            @else
                <span class="grid size-10 place-items-center rounded-xl border border-line text-gray-300" aria-hidden="true"><flux:icon.chevron-left class="size-5" /></span>
            @endif
            <span class="min-w-20 text-center text-[13px] font-bold text-gray-600"><span class="text-ink">{{ $posicion }}</span> de {{ $totalCandidatos }}</span>
            @if ($siguienteId)
                <a wire:navigate href="{{ route('empresa.candidatos.show', ['match' => $siguienteId, 'filtro' => $filtro, 'criterios' => $criterios]) }}" class="grid size-10 place-items-center rounded-xl border border-line-2 text-gray-600 transition hover:border-orange-300 hover:text-orange-600" aria-label="Ver candidato siguiente"><flux:icon.chevron-right class="size-5" /></a>
            @else
                <span class="grid size-10 place-items-center rounded-xl border border-line text-gray-300" aria-hidden="true"><flux:icon.chevron-right class="size-5" /></span>
            @endif
        </div>
    </div>

    <div class="flex items-center justify-between gap-5 mb-6 flex-wrap"><div class="flex items-center gap-4"><div class="size-16 rounded-full bg-orange-100 text-orange-600 grid place-items-center" aria-hidden="true"><flux:icon.user class="size-7" /></div><div><h1 class="text-[24px] font-extrabold">{{ $postulante->user->name }}</h1><p class="text-[14px] text-gray-500 mt-1">{{ $postulante->carrera ?: 'Carrera no informada' }}</p><span @class(['ad-chip mt-2', 'ad-chip-green' => $match->estado_match === 'cumple'])>{{ $match->estado_match === 'cumple' ? 'Cumple' : 'Parcial — cumple' }} {{ $match->criterios_cumplidos }} de {{ $match->criterios_totales }} criterios</span></div></div><button type="button" wire:click="toggleFavorito" wire:loading.attr="disabled" @class(['ad-favorite-button ad-btn-sm inline-flex items-center gap-2 rounded-xl border font-bold transition disabled:opacity-50', 'is-active' => $match->favorito]) aria-pressed="{{ $match->favorito ? 'true' : 'false' }}"><flux:icon.star variant="solid" class="size-5" />{{ $match->favorito ? 'Guardado en favoritos' : 'Guardar como favorito' }}</button></div>

    <div class="grid lg:grid-cols-[1.4fr_0.8fr] gap-5 items-start">
        <div class="space-y-5">
            <section class="ad-card"><div class="ad-card-head"><h2 class="text-[16px] font-bold">Educación</h2></div><div class="divide-y divide-line px-6">@forelse ($postulante->educaciones ?? [] as $educacion)<div class="py-4"><b class="text-[14px]">{{ $educacion['nivel'] }}{{ filled($educacion['carrera'] ?? null) ? ' · '.$educacion['carrera'] : '' }}</b><p class="mt-1 text-[13px] text-gray-500">{{ collect([$educacion['institucion'] ?? null, $educacion['pais'] ?? null, $educacion['mencion'] ?? null])->filter()->implode(' · ') }}</p><p class="mt-1 text-[13px] text-gray-500">@if (filled($educacion['egreso_anio'] ?? null))Egreso {{ $educacion['egreso_anio'] }}@elseif (filled($educacion['inicio_anio'] ?? null)){{ $educacion['inicio_anio'] }}–{{ $educacion['termino_anio'] ?? 'actualidad' }} · {{ $educacion['situacion'] ?? '' }}@endif</p></div>@empty<div class="p-6"><b class="text-[14px]">{{ $postulante->carrera ?: 'Sin título informado' }}</b><p class="mt-1 text-[13px] text-gray-500">{{ collect([$postulante->especialidad, $postulante->universidad])->filter()->implode(' · ') }}</p></div>@endforelse</div></section>
            @if (filled($postulante->idiomas))
                <section class="ad-card"><div class="ad-card-head"><h2 class="text-[16px] font-bold">Idiomas</h2></div><div class="flex flex-wrap gap-2 p-6">@foreach ($postulante->idiomas as $idioma)<span class="ad-chip ad-chip-orange">{{ $idioma['idioma'] }} · {{ $idioma['nivel'] }}</span>@endforeach</div></section>
            @endif
            <section class="ad-card"><div class="ad-card-head"><h2 class="text-[16px] font-bold">Industrias de interés</h2></div><div class="flex flex-wrap gap-2 p-6">@foreach (collect([$postulante->industria, $postulante->industria_2, $postulante->industria_3])->filter() as $industria)<span class="ad-candidate-value"><flux:icon.building-office-2 />{{ $industria }}</span>@endforeach</div></section>
            <section class="ad-card"><div class="ad-card-head flex-wrap gap-3"><h2 class="text-[16px] font-bold">Experiencia</h2><span class="ad-candidate-value"><flux:icon.briefcase />{{ $postulante->anios_experiencia }} años de experiencia</span></div><div class="divide-y divide-line px-6">@foreach ($postulante->experiencias ?? [] as $experiencia)<div class="py-4"><b class="text-[14px]">{{ $experiencia['cargo'] }}</b><p class="mt-1 text-[13px] text-gray-500">{{ collect([$experiencia['empresa'] ?? null, $experiencia['jerarquia'] ?? null, $experiencia['actividad_empresa'] ?? $experiencia['area'] ?? null])->filter()->implode(' · ') }}</p><p class="mt-1 text-[13px] text-gray-500">{{ $meses[$experiencia['inicio_mes'] ?? 1] ?? '' }} {{ $experiencia['inicio_anio'] ?? $experiencia['inicio'] ?? '' }} – {{ ($experiencia['actualmente'] ?? empty($experiencia['fin'])) ? 'Actualidad' : (($meses[$experiencia['fin_mes'] ?? 12] ?? '').' '.($experiencia['fin_anio'] ?? $experiencia['fin'] ?? '')) }}</p>@if (filled($experiencia['responsabilidades'] ?? null))<p class="mt-3 text-[13px] leading-relaxed text-gray-700">{{ $experiencia['responsabilidades'] }}</p>@endif</div>@endforeach@if ($postulante->resumen_profesional)<p class="py-4 text-[13px] leading-relaxed text-gray-700">{{ $postulante->resumen_profesional }}</p>@endif</div></section>
        </div>

        <aside id="contacto" class="ad-card border-[#BFE6CD] overflow-hidden"><div class="ad-card-head bg-match-100/50"><h2 class="text-[16px] font-bold flex items-center gap-2"><flux:icon :name="$puedeVerContacto ? 'lock-open' : 'lock-closed'" class="size-4 text-match" />Datos de contacto</h2><span class="ad-chip ad-chip-green ad-chip-dot">{{ $puedeVerContacto ? 'Visible' : 'Restringido' }}</span></div><div class="p-5 space-y-3">
            @if ($puedeVerContacto)
            <div class="ad-toggle-row"><flux:icon.identification class="size-4 text-gray-500" /><span class="flex-1 text-[13px]"><b>{{ $postulante->rut ?: 'Sin RUT informado' }}</b></span></div>
            <div class="ad-toggle-row"><flux:icon.phone class="size-4 text-gray-500" /><span class="flex-1 text-[13px]"><b>{{ $postulante->telefono ?: 'Sin teléfono informado' }}</b></span></div>
            <div class="ad-toggle-row"><flux:icon.envelope class="size-4 text-gray-500" /><span class="flex-1 text-[13px]"><b>{{ $postulante->user->email }}</b></span></div>
            <div class="ad-toggle-row"><flux:icon.map-pin class="size-4 text-gray-500" /><span class="flex-1 text-[13px]"><b>{{ $postulante->ciudad ?: 'Chile' }}</b></span></div>
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
            <p class="text-[13px] text-gray-700">Necesitas una suscripción de empresa activa para revelar RUT, teléfono y correo.</p>
            @endif
        </div></aside>
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
