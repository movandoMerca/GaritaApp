<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ExpandVehicleDocumentNumber extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE visitas MODIFY numeroDocumento VARCHAR(64) NULL');
    }

    public function down()
    {
        if (DB::table('visitas')->whereRaw('CHAR_LENGTH(numeroDocumento) > 8')->exists()) {
            throw new RuntimeException('No se puede revertir: existen documentos de más de 8 caracteres.');
        }

        DB::statement('ALTER TABLE visitas MODIFY numeroDocumento VARCHAR(8) NULL');
    }
}
