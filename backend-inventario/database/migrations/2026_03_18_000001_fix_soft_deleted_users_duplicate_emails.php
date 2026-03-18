<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ofuscar emails de usuarios soft-deleted que colisionen con emails activos
        $softDeleted = DB::table('usuarios')
            ->whereNotNull('deleted_at')
            ->get(['id', 'email']);

        foreach ($softDeleted as $usuario) {
            $existeActivo = DB::table('usuarios')
                ->whereNull('deleted_at')
                ->where('email', $usuario->email)
                ->exists();

            if ($existeActivo) {
                DB::table('usuarios')
                    ->where('id', $usuario->id)
                    ->update([
                        'email' => 'deleted_' . $usuario->id . '_' . time() . '@deleted.local',
                    ]);
            }
        }
    }

    public function down(): void
    {
        // No reversible
    }
};
