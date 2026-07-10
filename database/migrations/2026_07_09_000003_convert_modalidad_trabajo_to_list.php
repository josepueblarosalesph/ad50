<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('postulantes')
            ->whereNotNull('modalidad_trabajo')
            ->where('modalidad_trabajo', '!=', '')
            ->orderBy('id')
            ->each(function (object $postulante): void {
                DB::table('postulantes')
                    ->where('id', $postulante->id)
                    ->update(['modalidad_trabajo' => json_encode([$postulante->modalidad_trabajo], JSON_UNESCAPED_UNICODE)]);
            });
    }

    public function down(): void
    {
        DB::table('postulantes')
            ->whereNotNull('modalidad_trabajo')
            ->orderBy('id')
            ->each(function (object $postulante): void {
                $modalidades = json_decode((string) $postulante->modalidad_trabajo, true);

                DB::table('postulantes')
                    ->where('id', $postulante->id)
                    ->update(['modalidad_trabajo' => is_array($modalidades) ? ($modalidades[0] ?? null) : $postulante->modalidad_trabajo]);
            });
    }
};
