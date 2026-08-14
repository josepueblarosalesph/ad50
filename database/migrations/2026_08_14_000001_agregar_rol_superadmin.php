<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cuarto rol: `superadmin`.
 *
 * El admin actual habilita empresas y supervisa la plataforma, pero no puede tocar la
 * cuenta de nadie. Cambiar el tipo de un usuario es una operación de otra categoría
 * —convierte a un postulante en reclutador, o crea otro administrador— así que vive en
 * un rol aparte en lugar de sumarse a lo que ya puede hacer cualquier admin.
 *
 * El superadmin hereda todas las facultades del admin (ver User::esAdmin()); lo único
 * exclusivo es la pantalla de Usuarios.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            // En Postgres el enum de Laravel es un varchar con un CHECK: hay que
            // reescribir la restricción, no la columna.
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role::text = ANY (ARRAY['postulante'::text, 'empresa'::text, 'admin'::text, 'superadmin'::text]))");

            return;
        }

        // MySQL/MariaDB guardan el enum como tipo nativo: se redefine la columna.
        Schema::table('users', function (Blueprint $t): void {
            $t->enum('role', ['postulante', 'empresa', 'admin', 'superadmin'])->default('postulante')->change();
        });
    }

    public function down(): void
    {
        // Sin el rol en el enum, un superadmin dejaría la fila fuera de la restricción.
        DB::table('users')->where('role', 'superadmin')->update(['role' => 'admin']);

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role::text = ANY (ARRAY['postulante'::text, 'empresa'::text, 'admin'::text]))");

            return;
        }

        Schema::table('users', function (Blueprint $t): void {
            $t->enum('role', ['postulante', 'empresa', 'admin'])->default('postulante')->change();
        });
    }
};
