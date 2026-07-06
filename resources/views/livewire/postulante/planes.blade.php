<div class="min-h-screen bg-paper">
    <header class="border-b border-line bg-white dark:bg-[#1D2022]">
        <div class="mx-auto flex max-w-[1180px] items-center justify-between gap-4 px-6 py-4 md:px-11">
            <a href="{{ route('home') }}" class="ad-logo ad-logo-panel" aria-label="AD+50 Talento Senior"><img src="/images/ad50-logo.png" alt="AD+50 Talento Senior" class="ad-brand-logo"></a>
            <div class="flex items-center gap-3">
                <a href="{{ route('planes') }}" class="text-[13px] font-semibold text-gray-500">Planes para empresas</a>
                <a href="{{ route('registro', ['tipo' => 'postulante']) }}" class="ad-btn-primary ad-btn-sm">Crear mi perfil</a>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-[1180px] px-6 py-16 md:px-11">
        <div class="mx-auto mb-12 max-w-2xl text-center">
            <span class="ad-eyebrow">Plan para postulantes</span>
            <h1 class="mt-3 text-[34px] font-extrabold tracking-[-0.02em] md:text-[42px]">Haz visible tu experiencia</h1>
            <p class="mt-4 text-gray-500">Un único plan para formar parte del portal y conectar con empresas que buscan talento senior.</p>
        </div>

        <article class="ad-card mx-auto flex max-w-[520px] flex-col border-2 border-orange-500 p-8 shadow-card-lg">
            <span class="text-[11px] font-extrabold uppercase tracking-[.14em] text-orange-600">Membresía anual</span>
            <h2 class="mt-2 text-[24px] font-extrabold">Perfil profesional AD+50</h2>
            <div class="mt-5 text-[40px] font-extrabold">${{ number_format($plan?->precio_clp ?? 20000, 0, ',', '.') }} <small class="text-[13px] font-semibold text-gray-500">CLP</small></div>
            <p class="mb-7 text-[13px] text-gray-500">por año</p>
            <ul class="mb-8 grid gap-3">
                @foreach ($plan?->features ?? ['Perfil visible en el portal', 'Acceso a tus coincidencias', 'Oportunidades de empresas asociadas', 'Soporte por email'] as $feature)
                    <li wire:key="postulante-feature-{{ $loop->index }}" class="flex gap-2.5 text-[13.5px] text-gray-700"><flux:icon.check class="mt-0.5 size-4 shrink-0 text-match" />{{ $feature }}</li>
                @endforeach
            </ul>
            <a href="{{ route('registro', ['tipo' => 'postulante']) }}" class="ad-btn-primary ad-btn-block">Crear mi perfil</a>
        </article>
    </main>
</div>
