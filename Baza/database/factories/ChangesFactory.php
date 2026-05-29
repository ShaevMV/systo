<?php

namespace Database\Factories;

use App\Models\ChangesModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Nette\Utils\Json;

/**
 * Factory для тестовых смен сканеров.
 *
 * Поле user_id — JSON-массив идентификаторов пользователей,
 * участвующих в смене. По умолчанию закладывается user_id=1
 * (см. UsersTableSeeder — Admin Admin).
 *
 * @extends Factory<ChangesModel>
 */
class ChangesFactory extends Factory
{
    protected $model = ChangesModel::class;

    public function definition(): array
    {
        return [
            'user_id' => Json::encode([1]),
            'count_live_tickets' => 0,
            'count_el_tickets' => 0,
            'count_drug_tickets' => 0,
            'count_spisok_tickets' => 0,
            'count_auto_tickets' => 0,
            'count_parking_tickets' => 0,
            'count_parking_free_tickets' => 0,
            'count_parking_cross-country_tickets' => 0,
            'start' => now(),
            'end' => null,
            'festival_id' => '9d679bcf-b438-4ddb-ac04-023fa9bff4b8',
        ];
    }

    /**
     * Закрытая (завершённая) смена — заполнен `end`.
     */
    public function closed(): static
    {
        return $this->state(fn () => [
            'end' => now(),
        ]);
    }

    /**
     * Смена для другого набора пользователей.
     *
     * @param  int[]  $userIds
     */
    public function forUsers(array $userIds): static
    {
        return $this->state(fn () => [
            'user_id' => Json::encode($userIds),
        ]);
    }
}
