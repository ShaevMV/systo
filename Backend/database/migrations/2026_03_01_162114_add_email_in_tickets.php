<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('tickets', 'email')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->string('email')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('tickets', 'email')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->dropColumn('email');
            });
        }

    }
};
