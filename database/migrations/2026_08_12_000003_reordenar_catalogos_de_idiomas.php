<?php

use App\Support\CatalogosProfesionales;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Nuevo orden de idiomas y niveles de idioma.
 *
 * A diferencia de los catálogos de estudios, aquí no se elimina ningún valor: los idiomas
 * son los mismos y solo cambian de orden (los cinco más habituales arriba), y en niveles
 * se **suma** "Bilingüe / Nativo" sobre los dos que ya había. Por eso no hace falta
 * remapear nada de lo guardado: ninguna ficha queda apuntando a un valor inexistente.
 */
return new class extends Migration
{
    /** @var list<string> */
    private const IDIOMAS = [
        'Español', 'Inglés', 'Portugués', 'Francés', 'Alemán',
        'Chino Mandarín', 'Coreano', 'Italiano', 'Japonés', 'Mapudungun', 'Polaco', 'Ruso',
    ];

    /** @var list<string> */
    private const NIVELES = ['Bilingüe / Nativo', 'Avanzado', 'Intermedio'];

    public function up(): void
    {
        $this->reescribirCatalogo('idioma', self::IDIOMAS);
        $this->reescribirCatalogo('nivel_idioma', self::NIVELES);

        CatalogosProfesionales::olvidar();
    }

    public function down(): void
    {
        $this->reescribirCatalogo('idioma', [
            'Alemán', 'Chino Mandarín', 'Coreano', 'Español', 'Francés', 'Inglés',
            'Italiano', 'Japonés', 'Mapudungun', 'Polaco', 'Portugués', 'Ruso',
        ]);
        $this->reescribirCatalogo('nivel_idioma', ['Intermedio', 'Avanzado']);

        CatalogosProfesionales::olvidar();
    }

    /**
     * Deja el catálogo administrable exactamente con estos términos y en este orden.
     *
     * @param  list<string>  $terminos
     */
    private function reescribirCatalogo(string $catalogo, array $terminos): void
    {
        DB::table('terminos_catalogo')->where('catalogo', $catalogo)->delete();

        $ahora = now();

        DB::table('terminos_catalogo')->insert(
            collect($terminos)->values()->map(fn (string $termino, int $orden): array => [
                'catalogo' => $catalogo,
                'valor' => $termino,
                'orden' => $orden,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ])->all()
        );
    }
};
