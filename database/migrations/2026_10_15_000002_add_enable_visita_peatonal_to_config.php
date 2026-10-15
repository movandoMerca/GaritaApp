<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEnableVisitaPeatonalToConfig extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('config', 'enable_visita_peatonal')) {
            Schema::table('config', function (Blueprint $table) {
                $table->boolean('enable_visita_peatonal')->default(true);
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('config', 'enable_visita_peatonal')) {
            Schema::table('config', function (Blueprint $table) {
                $table->dropColumn('enable_visita_peatonal');
            });
        }
    }
}
