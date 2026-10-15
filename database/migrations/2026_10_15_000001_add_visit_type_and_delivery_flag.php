<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddVisitTypeAndDeliveryFlag extends Migration
{
    public function up()
    {
        // The legacy schema is imported from data.sql; MySQL ALTER avoids a DBAL dependency.
        DB::statement('ALTER TABLE visitas
            MODIFY Placa VARCHAR(8) NULL,
            MODIFY numeroDocumento VARCHAR(8) NULL,
            MODIFY tipoLicencia VARCHAR(2) NULL,
            MODIFY Fecha_vencimiento VARCHAR(20) NULL');

        Schema::table('visitas', function (Blueprint $table) {
            $table->string('tipo_visita', 20)->default('vehicular');
            $table->boolean('es_repartidor')->default(false);
        });
    }

    public function down()
    {
        foreach (['Placa', 'numeroDocumento', 'tipoLicencia', 'Fecha_vencimiento'] as $column) {
            if (DB::table('visitas')->whereNull($column)->exists()) {
                throw new RuntimeException('No se puede revertir: existen visitas sin datos vehiculares.');
            }
        }

        DB::statement('ALTER TABLE visitas
            MODIFY Placa VARCHAR(8) NOT NULL,
            MODIFY numeroDocumento VARCHAR(8) NOT NULL,
            MODIFY tipoLicencia VARCHAR(2) NOT NULL,
            MODIFY Fecha_vencimiento VARCHAR(20) NOT NULL');

        Schema::table('visitas', function (Blueprint $table) {
            $table->dropColumn(['tipo_visita', 'es_repartidor']);
        });
    }
}
