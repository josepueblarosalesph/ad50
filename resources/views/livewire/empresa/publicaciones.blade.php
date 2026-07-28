<div class="ad-panel">
    <x-slot:context>Empresa</x-slot:context>
    <x-slot:nav><x-nav-empresa activo="publicaciones" /></x-slot:nav>

    @if (session('status'))
        <div class="mb-5 rounded-xl border border-[#BFE6CD] bg-match-100 px-4 py-3 text-[13px] font-bold text-match">{{ session('status') }}</div>
    @endif

    @if (session('publicacion_error'))
        <div class="mb-5 flex flex-wrap items-center justify-between gap-x-4 gap-y-2 rounded-xl border border-[#F5C6C0] bg-[#FDECEA] px-4 py-3 text-[13px] font-bold text-[#A93226] dark:bg-[#3A2523]">
            <span>{{ session('publicacion_error') }}</span>
            <a wire:navigate href="{{ route('empresa.planes') }}" class="underline underline-offset-2">Ver planes</a>
        </div>
    @endif

    @if ($eliminadoId)
        <div class="mb-5 flex flex-wrap items-center justify-between gap-x-4 gap-y-2 rounded-xl border border-line-2 bg-paper px-4 py-3">
            <div class="flex items-center gap-2 text-[13px] text-gray-700">
                <flux:icon.trash class="size-4 flex-none text-gray-400" />
                <span>Eliminaste la publicación <b class="text-ink">«{{ $eliminadoCargo }}»</b>.</span>
                <button type="button" wire:click="restaurar" wire:loading.attr="disabled" wire:target="restaurar" class="font-bold text-orange-600 underline underline-offset-2 hover:text-orange-700">Deshacer</button>
            </div>
            <span class="text-[12px] text-gray-500">Esta publicación se eliminará en forma definitiva en los siguientes {{ \App\Models\Publicacion::DIAS_RETENCION_PAPELERA }} días.</span>
        </div>
    @endif

    <div class="mb-6 flex flex-wrap items-start justify-between gap-5">
        <div>
            <span class="ad-eyebrow">Portal laboral</span>
            <h1 class="mt-3 text-[30px] font-extrabold">Publicaciones</h1>
            <p class="mt-2 text-[14px] text-gray-500">Administra las oportunidades visibles para los postulantes.</p>
            <p class="mt-2 text-[13px] font-semibold text-gray-600">
                @if ($publicacionesTotales === null)
                    <flux:icon.megaphone class="inline size-4 text-orange-500" /> Publicaciones ilimitadas en tu plan.
                @else
                    <flux:icon.megaphone class="inline size-4 text-orange-500" />
                    Usaste {{ $publicacionesUsadas }} de {{ $publicacionesTotales }} publicaciones de tu plan
                    <span class="text-gray-500">({{ $publicacionesDisponibles }} disponibles).</span>
                @endif
            </p>
        </div>
        @if ($puedePublicar)
            <a wire:navigate href="{{ route('empresa.publicaciones.create') }}" class="ad-btn-primary ad-btn-sm"><flux:icon.plus class="size-4" />Nueva publicación</a>
        @else
            <a wire:navigate href="{{ route('empresa.planes') }}" class="ad-btn-primary ad-btn-sm"><flux:icon.credit-card class="size-4" />Ampliar plan para publicar</a>
        @endif
    </div>

    <section class="ad-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-[14px]">
                <thead><tr class="ad-thead-row"><th class="p-4">Publicación</th><th class="p-4">Ubicación</th><th class="p-4">Postulaciones</th><th class="p-4">Vigencia</th><th class="p-4">Estado</th><th class="p-4"></th></tr></thead>
                <tbody>
                    @forelse ($publicaciones as $publicacion)
                        <tr wire:key="publicacion-{{ $publicacion->id }}" class="border-b border-line last:border-0">
                            <td class="p-4"><a wire:navigate href="{{ route('empresa.publicaciones.show', $publicacion) }}" class="rounded-lg font-bold text-ink underline decoration-orange-300 underline-offset-4 transition hover:text-orange-600 hover:decoration-orange-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-600">{{ $publicacion->cargo }}</a><p class="mt-1 text-[12px] text-gray-500">{{ $publicacion->tipo_cargo }} · {{ $publicacion->vacantes }} vacante(s)</p></td>
                            <td class="p-4 text-gray-600">{{ $publicacion->comuna }} · {{ $publicacion->modalidad }}</td>
                            <td class="p-4"><a wire:navigate href="{{ route('empresa.publicaciones.postulaciones', $publicacion) }}" class="inline-flex min-w-9 items-center justify-center rounded-lg px-2 py-1 font-bold text-orange-600 underline decoration-orange-200 underline-offset-4 transition hover:bg-orange-100 hover:decoration-orange-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-600" aria-label="Ver las {{ $publicacion->postulaciones_count }} postulaciones de {{ $publicacion->cargo }}">{{ $publicacion->postulaciones_count }}</a></td>
                            <td class="p-4 text-gray-600">{{ $publicacion->vigente_hasta->translatedFormat('d M Y') }}</td>
                            <td class="p-4">
                                <select
                                    wire:key="estado-{{ $publicacion->id }}"
                                    wire:change="cambiarEstado({{ $publicacion->id }}, $event.target.value)"
                                    aria-label="Estado de la publicación {{ $publicacion->cargo }}"
                                    @class([
                                        'rounded-lg border px-2.5 py-1.5 text-[13px] font-bold focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500',
                                        'border-[#BFE6CD] bg-match-100 text-match' => $publicacion->estaVigente(),
                                        'border-line-2 bg-paper text-gray-600' => ! $publicacion->estaVigente(),
                                    ])
                                >
                                    @foreach ($estados as $valor => $etiqueta)
                                        <option value="{{ $valor }}" @selected($publicacion->estado === $valor)>{{ $etiqueta }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="p-4 text-right"><div class="flex justify-end gap-4 whitespace-nowrap"><a wire:navigate href="{{ route('empresa.publicaciones.show', $publicacion) }}" class="font-bold text-orange-600 hover:text-orange-700">Ver</a><a wire:navigate href="{{ route('empresa.publicaciones.edit', $publicacion) }}" class="font-bold text-gray-500 hover:text-ink">Editar</a><button type="button" wire:click="confirmarBorrado({{ $publicacion->id }})" class="font-bold text-[#A93226] hover:text-red-700 dark:text-red-400">Borrar</button></div></td>
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

    {{-- Confirmación de borrado: reemplaza el confirm nativo del navegador. --}}
    <flux:modal name="borrar-publicacion" class="max-w-lg" wire:close="$set('confirmacionTexto', '')">
        <div class="space-y-4">
            <div class="flex items-start gap-3">
                <span class="grid size-10 flex-none place-items-center rounded-xl bg-red-100 text-[#A93226] dark:bg-red-950/40 dark:text-red-400"><flux:icon.trash class="size-5" /></span>
                <div class="min-w-0">
                    <flux:heading size="lg">Eliminar publicación</flux:heading>
                    @if ($borrandoCargo !== '')
                        <flux:text class="mt-1 truncate">«{{ $borrandoCargo }}»</flux:text>
                    @endif
                </div>
            </div>

            <flux:text>La oferta dejará de estar visible en el portal y se archivarán las postulaciones que recibió.</flux:text>

            <flux:text>Para confirmar, escribe <strong class="font-bold text-ink">ELIMINAR</strong> en el siguiente cuadro y haz clic en Aceptar.</flux:text>

            <flux:input wire:model.live.debounce.200ms="confirmacionTexto" placeholder="ELIMINAR" autocomplete="off" autofocus />
            @error('confirmacionTexto')<flux:text class="text-[#A93226] dark:text-red-400">{{ $message }}</flux:text>@enderror

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="ghost">Cancelar</flux:button></flux:modal.close>
                <flux:button variant="danger" wire:click="borrar" wire:loading.attr="disabled" wire:target="borrar" :disabled="mb_strtoupper(trim($confirmacionTexto)) !== 'ELIMINAR'">Aceptar</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
