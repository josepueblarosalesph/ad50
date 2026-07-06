<div class="ad-panel">
    <x-slot:context>Administración</x-slot:context>
    <x-slot:status>Acceso interno</x-slot:status>
    <x-slot:nav>
        <a href="{{ route('admin.panel') }}" class="rounded-lg px-3.5 py-2 text-[13.5px] font-semibold text-gray-500 hover:text-ink">Resumen</a>
        <a href="{{ route('admin.empresas') }}" class="rounded-lg bg-orange-100 px-3.5 py-2 text-[13.5px] font-semibold text-ink">Empresas</a>
    </x-slot:nav>

    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div><h1 class="text-[27px] font-extrabold">Activación de empresas</h1><p class="mt-1.5 text-[14px] text-gray-500">Revisa antecedentes y habilita manualmente las cuentas verificadas.</p></div>
        <span class="ad-chip ad-chip-orange ad-chip-dot">{{ $pendientes->count() }} pendientes</span>
    </div>

    @if (session('status'))
        <div class="mb-5 rounded-xl border border-[#BFE6CD] bg-match-100 px-4 py-3 text-[13px] font-bold text-match">{{ session('status') }}</div>
    @endif

    <section class="space-y-4">
        <div class="flex items-center justify-between"><h2 class="text-[18px] font-extrabold">Pendientes de revisión</h2><span class="text-[13px] font-semibold text-gray-500">{{ $pendientes->count() }} solicitudes</span></div>
        @forelse ($pendientes as $empresa)
            <article class="ad-card p-6" wire:key="empresa-pendiente-{{ $empresa->id }}">
                <div class="flex flex-wrap items-start justify-between gap-4 border-b border-line pb-5">
                    <div><h3 class="text-[21px] font-extrabold">{{ $empresa->razon_social }}</h3><p class="mt-1 text-[13px] text-gray-500">RUT {{ $empresa->rut }} · {{ $empresa->rubro }}</p></div>
                    <span class="ad-chip ad-chip-orange">Pendiente desde {{ $empresa->datos_enviados_at?->translatedFormat('d M Y') }}</span>
                </div>
                <div class="grid gap-5 py-5 md:grid-cols-3">
                    <div><p class="text-[11px] font-extrabold uppercase tracking-wide text-gray-400">Cuenta</p><p class="mt-2 text-[14px] font-bold">{{ $empresa->user->name }}</p><p class="text-[13px] text-gray-500">{{ $empresa->user->email }}</p></div>
                    <div><p class="text-[11px] font-extrabold uppercase tracking-wide text-gray-400">Contacto principal</p><p class="mt-2 text-[14px] font-bold">{{ $empresa->contacto_principal_nombre }}</p><p class="text-[13px] text-gray-500">{{ $empresa->contacto_principal_cargo }} · {{ $empresa->contacto_principal_email }} · {{ $empresa->contacto_principal_telefono }}</p></div>
                    <div><p class="text-[11px] font-extrabold uppercase tracking-wide text-gray-400">Contacto técnico</p><p class="mt-2 text-[14px] font-bold">{{ $empresa->contacto_tecnico_nombre }}</p><p class="text-[13px] text-gray-500">{{ $empresa->contacto_tecnico_email }} · {{ $empresa->contacto_tecnico_telefono }}</p></div>
                </div>
                <div class="flex justify-end border-t border-line pt-4">
                    <button type="button" wire:click="activar({{ $empresa->id }})" wire:confirm="¿Confirmas que revisaste los antecedentes y deseas habilitar esta empresa?" class="ad-btn-primary ad-btn-sm"><flux:icon.check-badge class="size-4" />Habilitar empresa</button>
                </div>
            </article>
        @empty
            <div class="ad-card p-10 text-center"><flux:icon.check-circle class="mx-auto size-8 text-match" /><h3 class="mt-3 font-bold">No hay empresas pendientes</h3><p class="mt-1 text-[13px] text-gray-500">Las nuevas solicitudes aparecerán aquí.</p></div>
        @endforelse
    </section>

    <div class="mt-8 grid gap-5 lg:grid-cols-2">
        <section class="ad-card overflow-hidden"><div class="ad-card-head"><h2 class="text-[16px] font-bold">Aún sin antecedentes</h2><span class="ad-chip">{{ $inactivas->count() }}</span></div><div class="divide-y divide-line">@forelse ($inactivas as $empresa)<div class="p-4"><b class="text-[14px]">{{ $empresa->razon_social }}</b><p class="mt-1 text-[12px] text-gray-500">{{ $empresa->user->email }}</p></div>@empty<p class="p-6 text-[13px] text-gray-500">Todas las empresas enviaron sus datos.</p>@endforelse</div></section>
        <section class="ad-card overflow-hidden"><div class="ad-card-head"><h2 class="text-[16px] font-bold">Empresas activas</h2><span class="ad-chip ad-chip-green">{{ $activas->count() }}</span></div><div class="divide-y divide-line">@forelse ($activas as $empresa)<div class="p-4"><b class="text-[14px]">{{ $empresa->razon_social }}</b><p class="mt-1 text-[12px] text-gray-500">Habilitada {{ $empresa->activada_at?->translatedFormat('d M Y') }}{{ $empresa->activadaPor ? ' por '.$empresa->activadaPor->name : '' }}</p></div>@empty<p class="p-6 text-[13px] text-gray-500">Todavía no hay empresas activas.</p>@endforelse</div></section>
    </div>
</div>
