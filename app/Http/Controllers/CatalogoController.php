<?php

namespace App\Http\Controllers;

use App\Support\CatalogosAdministrables;
use App\Support\CatalogosProfesionales;
use Illuminate\Http\JsonResponse;

/**
 * Entrega un catálogo como JSON, para que el combobox no lo lleve incrustado.
 *
 * El catálogo de cargos son 30.000 valores: 733 KB por cada combobox que se renderice,
 * repetidos en cada respuesta de Livewire. Servirlo desde una URL propia lo baja a una
 * sola descarga que el navegador cachea, compartida por todas las instancias de la
 * pantalla y por todas las visitas siguientes.
 *
 * La URL lleva la versión del catálogo (ver [CatalogosProfesionales::version()]), así
 * que un admin puede editar los términos y la caché se invalida sola al cambiar la URL.
 */
class CatalogoController extends Controller
{
    /** Un día: la URL cambia si cambia el contenido, así que puede ser generosa. */
    private const SEGUNDOS_DE_CACHE = 86400;

    public function __invoke(string $catalogo): JsonResponse
    {
        abort_unless(CatalogosAdministrables::existe($catalogo), 404);

        $origen = CatalogosAdministrables::definicion($catalogo)['origen'];

        /** @var list<string> $valores */
        $valores = CatalogosProfesionales::$origen();

        return response()
            ->json($valores)
            ->setPublic()
            ->setMaxAge(self::SEGUNDOS_DE_CACHE)
            ->setEtag(CatalogosProfesionales::version($catalogo));
    }
}
