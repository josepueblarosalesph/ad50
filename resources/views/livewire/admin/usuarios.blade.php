<div class="ad-panel">
    <x-slot:context>Administración</x-slot:context>
    <x-slot:status>Superadministrador</x-slot:status>
    <x-slot:nav><x-nav-admin activo="usuarios" /></x-slot:nav>

    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-[27px] font-extrabold">Usuarios</h1>
            <p class="mt-1.5 text-[14px] text-gray-500">
                {{ $totalUsuarios }} {{ $totalUsuarios === 1 ? 'cuenta registrada' : 'cuentas registradas' }} en la plataforma
                @if ($totalSinVerificar > 0)
                    · <button type="button" wire:click="$set('verificacion', 'pendientes')" class="font-semibold text-orange-600 underline decoration-orange-200 underline-offset-4">{{ $totalSinVerificar }} sin verificar</button>
                @endif
            </p>
        </div>
    </div>

    @if (session('status'))
        <div class="ad-card mb-5 border-l-4 border-l-match p-4 text-[14px] font-semibold text-ink">
            {{ session('status') }}
        </div>
    @endif

    <section class="ad-card mb-5 p-4 md:p-5">
        <div class="grid gap-4 md:grid-cols-3">
            <flux:input wire:model.live.debounce.300ms="buscar" label="Buscar" placeholder="Nombre o correo" icon="magnifying-glass" />

            <div>
                <label for="filtro-rol" class="mb-1.5 block text-[12px] font-bold text-gray-600 dark:text-gray-300">Tipo de usuario</label>
                <select id="filtro-rol" wire:model.live="rol" class="w-full rounded-lg border border-line-2 bg-white px-3 py-2 text-[13.5px] font-semibold text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500 dark:bg-[#2A2D30]">
                    <option value="todos">Todos</option>
                    @foreach (\App\Models\User::ROLES as $clave => $etiqueta)
                        <option value="{{ $clave }}">{{ $etiqueta }} ({{ $conteoPorRol[$clave] ?? 0 }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="filtro-verificacion" class="mb-1.5 block text-[12px] font-bold text-gray-600 dark:text-gray-300">Correo</label>
                <select id="filtro-verificacion" wire:model.live="verificacion" class="w-full rounded-lg border border-line-2 bg-white px-3 py-2 text-[13.5px] font-semibold text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500 dark:bg-[#2A2D30]">
                    <option value="todos">Todos</option>
                    <option value="verificados">Verificados</option>
                    <option value="pendientes">Sin verificar</option>
                </select>
            </div>
        </div>

        @if ($hayFiltros)
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-line pt-3">
                <p class="text-[13px] text-gray-500">Mostrando <b class="text-ink">{{ $usuarios->total() }}</b> de {{ $totalUsuarios }} cuentas.</p>
                <button type="button" wire:click="limpiarFiltros" class="ad-btn-ghost ad-btn-sm">Limpiar filtros</button>
            </div>
        @endif
    </section>

    <section class="ad-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-[14px]">
                <thead>
                    <tr class="ad-thead-row">
                        @foreach ([
                            'name' => 'Usuario',
                            'role' => 'Tipo de usuario',
                        ] as $campo => $etiqueta)
                            <x-th-ordenable :campo="$campo" :orden="$orden" :direccion="$direccion">{{ $etiqueta }}</x-th-ordenable>
                        @endforeach
                        <th class="p-4">Ficha asociada</th>
                        <th class="p-4">Correo</th>
                        <x-th-ordenable campo="created_at" :orden="$orden" :direccion="$direccion">Registro</x-th-ordenable>
                        <th class="p-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($usuarios as $usuario)
                        <tr wire:key="usuario-{{ $usuario->id }}" class="border-b border-line last:border-0">
                            <td class="p-4">
                                <b class="block text-ink">{{ $usuario->name }}</b>
                                <a href="mailto:{{ $usuario->email }}" class="text-[13px] text-gray-500 underline decoration-orange-200 underline-offset-4 hover:text-orange-600">
                                    {{ $usuario->email }}
                                </a>
                            </td>
                            <td class="p-4">
                                <span @class([
                                    'ad-chip ad-chip-sm',
                                    'ad-chip-orange' => $usuario->esSuperadmin(),
                                    'ad-chip-gray' => ! $usuario->esSuperadmin(),
                                ])>{{ $usuario->rolLabel() }}</span>
                                @if ($usuario->id === auth()->id())
                                    <span class="mt-1 block text-[12px] text-gray-400">Tu cuenta</span>
                                @endif
                            </td>
                            <td class="p-4 text-gray-600">
                                @if ($usuario->postulante)
                                    Postulante
                                @elseif ($usuario->empresa)
                                    <span class="truncate">{{ $usuario->empresa->razon_social }}</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="p-4">
                                <span @class(['ad-chip ad-chip-sm', 'ad-chip-green' => $usuario->email_verified_at !== null])>
                                    {{ $usuario->email_verified_at !== null ? 'Verificado' : 'Sin verificar' }}
                                </span>
                            </td>
                            <td class="p-4 text-gray-600">{{ $usuario->created_at?->translatedFormat('d M Y') ?? '—' }}</td>
                            <td class="p-4">
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <x-acciones-verificacion :user="$usuario" />

                                    @if ($usuario->id === auth()->id())
                                        {{-- Degradarse a uno mismo deja la plataforma sin quién revierta el cambio. --}}
                                        <span class="text-[12.5px] text-gray-400">No editable</span>
                                    @else
                                        <button type="button" wire:click="abrirCambioRol({{ $usuario->id }})" class="ad-btn-ghost ad-btn-sm">
                                            Cambiar tipo
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-10 text-center">
                                <flux:icon.users class="mx-auto size-8 text-gray-400" />
                                <h2 class="mt-3 font-bold">{{ $hayFiltros ? 'Ninguna cuenta cumple estos filtros' : 'Todavía no hay usuarios' }}</h2>
                                @if ($hayFiltros)
                                    <button type="button" wire:click="limpiarFiltros" class="ad-btn-ghost ad-btn-sm mt-4">Limpiar filtros</button>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($usuarios->hasPages())
        <div class="mt-6">{{ $usuarios->links() }}</div>
    @endif

    <flux:modal name="cambiar-rol" class="max-w-lg" wire:close="$set('editandoId', null)">
        <form wire:submit="cambiarRol" class="space-y-5">
            <div>
                <flux:heading size="lg">Cambiar tipo de usuario</flux:heading>
                @if ($editandoNombre !== '')
                    <flux:text class="mt-1 truncate">{{ $editandoNombre }} · {{ $editandoEmail }}</flux:text>
                @endif
            </div>

            <div>
                <label for="rol-nuevo" class="mb-1.5 block text-[12px] font-bold text-gray-600 dark:text-gray-300">Tipo de usuario</label>
                <select id="rol-nuevo" wire:model="rolNuevo" class="w-full rounded-lg border border-line-2 bg-white px-3 py-2 text-[13.5px] font-semibold text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500 dark:bg-[#2A2D30]">
                    @foreach (\App\Models\User::ROLES as $clave => $etiqueta)
                        <option value="{{ $clave }}">{{ $etiqueta }}{{ $clave === $rolActual ? ' (actual)' : '' }}</option>
                    @endforeach
                </select>
                @error('rolNuevo')<p class="mt-1.5 text-[12.5px] font-semibold text-[#A93226] dark:text-red-400">{{ $message }}</p>@enderror
            </div>

            <div class="rounded-lg border border-line-2 bg-gray-50 p-3.5 text-[12.5px] text-gray-600 dark:bg-[#2A2D30] dark:text-gray-300">
                Solo cambia el tipo de cuenta. La ficha de postulante o la empresa asociada
                <b>no se eliminan</b>, así que el cambio se puede revertir. La persona
                completará lo que falte del nuevo rol la próxima vez que entre.
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close><flux:button variant="ghost" type="button">Cancelar</flux:button></flux:modal.close>
                <flux:button variant="primary" type="submit" wire:loading.attr="disabled" wire:target="cambiarRol">Guardar cambio</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
