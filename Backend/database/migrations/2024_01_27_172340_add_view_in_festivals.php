<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('festivals', function (Blueprint $table) {
            $table->string('view')->nullable(true);
        });
    }

    public function down()
    {
        Schema::table('festivals', function (Blueprint $table) {
            $table->dropColumn('view');
        });
    }
};
