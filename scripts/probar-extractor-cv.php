<?php

/**
 * Prueba el extractor de CV contra la API real del proveedor configurado.
 *
 * Hace UNA sola petición, así que sirve incluso con la cuota del tier gratuito casi
 * agotada. Genera un CV de prueba en PDF, lo pasa por todas las capas (validación del
 * archivo, lectura, saneamiento, mapeo a catálogos) e imprime lo que quedaría en la
 * ficha. No toca la base de datos ni guarda nada.
 *
 *   php scripts/probar-extractor-cv.php               # usa un CV de prueba generado
 *   php scripts/probar-extractor-cv.php ~/mi-cv.pdf   # usa tu propio PDF
 */

use App\Services\ExtraccionCvException;
use App\Services\ExtractorCv;
use App\Services\LectorDeCv;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

/** CV mínimo pero con estructura de PDF válida (xref incluido). */
function pdfDePrueba(): string
{
    $lineas = [
        'Marcela Rivas Soto', 'Jefa de Operaciones Logisticas', '',
        'EXPERIENCIA', 'Jefa de Logistica - Sodimac', 'Marzo 2015 - Actualidad', '',
        'EDUCACION', 'Ingenieria Civil Industrial', 'Universidad de Valparaiso, 1990 - 1995',
    ];
    $texto = "BT /F1 12 Tf 50 750 Td 16 TL\n";
    foreach ($lineas as $l) {
        $texto .= "($l) Tj T*\n";
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
    $offsets = [];
    foreach ($objetos as $i => $obj) {
        $offsets[] = strlen($pdf);
        $pdf .= ($i + 1)." 0 obj\n".$obj."\nendobj\n";
    }

    $inicioXref = strlen($pdf);
    $pdf .= 'xref'."\n".'0 '.(count($objetos) + 1)."\n".'0000000000 65535 f '."\n";
    foreach ($offsets as $off) {
        $pdf .= sprintf('%010d 00000 n ', $off)."\n";
    }

    return $pdf.'trailer'."\n".'<< /Size '.(count($objetos) + 1).' /Root 1 0 R >>'."\n".'startxref'."\n".$inicioXref."\n".'%%EOF'."\n";
}

$lector = app(LectorDeCv::class);

echo "proveedor : {$lector->nombre()}\n";
echo "modelo    : {$lector->modelo()}\n";

if (! $lector->disponible()) {
    echo "\nNo hay api_key configurada para este proveedor. Revisa EXTRACTOR_CV_PROVEEDOR en el .env.\n";
    exit(1);
}

$ruta = $argv[1] ?? null;
$pdf = $ruta !== null ? file_get_contents($ruta) : pdfDePrueba();
echo 'documento : '.($ruta ?? 'CV de prueba generado').' ('.strlen($pdf)." bytes)\n\n";

try {
    $resultado = app(ExtractorCv::class)->extraer($pdf, 0);
} catch (ExtraccionCvException $e) {
    echo 'FALLÓ: '.$e->getMessage()."\n";
    echo "El detalle técnico quedó en storage/logs/laravel.log.\n";
    exit(1);
}

echo "OK. Esto es lo que quedaría propuesto en la ficha:\n\n";
foreach (['nombres', 'apellidos', 'titular', 'ciudad', 'genero', 'nacionalidad', 'anioNacimiento', 'aniosExperiencia'] as $campo) {
    printf("  %-18s %s\n", $campo, var_export($resultado->datos[$campo] ?? null, true));
}

foreach ($resultado->datos['experiencias'] as $i => $e) {
    echo "  experiencia $i     {$e['cargo']}".($e['cargo_otro'] !== '' ? " ({$e['cargo_otro']})" : '')
        ." · {$e['empresa']}".($e['empresa_otro'] !== '' ? " ({$e['empresa_otro']})" : '')
        ." · {$e['inicio_anio']}\n";
}

foreach ($resultado->datos['educaciones'] as $i => $e) {
    echo "  educacion $i       {$e['institucion']} · {$e['carrera']} · {$e['pais']} · {$e['nivel']}\n";
}

printf("\n  %-18s %s\n", 'confianza', $resultado->confianza);
printf("  %-18s %s\n", 'flags', $resultado->flags === [] ? '(ninguno)' : implode(', ', $resultado->flags));
printf("  %-18s %s\n", 'requiere revisión', $resultado->requiereRevision() ? 'sí' : 'no');

foreach ($resultado->notas as $nota) {
    echo "  nota               $nota\n";
}
