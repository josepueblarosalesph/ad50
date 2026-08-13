<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Http\Responses\LoginResponse;
use App\Http\Responses\VerifyEmailResponse;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(TwoFactorLoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(VerifyEmailResponseContract::class, VerifyEmailResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureNotifications();
        $this->configureRateLimiting();
    }

    /**
     * Configure the notifications sent by the authentication flow.
     */
    private function configureNotifications(): void
    {
        // Los correos de autenticación son los únicos que Laravel arma por su cuenta, y sus
        // textos por defecto están en inglés (no hay traducción para ellos en lang/es.json).
        // Se redactan aquí para que lleguen en español y con el tono del sitio.

        VerifyEmail::toMailUsing(function (object $notifiable, string $url): MailMessage {
            // Laravel usa 60 minutos si no se configura auth.verification.expire.
            $minutos = (int) config('auth.verification.expire', 60);

            return (new MailMessage)
                ->subject('Confirma tu correo para activar tu cuenta · AD+50')
                ->greeting('¡Te damos la bienvenida a AD+50!')
                ->line('Para activar tu cuenta y empezar a usar la plataforma, confirma que este correo es tuyo.')
                ->action('Confirmar mi correo', $url)
                ->line("El enlace vence en {$minutos} minutos. Si expira, puedes pedir uno nuevo desde la misma pantalla.")
                ->line('Si no creaste una cuenta en AD+50, ignora este mensaje: no se hará nada con tu correo.')
                ->salutation('Un saludo,'."\n".'Equipo AD+50');
        });

        ResetPassword::toMailUsing(function (CanResetPassword $notifiable, string $token): MailMessage {
            $minutos = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

            $url = route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);

            return (new MailMessage)
                ->subject('Recupera tu contraseña · AD+50')
                ->greeting('Hola,')
                ->line('Recibiste este correo porque alguien pidió recuperar la contraseña de tu cuenta en AD+50.')
                ->action('Crear una contraseña nueva', $url)
                ->line("El enlace vence en {$minutos} minutos.")
                ->line('Si no pediste el cambio, ignora este mensaje: tu contraseña actual sigue funcionando.')
                ->salutation('Un saludo,'."\n".'Equipo AD+50');
        });
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn () => view('pages::auth.login'));
        Fortify::verifyEmailView(fn () => view('pages::auth.verify-email'));
        Fortify::twoFactorChallengeView(fn () => view('pages::auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('pages::auth.confirm-password'));
        Fortify::registerView(fn () => view('pages::auth.register'));
        Fortify::resetPasswordView(fn () => view('pages::auth.reset-password'));
        Fortify::requestPasswordResetLinkView(fn () => view('pages::auth.forgot-password'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}
