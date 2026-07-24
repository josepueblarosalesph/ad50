<div class="ad-panel">
    <x-slot:context>Empresa</x-slot:context>
    <x-slot:status>{{ $empresa?->plan?->nombre ?? 'Sin plan' }}</x-slot:status>
    <x-slot:nav>
        <a wire:navigate href="{{ route('empresa.panel') }}" class="rounded-lg px-3.5 py-2 text-[13.5px] font-semibold text-gray-500 hover:text-ink">Mi Panel</a>
        <a wire:navigate href="{{ route('empresa.busquedas.index') }}" class="rounded-lg px-3.5 py-2 text-[13.5px] font-semibold text-gray-500 hover:text-ink">Mis Procesos</a>
        <a wire:navigate href="{{ route('empresa.equipo') }}" class="rounded-lg bg-orange-100 px-3.5 py-2 text-[13.5px] font-semibold text-ink">Equipo</a>
    </x-slot:nav>

    <div class="mx-auto max-w-4xl">
        <div class="mb-6">
            <span class="ad-eyebrow">Usuarios de la empresa</span>
            <h1 class="mt-3 text-[30px] font-extrabold">Equipo de {{ $empresa->razon_social }}</h1>
            <p class="mt-2 max-w-2xl text-[14px] leading-relaxed text-gray-500">
                Puedes sumar hasta {{ \App\Models\Empresa::MAX_USUARIOS_ADICIONALES }} usuarios adicionales que comparten el acceso al panel.
                Se agregan de a uno. Te quedan <b class="text-ink">{{ $disponibles }}</b> disponible(s).
            </p>
        </div>

        @if (session('status'))
            <div class="mb-5 rounded-xl border border-[#BFE6CD] bg-match-100 px-4 py-3 text-[13px] font-bold text-match">{{ session('status') }}</div>
        @endif

        {{-- Listado del equipo --}}
        <section class="ad-card mb-6 overflow-hidden">
            <div class="ad-card-head"><h2 class="text-[16px] font-bold">Usuarios con acceso</h2></div>
            <div class="overflow-x-auto">
                <table class="w-full text-[14px]">
                    <thead>
                        <tr class="ad-thead-row">
                            <th class="p-4">Nombre</th>
                            <th class="p-4">Email</th>
                            <th class="p-4">Rol</th>
                            <th class="p-4"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-line">
                            <td class="p-4 font-semibold">{{ $principal->name }}</td>
                            <td class="p-4 text-gray-500">{{ $principal->email }}</td>
                            <td class="p-4"><span class="ad-chip ad-chip-orange">Contacto principal</span></td>
                            <td class="p-4"></td>
                        </tr>
                        @foreach ($adicionales as $usuario)
                            <tr class="border-b border-line last:border-0">
                                <td class="p-4 font-semibold">{{ $usuario->name }}</td>
                                <td class="p-4 text-gray-500">{{ $usuario->email }}</td>
                                <td class="p-4"><span class="ad-chip">Usuario adicional</span></td>
                                <td class="p-4 text-right">
                                    <button type="button"
                                            wire:click="eliminar({{ $usuario->id }})"
                                            wire:confirm="¿Eliminar a {{ $usuario->name }} del equipo? Perderá el acceso al panel."
                                            class="font-semibold text-red-600 hover:underline">Eliminar</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Alta de un usuario a la vez --}}
        <section class="ad-card">
            <div class="ad-card-head"><div><h2 class="text-[18px] font-extrabold">Agregar un usuario</h2><p class="mt-1 text-[13px] text-gray-500">Crea la cuenta con una contraseña temporal y compártela con la persona.</p></div></div>

            @if ($disponibles === 0)
                <div class="p-6">
                    <div class="flex gap-3 rounded-xl border border-orange-200 bg-orange-50 p-4 text-[13px] leading-relaxed text-gray-700">
                        <flux:icon.information-circle class="mt-0.5 size-5 shrink-0 text-orange-500" />
                        <p>Alcanzaste el máximo de {{ \App\Models\Empresa::MAX_USUARIOS_ADICIONALES }} usuarios adicionales. Elimina uno para poder agregar otro.</p>
                    </div>
                </div>
            @else
                <form wire:submit="agregar" class="grid gap-4 p-6 md:grid-cols-2">
                    <flux:input wire:model="nombre" label="Nombres *" />
                    <flux:input wire:model="apellidos" label="Apellidos *" />
                    <flux:input wire:model="email" type="email" label="Email corporativo *" />
                    <flux:input wire:model="password" type="password" label="Contraseña temporal *" viewable />
                    <div class="md:col-span-2 flex flex-wrap items-center justify-between gap-4">
                        <p class="max-w-xl text-[13px] leading-relaxed text-gray-500">La persona podrá cambiar su contraseña desde la configuración de su cuenta.</p>
                        <button type="submit" class="ad-btn-primary ad-btn-sm" wire:loading.attr="disabled" wire:target="agregar">
                            <span wire:loading.remove wire:target="agregar">Agregar usuario</span>
                            <span wire:loading wire:target="agregar">Agregando…</span>
                        </button>
                    </div>
                </form>
            @endif
        </section>
    </div>
</div>
