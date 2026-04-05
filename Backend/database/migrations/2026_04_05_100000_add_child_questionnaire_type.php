<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        // Создаём тип анкеты "Детская анкета"
        $childQuestionnaireTypeId = 'c3d4e5f6-a7b8-9012-cdef-345678901234';

        // Проверяем, не создан ли уже такой тип
        $exists = DB::table('questionnaire_type')
            ->where('id', $childQuestionnaireTypeId)
            ->exists();

        if (!$exists) {
            DB::table('questionnaire_type')->insert([
                'id' => $childQuestionnaireTypeId,
                'name' => 'Детская анкета',
                'code' => 'child',
                'active' => true,
                'sort' => 2,
                'questions' => json_encode([
                    [
                        'title' => 'Имя и Фамилия Ребенка',
                        'name' => 'childName',
                        'type' => 'string',
                        'validate' => null,
                        'required' => true,
                    ],
                    [
                        'title' => 'Сколько лет ребенку?',
                        'name' => 'childAge',
                        'type' => 'number',
                        'validate' => null,
                        'required' => true,
                    ],
                    [
                        'title' => 'Есть ли аллергия? (или другие медицинские особенности ребенка, которые необходимо знать в случае попадания в медпункт)',
                        'name' => 'allergy',
                        'type' => 'text',
                        'validate' => null,
                        'required' => false,
                    ],
                    [
                        'title' => 'Ваше имя и фамилия и номер телефона',
                        'name' => 'parentInfo',
                        'type' => 'string',
                        'validate' => null,
                        'required' => true,
                    ],
                    [
                        'title' => 'Номер телефон доверенного человека из города, который в случае ЧП не будет удивлён звонку из леса;))',
                        'name' => 'trustedPhone',
                        'type' => 'string',
                        'validate' => '/^\\+?[0-9\\s\\-\\(\\)]+$/',
                        'required' => false,
                    ],
                    [
                        'title' => 'Введите данные о платеже (дата и имя отправителя), либо данные о вашем участии на фестивале',
                        'name' => 'paymentInfo',
                        'type' => 'text',
                        'validate' => null,
                        'required' => true,
                    ],
                    [
                        'title' => 'Сумма платежа (если вы волонтер или оплатили абонемент в детском саду, поставьте 0)',
                        'name' => 'paymentAmount',
                        'type' => 'number',
                        'validate' => null,
                        'required' => true,
                    ],
                    [
                        'title' => 'Email или контакт в соцсетях',
                        'name' => 'contact',
                        'type' => 'string',
                        'validate' => null,
                        'required' => true,
                    ],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('questionnaire_type')
            ->where('id', 'c3d4e5f6-a7b8-9012-cdef-345678901234')
            ->delete();
    }
};
