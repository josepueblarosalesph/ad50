<div class="ad-panel">
    <x-slot:context>Empresa</x-slot:context>
    <x-slot:nav>
        <a wire:navigate href="{{ route('empresa.panel') }}" class="rounded-lg px-3.5 py-2 text-[13.5px] font-semibold text-gray-500 hover:text-ink">Mi Panel</a>
        <a wire:navigate href="{{ route('empresa.busquedas.index') }}" class="rounded-lg px-3.5 py-2 text-[13.5px] font-semibold text-gray-500 hover:text-ink">Mis Procesos</a>
        <a wire:navigate href="{{ route('empresa.publicaciones.index') }}" class="rounded-lg bg-orange-100 px-3.5 py-2 text-[13.5px] font-semibold text-ink">Publicaciones</a>
        @if (auth()->user()->esPrincipalEmpresa())
            <a wire:navigate href="{{ route('empresa.equipo') }}" class="rounded-lg px-3.5 py-2 text-[13.5px] font-semibold text-gray-500 hover:text-ink">Equipo</a>
        @endif
    </x-slot:nav>

    @if (session('status'))
        <div class="mb-5 rounded-xl border border-[#BFE6CD] bg-match-100 px-4 py-3 text-[13px] font-bold text-match">{{ session('status') }}</div>
    @endif

    <div class="mb-6 flex flex-wrap items-start justify-between gap-5">
        <div>
            <span class="ad-eyebrow">Portal laboral</span>
            <h1 class="mt-3 text-[30px] font-extrabold">Publicaciones</h1>
            <p class="mt-2 text-[14px] text-gray-500">Administra las oportunidades visibles para los postulantes.</p>
        </div>
        <a wire:navigate href="{{ route('empresa.publicaciones.create') }}" class="ad-btn-primary ad-btn-sm"><flux:icon.plus class="size-4" />Nueva publicación</a>
    </div>

    <section class="ad-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-[14px]">
                <thead><tr class="ad-thead-row"><th class="p-4">Publicación</th><th class="p-4">Ubicación</th><th class="p-4">Postulaciones</th><th class="p-4">Vigencia</th><th class="p-4">Estado</th><th class="p-4"></th></tr></thead>
                <tbody>
                    @forelse ($publicaciones as $publicacion)
                        <tr wire:key="publicacion-{{ $publicacion->id }}" class="border-b border-line last:border-0">
                            <td class="p-4"><a wire:navigate href="{{ route('empresa.publicaciones.postulaciones', $publicacion) }}" class="rounded-lg font-bold text-ink underline decoration-orange-300 underline-offset-4 transition hover:text-orange-600 hover:decoration-orange-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-600">{{ $publicacion->cargo }}</a><p class="mt-1 text-[12px] text-gray-500">{{ $publicacion->tipo_cargo }} · {{ $publicacion->vacantes }} vacante(s)</p></td>
                            <td class="p-4 text-gray-600">{{ $publicacion->comuna }} · {{ $publicacion->modalidad }}</td>
                            <td class="p-4"><a wire:navigate href="{{ route('empresa.publicaciones.postulaciones', $publicacion) }}" class="inline-flex min-w-9 items-center justify-center rounded-lg px-2 py-1 font-bold text-orange-600 underline decoration-orange-200 underline-offset-4 transition hover:bg-orange-100 hover:decoration-orange-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-600" aria-label="Ver las {{ $publicacion->postulaciones_count }} postulaciones de {{ $publicacion->cargo }}">{{ $publicacion->postulaciones_count }}</a></td>
                            <td class="p-4 text-gray-600">{{ $publicacion->vigente_hasta->translatedFormat('d M Y') }}</td>
                            <td class="p-4">
                                <select wire:change="cambiarEstado({{ $publicacion->id }}, $event.target.value)" class="rounded-lg border border-line-2 bg-paper px-2.5 py-1.5 text-[13px] font-bold">
                                    @foreach (['publicada' => 'Publicada', 'pausada' => 'Pausada', 'cerrada' => 'Cerrada'] as $valor => $etiqueta)
                                        <option value="{{ $valor }}" @selected($publicacion->estado === $valor)>{{ $etiqueta }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="p-4 text-right"><a wire:navigate href="{{ route('empresa.publicaciones.postulaciones', $publicacion) }}" class="ad-btn-primary ad-btn-sm whitespace-nowrap">Ver postulantes</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-10 text-center"><flux:icon.megaphone class="mx-auto size-8 text-gray-400" /><h2 class="mt-3 font-bold">Aún no tienes publicaciones</h2><p class="mt-2 text-[13px] text-gray-500">Publica una oportunidad para comenzar a recibir postulaciones.</p><a wire:navigate href="{{ route('empresa.publicaciones.create') }}" class="ad-btn-primary ad-btn-sm mt-4">Crear publicación</a></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($publicaciones->hasPages())
        <div class="mt-6">{{ $publicaciones->links() }}</div>
    @endif
</div>
