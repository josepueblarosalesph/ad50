<?php

namespace App\Providers;

use App\Services\LectorDeCv;
use App\Services\LectorDeCvClaude;
use App\Services\LectorDeCvGemini;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // El lector se resuelve por contrato por dos motivos: cambiar de proveedor es
        // cambiar EXTRACTOR_CV_PROVEEDOR en el .env, y los tests del extractor —capas de
        // validación, saneamiento y mapeo a catálogos— corren sin llamar a ninguna API.
        $this->app->bind(LectorDeCv::class, fn (): LectorDeCv => match (config('services.extractor_cv.proveedor')) {
            'claude' => new LectorDeCvClaude,
            default => new LectorDeCvGemini,
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
