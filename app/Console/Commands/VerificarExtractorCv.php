<?php

namespace App\Console\Commands;

use App\Jobs\LeerCvDelPostulante;
use App\Services\LectorDeCv;
use App\Support\EstadoLecturaCv;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Revisa que el autollenado de la ficha desde el CV esté operativo.
 *
 * Existe porque las piezas de este flujo fallan en silencio: sin credencial el bloque
 * simplemente no aparece, sin worker el CV se encola y nadie lo procesa, y sin las
 * migraciones al día se guarda mal. Nada de eso da un error visible, así que conviene
 * poder preguntarlo de una vez en el servidor.
 *
 *   php artisan cv:verificar           revisiones locales, no gasta cuota
 *   php artisan cv:verificar --leer    además encola una lectura real y la espera
 */
class VerificarExtractorCv extends Command
{
    protected $signature = 'cv:verificar {--leer : Encola una lectura real y espera el resultado}';

    protected $description = 'Comprueba que el autollenado de la ficha desde el CV esté configurado y operativo';

    /** Identificador reservado para la prueba, que no choca con ningún postulante. */
    private const POSTULANTE_DE_PRUEBA = 0;

    private const ESPERA_MAXIMA_SEGUNDOS = 180;

    private bool $hayProblemas = false;

    public function handle(): int
    {
        $lector = app(LectorDeCv::class);

        $this->newLine();
        $this->line('  <options=bold>Autollenado de la ficha desde el CV</>');
        $this->newLine();

        $this->revisarCredencial($lector);
        $this->revisarMigraciones();
        $this->revisarCola();
        $this->revisarDisco();

        if ($this->option('leer')) {
            $this->probarLecturaCompleta();
        } else {
            $this->newLine();
            $this->line('  Para probar la cadena completa contra el proveedor: <options=bold>php artisan cv:verificar --leer</>');
        }

        $this->newLine();

        return $this->hayProblemas ? self::FAILURE : self::SUCCESS;
    }

    private function revisarCredencial(LectorDeCv $lector): void
    {
        $proveedor = $lector->nombre();

        if (! $lector->disponible()) {
            $this->mal(
                "Sin credencial para «{$proveedor}»",
                'El bloque de autollenado no se le muestra a nadie. Define la api_key de ese proveedor, o cambia EXTRACTOR_CV_PROVEEDOR.',
            );

            return;
        }

        $this->bien("Credencial de «{$proveedor}» presente · modelo {$lector->modelo()}");
    }

    private function revisarMigraciones(): void
    {
        $migrator = app('migrator');
        $corridas = $migrator->getRepository()->getRan();

        $pendientes = collect($migrator->getMigrationFiles(database_path('migrations')))
            ->map(fn (string $archivo): string => $migrator->getMigrationName($archivo))
            ->reject(fn (string $nombre): bool => in_array($nombre, $corridas, true))
            ->values();

        if ($pendientes->isNotEmpty()) {
            $this->mal(
                "Faltan {$pendientes->count()} migraciones por correr",
                'Ejecuta php artisan migrate --force. Pendientes: '.$pendientes->implode(', '),
            );

            return;
        }

        $this->bien('Migraciones al día');
    }

    private function revisarCola(): void
    {
        $conexion = (string) config('queue.default');

        if ($conexion === 'sync') {
            $this->mal(
                'La cola está en «sync»',
                'La lectura correría dentro de la petición web y el servidor la cortaría por timeout. Usa la cola de base de datos o Redis.',
            );

            return;
        }

        $this->bien("Cola configurada en «{$conexion}»");

        // Un worker no se puede detectar desde aquí con certeza; lo que sí delata su
        // ausencia es que se acumulen trabajos sin procesar.
        try {
            $pendientes = DB::table('jobs')->count();
            $fallidos = DB::table('failed_jobs')->count();
        } catch (\Throwable) {
            return;
        }

        if ($pendientes > 0) {
            $this->ojo("Hay $pendientes trabajos esperando en la cola: puede que no haya ningún worker corriendo.");
        }

        if ($fallidos > 0) {
            $this->ojo("Hay $fallidos trabajos fallidos. Revísalos con php artisan queue:failed.");
        }
    }

    private function revisarDisco(): void
    {
        $prueba = 'cvs/.verificacion-'.bin2hex(random_bytes(4));

        try {
            Storage::disk('local')->put($prueba, 'x');
            $existe = Storage::disk('local')->exists($prueba);
            Storage::disk('local')->delete($prueba);
        } catch (\Throwable $e) {
            $this->mal('No se puede escribir el CV en disco', $e->getMessage());

            return;
        }

        $existe
            ? $this->bien('Disco de CV escribible ('.Storage::disk('local')->path('cvs').')')
            : $this->mal('El disco aceptó la escritura pero el archivo no quedó', 'Revisa permisos de storage/app/private.');
    }

    /**
     * Prueba de punta a punta: encola una lectura real y espera el resultado.
     *
     * Es lo único que demuestra que hay un worker vivo y que el proveedor responde,
     * porque recorre exactamente el mismo camino que un CV de verdad.
     */
    private function probarLecturaCompleta(): void
    {
        $this->newLine();
        $this->line('  <options=bold>Prueba de punta a punta</>');
        $this->newLine();

        $ruta = 'cvs/.verificacion-'.bin2hex(random_bytes(4)).'.pdf';
        Storage::disk('local')->put($ruta, $this->pdfDePrueba());

        EstadoLecturaCv::olvidar(self::POSTULANTE_DE_PRUEBA);
        LeerCvDelPostulante::dispatch(self::POSTULANTE_DE_PRUEBA, $ruta);

        $this->line('  CV de prueba encolado. Esperando a que un worker lo procese…');

        $inicio = time();
        $barra = $this->output->createProgressBar(self::ESPERA_MAXIMA_SEGUNDOS);
        $barra->start();

        do {
            sleep(2);
            $barra->setProgress(min(time() - $inicio, self::ESPERA_MAXIMA_SEGUNDOS));
            $estado = EstadoLecturaCv::leer(self::POSTULANTE_DE_PRUEBA);
            $terminado = $estado !== null && ($estado['estado'] ?? null) !== 'en_curso';
        } while (! $terminado && (time() - $inicio) < self::ESPERA_MAXIMA_SEGUNDOS);

        $barra->finish();
        $this->newLine(2);

        Storage::disk('local')->delete($ruta);
        EstadoLecturaCv::olvidar(self::POSTULANTE_DE_PRUEBA);

        $this->informarLectura($estado ?? null, time() - $inicio);
    }

    /**
     * @param  array<string, mixed>|null  $estado
     */
    private function informarLectura(?array $estado, int $segundos): void
    {
        if ($estado === null || ($estado['estado'] ?? null) === 'en_curso') {
            $this->mal(
                'Nadie procesó el trabajo',
                'Casi seguro no hay un worker corriendo. Levanta php artisan queue:work (con supervisor o systemd para que sobreviva).',
            );

            return;
        }

        if (($estado['estado'] ?? null) === 'error') {
            $this->mal('La lectura falló: '.($estado['mensaje'] ?? ''), 'El detalle técnico quedó en storage/logs/laravel.log.');

            return;
        }

        $datos = $estado['datos'] ?? [];

        $this->bien("Lectura completa en {$segundos}s · confianza: ".($estado['confianza'] ?? '?'));
        $this->line('     titular leído: <options=bold>'.($datos['titular'] ?? '(ninguno)').'</>');
        $this->line('     experiencias : '.count($datos['experiencias'] ?? []).' · educaciones: '.count($datos['educaciones'] ?? []));
        $this->newLine();
        $this->line('  <fg=green;options=bold>El autollenado está operativo en este servidor.</>');
    }

    /** CV mínimo con estructura de PDF válida, para no depender de un archivo externo. */
    private function pdfDePrueba(): string
    {
        $lineas = [
            'Marcela Rivas Soto', 'Jefa de Operaciones Logisticas', '',
            'EXPERIENCIA', 'Jefa de Logistica - Sodimac', 'Marzo 2015 - Actualidad', '',
            'EDUCACION', 'Ingenieria Civil Industrial', 'Universidad de Valparaiso, 1990 - 1995',
        ];

        $texto = "BT /F1 12 Tf 50 750 Td 16 TL\n";
        foreach ($lineas as $linea) {
            $texto .= "($linea) Tj T*\n";
        }
        $texto .= 'ET';

        $objetos = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',
            '<< /Length '.strlen($texto)." >>\nstream\n".$texto."\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.7\n";
        $posiciones = [];

        foreach ($objetos as $i => $objeto) {
            $posiciones[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n".$objeto."\nendobj\n";
        }

        $inicioXref = strlen($pdf);
        $pdf .= 'xref'."\n".'0 '.(count($objetos) + 1)."\n".'0000000000 65535 f '."\n";

        foreach ($posiciones as $posicion) {
            $pdf .= sprintf('%010d 00000 n ', $posicion)."\n";
        }

        return $pdf.'trailer'."\n".'<< /Size '.(count($objetos) + 1).' /Root 1 0 R >>'."\n".'startxref'."\n".$inicioXref."\n".'%%EOF'."\n";
    }

    private function bien(string $mensaje): void
    {
        $this->line("  <fg=green>✓</> $mensaje");
    }

    private function ojo(string $mensaje): void
    {
        $this->line("  <fg=yellow>!</> $mensaje");
    }

    private function mal(string $mensaje, string $comoArreglarlo): void
    {
        $this->hayProblemas = true;
        $this->line("  <fg=red>✗</> <options=bold>$mensaje</>");
        $this->line("     $comoArreglarlo");
    }
}
