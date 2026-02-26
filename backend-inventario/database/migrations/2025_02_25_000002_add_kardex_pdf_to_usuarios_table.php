<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('kardex_pdf_ruta')->nullable()->after('activo');
            $table->string('kardex_pdf_nombre_original')->nullable()->after('kardex_pdf_ruta');
            $table->unsignedBigInteger('kardex_pdf_tamano')->nullable()->after('kardex_pdf_nombre_original');
            $table->timestamp('kardex_pdf_subido_en')->nullable()->after('kardex_pdf_tamano');
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn(['kardex_pdf_ruta', 'kardex_pdf_nombre_original', 'kardex_pdf_tamano', 'kardex_pdf_subido_en']);
        });
    }
};
