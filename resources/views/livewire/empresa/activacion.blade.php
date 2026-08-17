<div class="ad-panel">
    <x-slot:context>Empresa</x-slot:context>
    <x-slot:status>Activación de cuenta</x-slot:status>
    <x-slot:nav>
        <a href="{{ route('empresa.activacion') }}" class="rounded-lg bg-orange-100 px-3.5 py-2 text-[13.5px] font-semibold text-ink">Activación de cuenta</a>
    </x-slot:nav>

    <div class="mx-auto max-w-4xl">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <span class="ad-eyebrow">Activación de empresa</span>
                <h1 class="mt-3 text-[30px] font-extrabold">Completa los datos de tu empresa</h1>
                <p class="mt-2 max-w-2xl text-[14px] leading-relaxed text-gray-500">Tu pago ya fue confirmado. Completa los datos restantes para terminar de activar tu cuenta y acceder a los perfiles.</p>
            </div>
        </div>

        @if ($empresa->estado_activacion === 'inactiva')
            {{-- La bienvenida se lee una vez y después estorba, así que se puede cerrar. El
                 cierre se recuerda en localStorage (mismo criterio que el sidebar plegado:
                 es una preferencia de vista, no vale un viaje al servidor) y va colgado del
                 id de la empresa, para que otra cuenta en el mismo navegador sí la vea.
                 x-cloak evita que asome un instante cuando ya estaba cerrada. --}}
            <div
                x-data="{ visible: localStorage.getItem('ad-bienvenida-activacion-{{ $empresa->id }}') !== '1' }"
                x-show="visible"
                x-cloak
                class="relative mb-6 overflow-hidden rounded-[20px] border border-orange-200 bg-gradient-to-br from-orange-50 to-paper p-6 sm:p-8"
            >
                <button
                    type="button"
                    x-on:click="visible = false; localStorage.setItem('ad-bienvenida-activacion-{{ $empresa->id }}', '1')"
                    class="absolute right-4 top-4 grid size-9 place-items-center rounded-full text-gray-400 transition hover:bg-white/70 hover:text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500"
                    aria-label="Cerrar la bienvenida"
                >
                    <flux:icon.x-mark class="size-5" />
                </button>

                <h2 class="pr-10 text-[22px] font-extrabold">¡Bienvenido! 👋</h2>
                <p class="mt-2 max-w-2xl pr-10 text-[14px] leading-relaxed text-gray-600">Ya completaste el pago. Solo falta ingresar los datos de tu empresa:</p>

                <div class="mt-6 flex flex-col items-stretch gap-4 sm:flex-row">
                    <div class="flex flex-1 items-start gap-4 rounded-[16px] border border-line-2 bg-white p-5 shadow-[var(--shadow-card)] dark:bg-[#222528]">
                        <span class="grid size-11 flex-none place-items-center rounded-full bg-orange-500 text-[18px] font-black text-white">1</span>
                        <div>
                            <div class="flex items-center gap-2"><flux:icon.check class="size-5 flex-none text-match" /><h3 class="text-[15px] font-extrabold text-ink">Plan pagado</h3></div>
                            <p class="mt-1 text-[13px] leading-relaxed text-gray-500">Tu suscripción ya se encuentra activa.</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-center text-orange-300"><flux:icon.arrow-right class="size-7 rotate-90 sm:rotate-0" /></div>

                    <div class="flex flex-1 items-start gap-4 rounded-[16px] border border-line-2 bg-white p-5 shadow-[var(--shadow-card)] dark:bg-[#222528]">
                        <span class="grid size-11 flex-none place-items-center rounded-full bg-orange-500 text-[18px] font-black text-white">2</span>
                        <div>
                            <div class="flex items-center gap-2"><flux:icon.building-office-2 class="size-5 flex-none text-orange-600" /><h3 class="text-[15px] font-extrabold text-ink">Completa los datos de tu empresa</h3></div>
                            <p class="mt-1 text-[13px] leading-relaxed text-gray-500">Razón social, RUT y datos de contacto.</p>
                        </div>
                    </div>
                </div>

                <p class="mt-6 text-[15px] font-extrabold text-orange-600">¡Comencemos! 🚀</p>
            </div>
        @endif

        @if (session('status'))
            <div class="mb-5 rounded-xl border border-[#BFE6CD] bg-match-100 px-4 py-3 text-[13px] font-bold text-match">{{ session('status') }}</div>
        @endif


        <form wire:submit="guardar" class="space-y-5">
            <section class="ad-card">
                <div class="ad-card-head"><div><h2 class="text-[18px] font-extrabold">Datos de la empresa</h2><p class="mt-1 text-[13px] text-gray-500">Información legal y actividad principal.</p></div></div>
                <div class="grid gap-4 p-6 md:grid-cols-2">
                    <flux:input wire:model="razonSocial" label="Razón social *" />
                    <flux:input wire:model.blur.live="rut" label="RUT de la empresa *" placeholder="76.123.456-7" />
                    <div class="md:col-span-2"><flux:input wire:model="rubro" label="Rubro o actividad principal *" placeholder="Ej. Servicios financieros" /></div>
                </div>
            </section>

            <section class="ad-card">
                <div class="ad-card-head"><div><h2 class="text-[18px] font-extrabold">Contacto administrador</h2><p class="mt-1 text-[13px] text-gray-500">Única persona responsable de administrar la cuenta, el plan y los usuarios.</p></div></div>
                <div class="grid gap-4 p-6 md:grid-cols-2">
                    <flux:input wire:model="contactoPrincipalNombre" label="Nombre completo *" />
                    <flux:input wire:model="contactoPrincipalCargo" label="Cargo *" />
                    <flux:input wire:model="contactoPrincipalEmail" type="email" label="Email *" />
                    <x-input-telefono wire:model="contactoPrincipalTelefono" label="Teléfono *" />
                    <div class="md:col-span-2">
                        <flux:textarea wire:model="contactoPrincipalDescripcion" label="Descripción" rows="3" maxlength="1000" placeholder="Cuéntanos brevemente sobre la empresa o el contacto." />
                    </div>
                </div>
            </section>

            <section class="ad-card">
                <div class="ad-card-head"><div><h2 class="text-[18px] font-extrabold">Contactos usuarios <span class="text-[13px] font-semibold text-gray-400">(opcionales)</span></h2><p class="mt-1 text-[13px] text-gray-500">Puedes habilitar ahora hasta tres usuarios adicionales con acceso al panel.</p></div></div>
                <div class="grid gap-5 p-6">
                    @foreach ($usuarios as $index => $usuario)
                        <fieldset wire:key="usuario-{{ $index }}" class="rounded-[16px] border border-line-2 p-5">
                            <legend class="px-2 text-[14px] font-extrabold text-ink">Usuario {{ $index + 1 }}</legend>
                            {{-- Estas fichas son opcionales y describen a terceros, no a quien
                                 llena el formulario: el autocompletado del navegador aquí solo
                                 mete datos que nadie pidió. `new-password` es lo que frena a los
                                 gestores de contraseñas, que ignoran `off` en campos de clave. --}}
                            <div class="grid gap-4 md:grid-cols-2">
                                <flux:input wire:model="usuarios.{{ $index }}.nombre" label="Nombres" autocomplete="off" />
                                <flux:input wire:model="usuarios.{{ $index }}.apellidos" label="Apellidos" autocomplete="off" />
                                <flux:input wire:model="usuarios.{{ $index }}.email" type="email" label="Email corporativo" autocomplete="off" />
                                <flux:input wire:model="usuarios.{{ $index }}.password" type="password" label="Contraseña temporal" autocomplete="new-password" viewable />
                            </div>
                        </fieldset>
                    @endforeach
                    <p class="text-[13px] leading-relaxed text-gray-500">Completa todos los campos de cada usuario que quieras habilitar, o deja esta sección en blanco si no quieres agregar a nadie ahora. Luego podrás agregarlos o eliminarlos desde la sección Equipo.</p>
                </div>
            </section>

            <div class="ad-card flex flex-wrap items-center justify-between gap-4 p-5">
                <p class="max-w-xl text-[13px] leading-relaxed text-gray-500">Al continuar, declaras que los datos son correctos y finalizarás la activación de tu cuenta.</p>
                <button type="submit" class="ad-btn-primary ad-btn-sm" wire:loading.attr="disabled" wire:target="guardar">
                    <span wire:loading.remove wire:target="guardar">Guardar y finalizar</span>
                    <span wire:loading wire:target="guardar">Guardando…</span>
                </button>
            </div>
        </form>
    </div>
</div>
