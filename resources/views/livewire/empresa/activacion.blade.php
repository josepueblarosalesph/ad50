<div class="ad-panel">
    <x-slot:context>Empresa</x-slot:context>
    <x-slot:status>{{ $empresa->estado_activacion === 'pendiente' ? 'Pendiente de revisión' : 'Cuenta inactiva' }}</x-slot:status>
    <x-slot:nav>
        <a href="{{ route('empresa.activacion') }}" class="rounded-lg bg-orange-100 px-3.5 py-2 text-[13.5px] font-semibold text-ink">Activación de cuenta</a>
    </x-slot:nav>

    <div class="mx-auto max-w-4xl">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <span class="ad-eyebrow">Validación de empresa</span>
                <h1 class="mt-3 text-[30px] font-extrabold">Completa los antecedentes de tu empresa</h1>
                <p class="mt-2 max-w-2xl text-[14px] leading-relaxed text-gray-500">Revisaremos esta información manualmente antes de habilitar procesos y acceso a perfiles profesionales.</p>
            </div>
            @unless ($empresa->estado_activacion === 'inactiva')
                <span @class(['ad-chip', 'ad-chip-orange' => $empresa->estado_activacion !== 'activa', 'ad-chip-green' => $empresa->estado_activacion === 'activa'])>
                    {{ $empresa->estado_activacion === 'pendiente' ? 'Revisión pendiente' : ucfirst($empresa->estado_activacion) }}
                </span>
            @endunless
        </div>

        @if (session('status'))
            <div class="mb-5 rounded-xl border border-[#BFE6CD] bg-match-100 px-4 py-3 text-[13px] font-bold text-match">{{ session('status') }}</div>
        @endif

        @if ($empresa->estado_activacion === 'pendiente')
            <div class="mb-5 flex gap-3 rounded-xl border border-orange-200 bg-orange-50 p-4 text-[13px] leading-relaxed text-gray-700">
                <flux:icon.clock class="mt-0.5 size-5 shrink-0 text-orange-500" />
                <p><b class="text-ink">Antecedentes recibidos.</b> Puedes corregirlos mientras esperas. La activación será realizada por un administrador después de verificarlos.</p>
            </div>
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
                <div class="ad-card-head"><div><h2 class="text-[18px] font-extrabold">Contacto principal</h2><p class="mt-1 text-[13px] text-gray-500">Persona responsable de la cuenta y los procesos de selección.</p></div></div>
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
                <div class="ad-card-head"><div><h2 class="text-[18px] font-extrabold">Contacto técnico <span class="text-[13px] font-semibold text-gray-400">(opcional)</span></h2><p class="mt-1 text-[13px] text-gray-500">Persona a quien contactar ante temas de acceso, seguridad o integración.</p></div></div>
                <div class="grid gap-4 p-6 md:grid-cols-2">
                    <flux:input wire:model="contactoTecnicoNombre" label="Nombre completo" />
                    <flux:input wire:model="contactoTecnicoCargo" label="Cargo" />
                    <flux:input wire:model="contactoTecnicoEmail" type="email" label="Email" />
                    <x-input-telefono wire:model="contactoTecnicoTelefono" label="Teléfono" />
                </div>
            </section>

            <div class="ad-card flex flex-wrap items-center justify-between gap-4 p-5">
                <p class="max-w-xl text-[13px] leading-relaxed text-gray-500">Al enviar, declaras que los antecedentes son correctos y autorizas su revisión para habilitar la cuenta.</p>
                <button type="submit" class="ad-btn-primary ad-btn-sm" wire:loading.attr="disabled" wire:target="guardar">
                    <span wire:loading.remove wire:target="guardar">{{ $empresa->estado_activacion === 'pendiente' ? 'Actualizar antecedentes' : 'Enviar a revisión' }}</span>
                    <span wire:loading wire:target="guardar">Guardando…</span>
                </button>
            </div>
        </form>
    </div>
</div>
