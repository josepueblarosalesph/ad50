<div class="ad-panel">
    <x-slot:context>Administración</x-slot:context>
    <x-slot:nav><x-nav-admin activo="mensajes" /></x-slot:nav>

    <div class="mb-6">
        <h1 class="text-[27px] font-extrabold">Mensajes</h1>
        <p class="mt-1.5 text-[14px] text-gray-500">
            {{ $totalPendientes }} {{ $totalPendientes === 1 ? 'mensaje pendiente' : 'mensajes pendientes' }} de responder.
        </p>
    </div>

    <section class="ad-card mb-5 p-4 md:p-5">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-campo-select id="filtro-estado" label="Estado" wire:model.live="estado">
                <option value="pendientes">Pendientes</option>
                <option value="todos">Todos</option>
                @foreach ($estados as $clave => $etiqueta)
                    <option value="{{ $clave }}">{{ $etiqueta }}</option>
                @endforeach
            </x-campo-select>

            <x-campo-select id="filtro-motivo" label="Motivo" wire:model.live="motivo">
                <option value="todos">Todos los motivos</option>
                @foreach ($motivos as $clave => $etiqueta)
                    <option value="{{ $clave }}">{{ $etiqueta }}@if (($pendientesPorMotivo[$clave] ?? 0) > 0) ({{ $pendientesPorMotivo[$clave] }} pendientes)@endif</option>
                @endforeach
            </x-campo-select>
        </div>
    </section>

    <div class="space-y-3">
        @forelse ($mensajes as $mensaje)
            <article wire:key="mensaje-{{ $mensaje->id }}" @class(['ad-card p-4 md:p-5', 'border-l-[3px] border-l-orange-500' => $mensaje->estado === 'nuevo'])>
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="ad-chip ad-chip-sm ad-chip-orange">{{ $mensaje->motivoLabel() }}</span>
                            <span @class([
                                'ad-chip ad-chip-sm',
                                'ad-chip-gray' => $mensaje->estado !== 'respondido',
                                'ad-chip-green' => $mensaje->estado === 'respondido',
                            ])>{{ $mensaje->estadoLabel() }}</span>
                            <span class="text-[12px] text-gray-400">{{ $mensaje->created_at->translatedFormat('j M Y, H:i') }}</span>
                        </div>
                        <p class="mt-2 truncate text-[14px] font-bold text-ink">
                            {{ $mensaje->nombre }}
                            <span class="font-normal text-gray-500">· {{ $mensaje->email }}</span>
                            @if ($mensaje->user === null)
                                <span class="text-[12px] font-semibold text-gray-400">(cuenta eliminada)</span>
                            @endif
                        </p>
                        <p class="mt-1 line-clamp-2 text-[13px] leading-relaxed text-gray-600 dark:text-gray-300">{{ $mensaje->mensaje }}</p>
                    </div>

                    <div class="flex flex-none flex-wrap items-center gap-2">
                        <button type="button" wire:click="abrir({{ $mensaje->id }})" class="ad-btn-ghost ad-btn-sm">Leer</button>
                        @if ($mensaje->estaPendiente())
                            <button type="button" wire:click="marcarRespondido({{ $mensaje->id }})" class="ad-btn-primary ad-btn-sm whitespace-nowrap">Marcar respondido</button>
                        @else
                            <button type="button" wire:click="reabrir({{ $mensaje->id }})" class="ad-btn-ghost ad-btn-sm whitespace-nowrap">Reabrir</button>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div class="ad-card p-10 text-center">
                <flux:icon.inbox class="mx-auto size-8 text-gray-400" />
                <h2 class="mt-3 font-bold">No hay mensajes con estos filtros</h2>
                <p class="mt-2 text-[13px] text-gray-500">Cuando alguien escriba desde la pantalla de Ayuda, aparecerá aquí.</p>
            </div>
        @endforelse
    </div>

    @if ($mensajes->hasPages())
        <div class="mt-6">{{ $mensajes->links() }}</div>
    @endif

    <flux:modal name="mensaje-contacto" class="w-full max-w-2xl" wire:close="cerrar">
        @if ($abierto)
            <div class="space-y-5">
                <div>
                    <flux:heading size="lg">{{ $abierto->motivoLabel() }}</flux:heading>
                    <flux:text class="mt-1.5">
                        {{ $abierto->nombre }} · {{ $abierto->email }} ·
                        {{ $abierto->created_at->translatedFormat('j \d\e F \d\e Y, H:i') }}
                    </flux:text>
                </div>

                <div class="max-h-80 overflow-y-auto whitespace-pre-line rounded-xl border border-line-2 bg-paper p-4 text-[13.5px] leading-relaxed text-gray-700 dark:bg-[#222528] dark:text-gray-300">{{ $abierto->mensaje }}</div>

                <div class="flex flex-wrap justify-end gap-2">
                    {{-- mailto y no un formulario de respuesta: se contesta desde el correo
                         de siempre, y aquí solo se deja constancia de que ya se hizo. --}}
                    <flux:button
                        :href="'mailto:'.$abierto->email.'?subject='.rawurlencode('AD+50 · '.$abierto->motivoLabel())"
                        variant="ghost"
                        icon="envelope"
                    >Responder por correo</flux:button>

                    @if ($abierto->estaPendiente())
                        <flux:button wire:click="marcarRespondido({{ $abierto->id }})" variant="primary">Marcar como respondido</flux:button>
                    @else
                        <flux:button wire:click="cerrar" variant="primary">Cerrar</flux:button>
                    @endif
                </div>
            </div>
        @endif
    </flux:modal>
</div>
