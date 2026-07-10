<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('nombres')->nullable()->after('name');
            $t->string('apellidos')->nullable()->after('nombres');
        });

        foreach (DB::table('users')->select('id', 'name')->get() as $user) {
            $partes = preg_split('/\s+/', trim((string) $user->name), 2);

            DB::table('users')->where('id', $user->id)->update([
                'nombres' => $partes[0] ?? '',
                'apellidos' => $partes[1] ?? '',
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn(['nombres', 'apellidos']);
        });
    }
};
