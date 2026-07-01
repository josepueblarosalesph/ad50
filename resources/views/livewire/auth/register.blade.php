<div class="grid md:grid-cols-[1fr_1.05fr] min-h-screen">

{{-- ====== Panel marca ====== --}}
<aside class="relative bg-ink text-white flex items-center overflow-hidden">
    <div class="absolute inset-0 opacity-40"
         style="background: linear-gradient(135deg, rgba(117,120,123,.7), rgba(52,54,56,.96));"></div>
    <div class="relative px-12 py-14">
        <div class="inline-flex items-center bg-ink rounded-[11px] px-3 py-2 mb-10">
            <span class="text-white font-extrabold text-xl tracking-wide">AD+50</span>
        </div>
        <span class="ad-eyebrow">Bienvenido</span>
        <h1 class="text-[38px] font-extrabold tracking-[-0.02em] mt-3 max-w-[420px]">
            Tu experiencia, visible para quien la busca.
        </h1>
        <ul class="grid gap-4 mt-8 max-w-[380px] list-none">
            @foreach ([
                'Una ficha única que cargas una vez y mantienes actualizada.',
                'Tú controlas la visibilidad y el contacto.',
                'Datos protegidos bajo AD Consulting. Nunca se comercializan.',
            ] as $l)
                <li class="flex gap-3 text-[14.5px] text-white/85 font-medium">
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
<section class="bg-white p-12 flex flex-col justify-center">
    <form wire:submit="submit" class="w-full max-w-[420px] mx-auto">

        {{-- Tabs Postulante / Empresa --}}
        <div class="flex gap-2 bg-paper p-1.5 rounded-full mb-7 border border-line">
            <button type="button" wire:click="setRole('postulante')"
                @class([
                    'flex-1 py-2 px-4 rounded-full text-[13px] font-semibold transition',
                    'bg-white text-ink shadow-[var(--shadow-card)]' => $role === 'postulante',
                    'text-gray-500' => $role !== 'postulante',
                ])>Soy postulante</button>
            <button type="button" wire:click="setRole('empresa')"
                @class([
                    'flex-1 py-2 px-4 rounded-full text-[13px] font-semibold transition',
                    'bg-white text-ink shadow-[var(--shadow-card)]' => $role === 'empresa',
                    'text-gray-500' => $role !== 'empresa',
                ])>Soy empresa</button>
        </div>

        <h2 class="text-[25px] font-extrabold">Crea tu cuenta</h2>
        <p class="text-[14px] text-gray-500 mt-2 mb-6">
            Toma menos de 2 minutos. Luego completas tu {{ $role === 'postulante' ? 'ficha profesional' : 'perfil de empresa' }}.
        </p>

        @if ($role === 'empresa')
            <div class="grid gap-3.5 mb-3.5">
                <flux:input wire:model="razon_social" label="Razón social *" placeholder="Forestal del Bío Bío S.A." />
            </div>
        @endif

        <div class="grid grid-cols-2 gap-3.5">
            <flux:input wire:model="nombre"    label="Nombre *"    placeholder="María José" />
            <flux:input wire:model="apellidos" label="Apellidos *" placeholder="Fuentes Rojas" />
        </div>
        <div class="mt-3.5">
            <flux:input wire:model="email" type="email" label="Email *" placeholder="tu@correo.cl" />
        </div>
        <div class="mt-3.5">
            <flux:input wire:model="password" type="password" label="Contraseña *" placeholder="••••••••••" viewable />
        </div>

        <label class="flex gap-3 items-start mt-5 mb-5 p-4 bg-orange-50 border border-orange-200 rounded-[10px] cursor-pointer">
            <flux:switch wire:model.live="acepta" class="mt-0.5" />
            <p class="text-[12px] text-gray-700 leading-[1.5]">
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
