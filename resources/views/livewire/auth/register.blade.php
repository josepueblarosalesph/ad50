<div class="grid min-h-screen bg-paper lg:grid-cols-[1fr_1.05fr]">

{{-- ====== Panel marca ====== --}}
<aside class="relative hidden items-start overflow-hidden bg-ink text-white lg:flex">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_15%,rgba(232,119,34,.2),transparent_38%),linear-gradient(135deg,#4C4F51,#252729_65%)]"></div>
    <div class="relative px-10 py-14 xl:px-14">
        <a href="{{ route('home') }}" class="mb-10 inline-flex rounded-[14px] bg-black/15 px-3 py-2 ring-1 ring-white/10" aria-label="AD+50 Talento Senior">
            <img src="/images/ad50-logo.png" alt="AD+50 Talento Senior" class="h-[72px] w-auto">
        </a>
        <span class="ad-eyebrow">Bienvenido</span>
        <h1 class="mt-4 max-w-[460px] text-[44px] leading-[1.02] text-white xl:text-[52px]">
            Tu experiencia, visible para quien la busca.
        </h1>
        <ul class="grid gap-4 mt-8 max-w-[380px] list-none">
            @foreach ([
                'Un perfil profesional único que completas una vez y mantienes actualizado.',
                'Tú controlas la visibilidad y el contacto.',
                'Datos protegidos bajo AD Consulting. Nunca se comercializan.',
            ] as $l)
                <li class="flex gap-3 text-[15px] font-semibold leading-[1.55] text-white/85">
                    <flux:icon.check class="size-5 text-orange-500 flex-none mt-0.5" />
                    <span>{{ $l }}</span>
                </li>
            @endforeach
        </ul>
        <div class="flex gap-3 mt-10">
            @foreach (['Sence','Icontec','Ley 21.719'] as $c)
                <span class="text-[10px] font-bold tracking-wider text-white/60
                             border border-white/15 rounded-md px-2.5 py-1.5 uppercase">{{ $c }}</span>
            @endforeach
        </div>
    </div>
</aside>

{{-- ====== Panel formulario ====== --}}
<section class="relative flex flex-col justify-center bg-white px-5 py-9 dark:bg-[#16181A] sm:px-8 lg:px-12 xl:px-20">
    <a href="{{ route('home') }}" class="ad-auth-back absolute right-5 top-5 sm:right-8 sm:top-7 lg:right-10" wire:navigate>
        <flux:icon.arrow-left class="size-4" />
        Volver al inicio
    </a>
    <a href="{{ route('home') }}" class="mb-8 inline-flex w-fit rounded-[12px] bg-ink px-3 py-2 lg:hidden" aria-label="AD+50 Talento Senior">
        <img src="/images/ad50-logo.png" alt="AD+50 Talento Senior" class="h-14 w-auto">
    </a>
    <form wire:submit="submit" class="mx-auto w-full max-w-[470px] rounded-[24px] border border-line-2 bg-white p-6 shadow-[var(--shadow-card-lg)] dark:bg-[#222528] sm:p-9">

        {{-- Tabs Postulante / Empresa --}}
        <div class="flex gap-2 bg-paper p-1.5 rounded-full mb-7 border border-line">
            <button type="button" wire:click="setRole('postulante')"
                @class([
                    'flex-1 py-2 px-4 rounded-full text-[13px] font-semibold transition',
                    'bg-white text-ink shadow-[var(--shadow-card)] dark:bg-[#2B2F32]' => $role === 'postulante',
                    'text-gray-500' => $role !== 'postulante',
                ])>Soy postulante</button>
            <button type="button" wire:click="setRole('empresa')"
                @class([
                    'flex-1 py-2 px-4 rounded-full text-[13px] font-semibold transition',
                    'bg-white text-ink shadow-[var(--shadow-card)] dark:bg-[#2B2F32]' => $role === 'empresa',
                    'text-gray-500' => $role !== 'empresa',
                ])>Soy empresa</button>
        </div>

        <h2 class="mt-3 text-[34px] sm:text-[40px]">Crea tu cuenta</h2>
        <p class="text-[14px] text-gray-500 mt-2 mb-6">
            Toma menos de 2 minutos. Luego completas tu {{ $role === 'postulante' ? 'perfil profesional' : 'perfil de empresa' }}.
        </p>

        @if ($role === 'empresa')
            <div class="grid gap-3.5">
                <flux:input wire:model="razon_social" label="Razón social *" placeholder="Forestal del Bío Bío S.A." />
                <flux:input wire:model.blur="rut" label="RUT de la empresa *" placeholder="76.123.456-0" />
            </div>

            <div class="mb-4 mt-6 flex items-center gap-3">
                <span class="flex-none text-[11px] font-extrabold uppercase tracking-[.14em] text-gray-400">Datos de contacto</span>
                <span class="h-px flex-1 bg-line"></span>
            </div>
        @endif

        <div class="grid gap-3.5 sm:grid-cols-2">
            <flux:input wire:model="nombre"    label="Nombre *"    placeholder="María José" />
            <flux:input wire:model="apellidos" label="Apellidos *" placeholder="Fuentes Rojas" />
        </div>
        @if ($role === 'empresa')
            <div class="mt-3.5">
                <x-input-telefono wire:model="telefono" label="Teléfono de contacto *" />
            </div>
        @endif
        <div class="mt-3.5">
            <flux:input wire:model="email" type="email" label="Email *" placeholder="tu@correo.cl" />
        </div>
        <div class="mt-3.5">
            <flux:input wire:model="password" type="password" label="Contraseña *" placeholder="••••••••••" viewable />
        </div>

        <label class="mt-5 mb-5 flex cursor-pointer items-start gap-3 rounded-[10px] border border-orange-200 bg-orange-50 p-4">
            <flux:switch wire:model.live="acepta" class="mt-0.5" />
            <p class="text-[13px] leading-[1.55] text-gray-700">
                Autorizo el tratamiento de mis datos personales por AD Consulting con la finalidad de
                participar en procesos de selección, conforme a la
                <b class="text-ink">Ley N° 21.719</b>. Puedo actualizar, pausar o eliminar mi información cuando quiera.
            </p>
        </label>

        <button type="submit" class="ad-btn-primary ad-btn-block"
                wire:loading.attr="disabled" wire:target="submit">
            <span wire:loading.remove wire:target="submit">Crear cuenta y continuar</span>
            <span wire:loading wire:target="submit">Creando…</span>
        </button>

        <p class="text-center text-[13px] text-gray-500 mt-5">
            ¿Ya tienes cuenta?
            <a href="{{ route('login') }}" class="text-orange-600 font-bold">Ingresa aquí</a>
        </p>
    </form>
</section>

</div>
