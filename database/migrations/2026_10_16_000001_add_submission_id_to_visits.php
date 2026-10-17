<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSubmissionIdToVisits extends Migration
{
    public function up()
    {
        Schema::table('visitas', function (Blueprint $table) {
            $table->uuid('submission_id')->nullable()->unique();
        });
    }

    public function down()
    {
        Schema::table('visitas', function (Blueprint $table) {
            $table->dropUnique(['submission_id']);
            $table->dropColumn('submission_id');
        });
    }
}
