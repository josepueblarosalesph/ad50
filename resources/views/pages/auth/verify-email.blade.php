<x-layouts::auth title="Verifica tu correo electrónico">
    <div class="flex flex-col gap-6 text-center">
        <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-orange-100 text-orange-700">
            <flux:icon.envelope class="size-7" />
        </span>

        <div>
            <flux:heading size="xl">Confirma tu correo para activar tu cuenta</flux:heading>
            <flux:text class="mt-3 leading-relaxed">
                Enviamos un enlace de verificación a <strong class="text-ink">{{ auth()->user()->email }}</strong>. Ábrelo para acceder a tu cuenta.
            </flux:text>
        </div>

        @if (session('status') == 'verification-link-sent')
            <div class="rounded-xl border border-[#BFE6CD] bg-match-100 px-4 py-3 text-[13px] font-bold text-match">
                Enviamos un nuevo enlace de verificación a tu correo.
            </div>
        @endif

        <div class="flex flex-col gap-3">
            <form method="POST" action="{{ route('verification.send') }}" class="w-full">
                @csrf
                <button type="submit" class="ad-btn-primary ad-btn-block">Reenviar correo de verificación</button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="ad-btn-ghost ad-btn-sm" data-test="logout-button">Cerrar sesión</button>
            </form>
        </div>
    </div>
</x-layouts::auth>
