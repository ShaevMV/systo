<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('questionnaire', function (Blueprint $table) {
            $table->text('creationOfSisto')
                ->nullable(true)
                ->default(null)
                ->comment('Считаете ли вы себя участвующим в сотворении Систо')
                ->after('questionForSysto');

            $table->text('activeOfEvent')
                ->nullable(true)
                ->default(null)
                ->comment('Готовы принимать более активное или творческое участие в создании события')
                ->after('questionForSysto');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('questionnaire', function (Blueprint $table) {
            $table->dropColumn('creationOfSisto');
            $table->dropColumn('activeOfEvent');
        });
    }
};
