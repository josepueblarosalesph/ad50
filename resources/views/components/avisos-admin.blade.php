{{--
    Campana de avisos de la administración: dice cuándo ha llegado un mensaje de contacto
    sin tener que entrar a la bandeja a mirar.

    Solo se dibuja para admin y superadmin. Para el resto de roles no existe: la bandeja
    de Ayuda es un buzón interno, no una notificación de cada usuario.

    Dos números distintos a propósito:
      · el globo cuenta los SIN LEER, que es lo que responde a «¿llegó algo nuevo?»;
      · el pie del desplegable cuenta los PENDIENTES (sin leer + leídos sin responder),
        que es el trabajo que queda por hacer.
    Si el globo contara los pendientes, un mensaje ya visto seguiría avisando como si
    acabara de entrar y la campana dejaría de significar nada.

    El estado es de la administración entera, no de cada admin (así está modelado
    MensajeContacto): lo que un admin marca como leído deja de avisar a todos.
--}}

@php
    $esAdmin = auth()->user()?->esAdmin() ?? false;

    $sinLeer = $esAdmin
        ? \App\Models\MensajeContacto::query()->where('estado', 'nuevo')->latest()->take(5)->get()
        : collect();

    $totalSinLeer = $esAdmin ? \App\Models\MensajeContacto::query()->where('estado', 'nuevo')->count() : 0;
    $totalPendientes = $esAdmin ? \App\Models\MensajeContacto::query()->pendientes()->count() : 0;
@endphp

@if ($esAdmin)
    <flux:dropdown align="end">
        <button
            type="button"
            class="ad-btn-icon relative"
            aria-label="{{ $totalSinLeer === 0
                ? 'Avisos: no hay mensajes sin leer'
                : ($totalSinLeer === 1 ? 'Avisos: 1 mensaje sin leer' : "Avisos: {$totalSinLeer} mensajes sin leer") }}"
        >
            <flux:icon.bell class="size-[18px]" />

            @if ($totalSinLeer > 0)
                {{-- aria-hidden: la cuenta ya va en el aria-label del botón; anunciarla
                     dos veces solo alarga lo que oye quien usa lector de pantalla. --}}
                <span
                    aria-hidden="true"
                    class="absolute -right-1.5 -top-1.5 grid min-w-[18px] place-items-center rounded-full bg-orange-600 px-1 text-[10.5px] font-extrabold leading-[18px] text-white"
                >{{ $totalSinLeer > 9 ? '9+' : $totalSinLeer }}</span>
            @endif
        </button>

        <flux:menu class="min-w-[280px]">
            @forelse ($sinLeer as $mensaje)
                <flux:menu.item :href="route('admin.mensajes')" icon="envelope">
                    <span class="block truncate font-semibold">{{ $mensaje->nombre }}</span>
                    <span class="block truncate text-[12px] text-gray-500">
                        {{ $mensaje->motivoLabel() }} · {{ $mensaje->created_at?->diffForHumans() }}
                    </span>
                </flux:menu.item>
            @empty
                <div class="px-3 py-4 text-center text-[13px] text-gray-500">
                    No hay mensajes sin leer.
                </div>
            @endforelse

            <flux:menu.separator />

            <flux:menu.item :href="route('admin.mensajes')" icon="inbox">
                Ver la bandeja
                @if ($totalPendientes > 0)
                    ({{ $totalPendientes }} {{ $totalPendientes === 1 ? 'pendiente' : 'pendientes' }})
                @endif
            </flux:menu.item>
        </flux:menu>
    </flux:dropdown>
@endif
