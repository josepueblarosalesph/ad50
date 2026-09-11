<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Datos del informe de usuarios registrados, agrupados por tipo de cuenta.
 *
 * Vive fuera del componente Livewire a propósito. Primero porque el informe es del
 * padrón completo y **no acepta filtros**: no recibe argumentos, así que no hay forma
 * de que termine reflejando lo que alguien tenía filtrado en pantalla y dos descargas
 * del mismo día den documentos distintos. Y segundo porque así se puede comprobar el
 * contenido en una prueba: dentro del PDF el texto va comprimido y codificado con la
 * fuente embebida, de modo que sobre el binario no se puede afirmar nada útil.
 *
 * @phpstan-type FilaResumen array{etiqueta: string, total: int, verificadas: int, sin_verificar: int}
 */
class InformeDeUsuarios
{
    /**
     * Tipos de cuenta que puede cubrir el informe, en el orden en que se listan.
     *
     * Quedan fuera `admin` y `superadmin`: son cuentas internas del equipo, no gente
     * registrada en la plataforma, y mezclarlas desvirtúa los totales y los porcentajes
     * del resumen. Si mañana hiciera falta un informe del equipo interno, es otro
     * documento con otro propósito, no una fila más en este.
     *
     * @var list<string>
     */
    public const ROLES = ['postulante', 'empresa'];

    /**
     * Los tres informes que se pueden pedir, con el título y el nombre de archivo de
     * cada uno. Viven juntos aquí para que el documento, su nombre y lo que contiene
     * no puedan contradecirse: un PDF titulado «postulantes» con empresas dentro sería
     * peor que no tener el informe.
     *
     * @var array<string, array{roles: list<string>, titulo: string, archivo: string}>
     */
    public const ALCANCES = [
        'todos' => [
            'roles' => self::ROLES,
            'titulo' => 'Informe de usuarios registrados',
            'archivo' => 'usuarios-registrados',
        ],
        'postulante' => [
            'roles' => ['postulante'],
            'titulo' => 'Informe de postulantes registrados',
            'archivo' => 'postulantes',
        ],
        'empresa' => [
            'roles' => ['empresa'],
            'titulo' => 'Informe de empresas registradas',
            'archivo' => 'empresas',
        ],
    ];

    /**
     * Recuento por tipo y el detalle de cada cuenta con su fecha de registro.
     *
     * Incluye los tipos que no tienen ninguna cuenta, con total 0: «ninguna empresa
     * registrada» es un dato, y omitir la fila obligaría a quien lo lee a recordar
     * cuántos tipos existen para notar que falta uno.
     *
     * @param  string  $alcance  Una clave de ALCANCES: todo el padrón o un solo tipo.
     * @return array{
     *     usuariosPorTipo: array<string, Collection<int, User>>,
     *     resumen: array<string, FilaResumen>,
     *     total: int,
     *     totalVerificadas: int,
     *     totalSinVerificar: int,
     *     titulo: string,
     * }
     */
    public static function armar(string $alcance = 'todos'): array
    {
        // Un alcance desconocido no se degrada a «todos» en silencio: eso convertiría
        // un error de programación en un informe con datos que nadie pidió.
        abort_unless(array_key_exists($alcance, self::ALCANCES), 404);

        $usuariosPorTipo = [];
        $resumen = [];

        foreach (self::ALCANCES[$alcance]['roles'] as $rol) {
            $usuarios = User::query()
                ->where('role', $rol)
                // Mismo orden que la tabla en pantalla: lo más reciente arriba. El id
                // desempata para que dos registros del mismo instante no se barajen.
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get(['id', 'name', 'email', 'email_verified_at', 'created_at']);

            $verificadas = $usuarios->whereNotNull('email_verified_at')->count();

            $usuariosPorTipo[$rol] = $usuarios;

            $resumen[$rol] = [
                'etiqueta' => User::ROLES[$rol],
                'total' => $usuarios->count(),
                'verificadas' => $verificadas,
                'sin_verificar' => $usuarios->count() - $verificadas,
            ];
        }

        return [
            'usuariosPorTipo' => $usuariosPorTipo,
            'resumen' => $resumen,
            'total' => array_sum(array_column($resumen, 'total')),
            'totalVerificadas' => array_sum(array_column($resumen, 'verificadas')),
            'totalSinVerificar' => array_sum(array_column($resumen, 'sin_verificar')),
            'titulo' => self::ALCANCES[$alcance]['titulo'],
        ];
    }
}
