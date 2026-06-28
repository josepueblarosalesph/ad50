<div>
    <x-slot:context>Empresa</x-slot:context>
    <x-slot:nav>
        <a href="{{ route('empresa.panel') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-gray-500 hover:text-ink">Panel</a>
        <a href="{{ route('empresa.resultados', $match->busqueda) }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-ink bg-orange-100">Búsquedas</a>
        <a href="{{ route('planes') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-gray-500 hover:text-ink">Mi plan</a>
    </x-slot:nav>
    <x-slot:sidebar>
        <div class="text-[10.5px] tracking-[0.12em] uppercase text-gray-400 font-bold px-2.5 mb-2">Candidato</div>
        <a href="{{ route('empresa.resultados', $match->busqueda) }}" class="flex items-center gap-3 text-[14px] font-semibold px-3 py-2.5 rounded-[10px] text-gray-700 hover:bg-paper"><flux:icon.arrow-left class="size-[18px]" />Volver a resultados</a>
        <a href="#contacto" class="flex items-center gap-3 text-[14px] font-semibold px-3 py-2.5 rounded-[10px] bg-orange-100 text-orange-600"><flux:icon.user class="size-[18px]" />Ficha profesional</a>
    </x-slot:sidebar>

    @php($postulante = $match->postulante)
    <a href="{{ route('empresa.resultados', $match->busqueda) }}" class="inline-flex items-center gap-2 text-[13px] font-semibold text-gray-500 mb-5"><flux:icon.arrow-left class="size-4" />Volver a resultados</a>

    <div class="flex items-center justify-between gap-5 mb-6 flex-wrap"><div class="flex items-center gap-4"><div class="size-16 rounded-full bg-orange-100 text-orange-600 grid place-items-center text-xl font-extrabold">{{ $postulante->user->initials() }}</div><div><h1 class="text-[24px] font-extrabold">{{ $postulante->user->initials() }} <span class="font-medium text-gray-400 text-[15px]">· perfil #{{ $postulante->id }}</span></h1><p class="text-[14px] text-gray-500 mt-1">{{ $postulante->cargo_actual ?: 'Perfil profesional' }}</p><span class="ad-chip ad-chip-green mt-2"><flux:icon.check class="size-4" />Cumple {{ $match->criterios_cumplidos }} de {{ $match->criterios_totales }} criterios</span></div></div><button class="ad-btn-ghost ad-btn-sm">Guardar candidato</button></div>

    <div class="grid lg:grid-cols-[1.4fr_0.8fr] gap-5 items-start">
        <div class="space-y-5">
            <section class="ad-card"><div class="ad-card-head"><h2 class="text-[16px] font-bold">Educación</h2></div><div class="p-6"><b class="text-[14px]">Ingeniería Comercial</b><p class="text-[13px] text-gray-500 mt-1">Universidad de Concepción</p></div></section>
            <section class="ad-card"><div class="ad-card-head"><h2 class="text-[16px] font-bold">Industrias de interés</h2></div><div class="p-6 flex flex-wrap gap-2"><span class="ad-chip ad-chip-orange">{{ $postulante->industria ?: 'Industria abierta' }}</span><span class="ad-chip">Servicios profesionales</span></div></section>
            <section class="ad-card"><div class="ad-card-head"><h2 class="text-[16px] font-bold">Experiencia</h2><span class="ad-chip ad-chip-orange ad-chip-dot">{{ $postulante->anios_experiencia }} años</span></div><div class="p-6"><b class="text-[14px]">{{ $postulante->cargo_actual ?: 'Trayectoria profesional' }}</b><p class="text-[13px] text-gray-500 mt-2 leading-relaxed">Experiencia liderando equipos, procesos de planificación y gestión en organizaciones de la industria {{ Str::lower($postulante->industria ?: 'nacional') }}.</p></div></section>
        </div>

        <aside id="contacto" class="ad-card border-[#BFE6CD] overflow-hidden"><div class="ad-card-head bg-match-100/50"><h2 class="text-[16px] font-bold flex items-center gap-2"><flux:icon.lock-open class="size-4 text-match" />Datos de contacto</h2><span class="ad-chip ad-chip-green ad-chip-dot">Visible</span></div><div class="p-5 space-y-3">
            <div class="ad-toggle-row"><flux:icon.phone class="size-4 text-gray-500" /><span class="flex-1 text-[13px]"><b>{{ $postulante->telefono ?: 'Sin teléfono informado' }}</b></span></div>
            <div class="ad-toggle-row"><flux:icon.envelope class="size-4 text-gray-500" /><span class="flex-1 text-[13px]"><b>{{ $postulante->user->email }}</b></span></div>
            <div class="ad-toggle-row"><flux:icon.map-pin class="size-4 text-gray-500" /><span class="flex-1 text-[13px]"><b>{{ $postulante->ciudad ?: 'Chile' }}</b></span></div>
            <p class="text-[11.5px] text-gray-500 flex gap-2"><flux:icon.information-circle class="size-3.5 flex-none" />El acceso queda registrado para fines de privacidad y auditoría.</p>
        </div></aside>
    </div>
</div>
