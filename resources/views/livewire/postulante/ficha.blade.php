<div>
    <x-slot:context>Postulante</x-slot:context>
    <x-slot:nav>
        <a href="{{ route('postulante.panel') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-gray-500 hover:text-ink">Mi panel</a>
        <a href="{{ route('postulante.ficha') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-ink bg-orange-100">Mi ficha</a>
        <a href="{{ route('postulante.panel') }}#coincidencias" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-gray-500 hover:text-ink">Búsquedas que me incluyen</a>
        <a href="{{ route('planes') }}" class="text-[13.5px] font-semibold px-3.5 py-2 rounded-lg text-gray-500 hover:text-ink">Mi suscripción</a>
    </x-slot:nav>
    <x-slot:sidebar>
        <div class="text-[10.5px] tracking-[0.12em] uppercase text-gray-400 font-bold px-2.5 mb-2">Mi ficha</div>
        @foreach ([['user','Datos personales', 'datos-personales'], ['academic-cap','Educación', 'educacion'], ['building-office-2','Industrias de interés', 'industrias'], ['briefcase','Experiencia', 'experiencia']] as [$icon, $label, $anchor])
            <a href="#{{ $anchor }}" @class(['flex items-center gap-3 text-[14px] font-semibold px-3 py-2.5 rounded-[10px]', 'bg-orange-100 text-orange-600' => $loop->first, 'text-gray-700 hover:bg-paper' => !$loop->first])><flux:icon :name="$icon" class="size-[18px]" />{{ $label }}</a>
        @endforeach
    </x-slot:sidebar>

    <form wire:submit="save">
        <div class="flex items-start justify-between gap-5 mb-6 flex-wrap">
            <div><h1 class="text-[27px] font-extrabold">Mi ficha profesional</h1><p class="text-[14px] text-gray-500 mt-1.5">Completa tus datos para aparecer en las búsquedas de empresas.</p></div>
            <button type="submit" class="ad-btn-primary ad-btn-sm" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">Guardar cambios</span>
                <span wire:loading wire:target="save">Guardando…</span>
            </button>
        </div>

        @if (session('status'))
            <div class="mb-5 rounded-[10px] border border-[#BFE6CD] bg-match-100 px-4 py-3 text-[13px] font-semibold text-match">{{ session('status') }}</div>
        @endif

        <div class="ad-card p-5 mb-5 flex flex-wrap items-center gap-6">
            <div class="flex-1 min-w-60"><div class="flex justify-between text-[12.5px] font-semibold mb-2"><span>Completitud de tu ficha</span><span class="text-orange-600">{{ $completitud }}%</span></div><div class="h-2 rounded-full bg-line overflow-hidden"><div class="h-full bg-gradient-to-r from-orange-500 to-[#F59A53]" style="width: {{ $completitud }}%"></div></div></div>
            <div class="ad-toggle-row min-w-64"><div><b class="text-[13.5px] block">Visibilidad del perfil</b><span class="text-[12px] text-gray-500">{{ $visible ? 'Activo — visible para empresas' : 'Perfil pausado' }}</span></div><flux:switch wire:model.live="visible" /></div>
        </div>

        <section id="datos-personales" class="ad-card scroll-mt-24">
            <div class="ad-card-head"><h2 class="text-[16px] font-bold">Datos personales</h2><span class="text-[11px] font-bold text-orange-500 uppercase tracking-wider">Sección 1 de 4</span></div>
            <div class="p-6 grid md:grid-cols-2 gap-4">
                <flux:input wire:model="name" label="Nombre completo *" />
                <flux:input wire:model="rut" label="RUT *" placeholder="9.842.115-6" description="Identificador único para evitar perfiles duplicados." />
                <flux:input wire:model="anioNacimiento" type="number" min="1900" max="{{ now()->year }}" label="Año de nacimiento *" />
                <flux:input wire:model="telefono" label="Teléfono" placeholder="+56 9 5555 1234" />
                <flux:input wire:model="email" type="email" label="Email *" />
                <flux:input wire:model="linkedin" type="url" label="LinkedIn" placeholder="https://linkedin.com/in/..." />
                <flux:input wire:model="ciudad" label="Ciudad" placeholder="Concepción" />
            </div>
            <div class="px-6 pb-6 flex gap-2 text-[11.5px] text-gray-500"><flux:icon.lock-closed class="size-4 flex-none" />Tu RUT, teléfono y email solo se muestran a empresas con una suscripción activa.</div>
        </section>

        <section id="educacion" class="ad-card mt-5 scroll-mt-24">
            <div class="ad-card-head"><h2 class="text-[16px] font-bold">Educación</h2><span class="text-[11px] font-bold text-orange-500 uppercase tracking-wider">Sección 2 de 4</span></div>
            <div class="p-6 grid md:grid-cols-2 gap-4">
                <flux:input wire:model="carrera" label="Título o carrera *" placeholder="Ingeniería Comercial" />
                <flux:input wire:model="universidad" label="Universidad o institución *" placeholder="Universidad de Concepción" />
                <flux:input wire:model="especialidad" label="Especialidad o área *" placeholder="Finanzas" />
                <flux:input wire:model="postgrado" label="Postgrado" placeholder="MBA" />
            </div>
        </section>

        <section id="industrias" class="ad-card mt-5 scroll-mt-24">
            <div class="ad-card-head"><h2 class="text-[16px] font-bold">Industrias de interés</h2><span class="text-[11px] font-bold text-orange-500 uppercase tracking-wider">Sección 3 de 4</span></div>
            <div class="p-6 grid md:grid-cols-3 gap-4">
                <flux:input wire:model="industria" label="Industria 1 *" placeholder="Banca y servicios financieros" />
                <flux:input wire:model="industria2" label="Industria 2" placeholder="Forestal / Papelera" />
                <flux:input wire:model="industria3" label="Industria 3" placeholder="Manufactura" />
            </div>
        </section>

        <section id="experiencia" class="ad-card mt-5 scroll-mt-24 border-l-[3px] border-l-orange-500">
            <div class="ad-card-head"><h2 class="text-[16px] font-bold">Experiencia principal</h2><span class="ad-chip ad-chip-orange ad-chip-dot">Más relevante</span></div>
            <div class="p-6 grid md:grid-cols-2 xl:grid-cols-3 gap-4">
                <flux:input class="xl:col-span-2" wire:model="cargoActual" label="Cargo *" placeholder="Subgerente de Administración y Finanzas" />
                <flux:input wire:model="empresaActual" label="Empresa *" placeholder="Empresa actual o más reciente" />
                <flux:input wire:model="experienciaArea" label="Área o especialidad *" placeholder="Finanzas" />
                <flux:input wire:model="experienciaInicio" type="number" min="1950" max="{{ now()->year }}" label="Año de inicio *" />
                <flux:input wire:model="experienciaFin" type="number" min="1950" max="{{ now()->year }}" label="Año de término" description="Déjalo vacío si es tu cargo actual." />
                <flux:input wire:model="aniosExperiencia" type="number" min="0" max="80" label="Años totales de experiencia *" />
                <flux:textarea class="md:col-span-2 xl:col-span-3" wire:model="resumenProfesional" label="Resumen profesional" placeholder="Describe tus principales responsabilidades y logros." rows="5" />
            </div>
        </section>

        <div class="ad-card mt-5 p-5 flex flex-wrap items-center justify-between gap-4"><div class="flex gap-3"><flux:icon.shield-check class="size-6 text-gray-500 flex-none" /><div><b class="text-[14px]">Tú controlas tu información</b><p class="text-[12.5px] text-gray-500 mt-1">Puedes editarla, pausar tu visibilidad o solicitar su eliminación.</p></div></div><button type="submit" class="ad-btn-primary ad-btn-sm">Guardar toda la ficha</button></div>
    </form>
</div>
