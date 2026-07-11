<?php

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Services\MatchingService;
/* @chisel-email-verification */
use Illuminate\Contracts\Auth\MustVerifyEmail;
/* @end-chisel-email-verification */
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Configuración de perfil')] #[Layout('components.layouts.app')] class extends Component {
    use PasswordValidationRules;
    use ProfileValidationRules;

    public string $name = '';

    public string $email = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $visible = false;

    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->visible = (bool) Auth::user()->postulante?->visible;
    }

    /** Actualiza el nombre y el correo de la cuenta. */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        session()->flash('status-datos', 'Actualizamos los datos de tu cuenta.');
    }

    /** Cambia la contraseña de la cuenta. */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update(['password' => $validated['password']]);

        $this->reset('current_password', 'password', 'password_confirmation');

        session()->flash('status-password', 'Actualizamos tu contraseña.');
    }

    /**
     * Cambia la visibilidad del perfil del postulante y resincroniza el matching
     * (pausar el perfil lo quita de las búsquedas de empresas).
     */
    public function updatedVisible(bool $value): void
    {
        $postulante = Auth::user()->postulante;

        if ($postulante === null) {
            $this->visible = false;

            return;
        }

        $postulante->update(['visible' => $value]);
        app(MatchingService::class)->sincronizarPostulante($postulante->fresh());
    }

    #[Computed]
    public function esPostulante(): bool
    {
        return Auth::user()->role === 'postulante' && Auth::user()->postulante !== null;
    }

    /* @chisel-email-verification */
    /** Reenvía el correo de verificación al usuario actual. */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }
    /* @end-chisel-email-verification */
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">Configuración de perfil</flux:heading>

    <x-pages::settings.layout heading="Perfil" subheading="Administra tus datos de cuenta, tu contraseña y la visibilidad de tu perfil.">
        <div class="space-y-5">
            {{-- Datos de la cuenta --}}
            <div class="ad-card">
                <div class="ad-card-head bg-orange-50/60 dark:bg-orange-50"><div><h2 class="text-[16px] font-extrabold text-orange-700 dark:text-orange-500">Datos de la cuenta</h2><p class="mt-1 text-[13px] text-gray-500">Tu nombre y tu correo electrónico.</p></div></div>
                <form wire:submit="updateProfileInformation" class="space-y-5 p-6">
                    @if (session('status-datos'))
                        <div class="rounded-[10px] border border-[#BFE6CD] bg-match-100 px-4 py-3 text-[13px] font-semibold text-match" role="status">{{ session('status-datos') }}</div>
                    @endif

                    <flux:input wire:model="name" label="Nombre" type="text" required autocomplete="name" />

                    <div>
                        <flux:input wire:model="email" label="Correo electrónico" type="email" required autocomplete="email" />

                        {{-- @chisel-email-verification --}}
                        @if ($this->hasUnverifiedEmail)
                            <flux:text class="mt-3">
                                Tu correo electrónico no está verificado.
                                <flux:link class="cursor-pointer text-sm" wire:click.prevent="resendVerificationNotification">Reenviar el correo de verificación.</flux:link>
                            </flux:text>

                            @if (session('status') === 'verification-link-sent')
                                <flux:text class="mt-2 font-medium !text-green-600 dark:!text-green-400">Te enviamos un nuevo enlace de verificación.</flux:text>
                            @endif
                        @endif
                        {{-- @end-chisel-email-verification --}}
                    </div>

                    <div class="flex justify-end">
                        <flux:button variant="primary" type="submit" data-test="update-profile-button">Guardar cambios</flux:button>
                    </div>
                </form>
            </div>

            {{-- Contraseña --}}
            <div class="ad-card">
                <div class="ad-card-head bg-orange-50/60 dark:bg-orange-50"><div><h2 class="text-[16px] font-extrabold text-orange-700 dark:text-orange-500">Contraseña</h2><p class="mt-1 text-[13px] text-gray-500">Usa una contraseña larga y única para mantener tu cuenta segura.</p></div></div>
                <form wire:submit="updatePassword" class="space-y-5 p-6">
                    @if (session('status-password'))
                        <div class="rounded-[10px] border border-[#BFE6CD] bg-match-100 px-4 py-3 text-[13px] font-semibold text-match" role="status">{{ session('status-password') }}</div>
                    @endif

                    <flux:input wire:model="current_password" label="Contraseña actual" type="password" autocomplete="current-password" viewable />
                    <flux:input wire:model="password" label="Nueva contraseña" type="password" autocomplete="new-password" viewable />
                    <flux:input wire:model="password_confirmation" label="Confirmar nueva contraseña" type="password" autocomplete="new-password" viewable />

                    <div class="flex justify-end">
                        <flux:button variant="primary" type="submit" data-test="update-password-button">Actualizar contraseña</flux:button>
                    </div>
                </form>
            </div>

            {{-- Visibilidad del perfil (solo postulantes) --}}
            @if ($this->esPostulante)
                <div class="ad-card">
                    <div class="ad-card-head bg-orange-50/60 dark:bg-orange-50"><div><h2 class="text-[16px] font-extrabold text-orange-700 dark:text-orange-500">Visibilidad del perfil</h2><p class="mt-1 text-[13px] text-gray-500">Controla si las empresas pueden encontrarte en sus búsquedas.</p></div></div>
                    <div class="p-6">
                        <div class="ad-toggle-row">
                            <div>
                                <b class="block text-[13.5px]">{{ $visible ? 'Perfil activo' : 'Perfil pausado' }}</b>
                                <span class="text-[13px] text-gray-500">{{ $visible ? 'Apareces en las búsquedas compatibles de las empresas.' : 'Tu perfil no aparece en ninguna búsqueda.' }}</span>
                            </div>
                            <flux:switch wire:model.live="visible" />
                        </div>
                    </div>
                </div>
            @endif

            {{-- @chisel-email-verification --}}
            @if ($this->showDeleteUser)
            {{-- @end-chisel-email-verification --}}
                <livewire:pages::settings.delete-user-form />
            {{-- @chisel-email-verification --}}
            @endif
            {{-- @end-chisel-email-verification --}}
        </div>
    </x-pages::settings.layout>
</section>
