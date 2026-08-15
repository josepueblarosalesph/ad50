<x-layouts::auth title="Crear contraseña nueva">
    <div class="flex flex-col gap-6">
        <x-auth-header title="Crear contraseña nueva" description="Escribe abajo la contraseña que usarás de ahora en adelante" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Token -->
            <input type="hidden" name="token" value="{{ request()->route('token') }}">

            <!-- Email Address -->
            <flux:input
                name="email"
                value="{{ request('email') }}"
                label="Correo electrónico"
                type="email"
                required
                autocomplete="email"
            />

            <!-- Password -->
            <flux:input
                name="password"
                label="Contraseña nueva"
                type="password"
                required
                autocomplete="new-password"
                placeholder="Ingresa tu contraseña nueva"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                label="Confirmar contraseña"
                type="password"
                required
                autocomplete="new-password"
                placeholder="Repite tu contraseña nueva"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="reset-password-button">
                    Guardar contraseña nueva
                </flux:button>
            </div>
        </form>
    </div>
</x-layouts::auth>
