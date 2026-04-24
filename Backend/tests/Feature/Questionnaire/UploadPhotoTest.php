<?php

declare(strict_types=1);

namespace Tests\Feature\Questionnaire;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Shared\Domain\ValueObject\Uuid;
use Tests\TestCase;
use Tickets\Order\OrderTicket\Dto\OrderTicket\OrderTicketDto;
use Tickets\Order\OrderTicket\Dto\OrderTicket\PriceDto;
use Tickets\Order\OrderTicket\Repositories\OrderTicketRepositoryInterface;

/**
 * Feature тест для QuestionnaireController::uploadPhoto().
 *
 * Проверяет HTTP-слой: корректный ответ, 404, 422 при ошибках валидации.
 * Репозиторий мокируется через DI-контейнер Laravel.
 */
class UploadPhotoTest extends TestCase
{
    private const ORDER_ID  = '222abc0c-fc8e-4a1d-a4b0-d345cafacf95';
    private const TICKET_ID = '56f04400-02ab-4cbe-bfd4-4f7dda23d675';
    private const FESTIVAL_ID = '9d679bcf-b438-4ddb-ac04-023fa9bff4b8';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * Вспомогательный метод: строим минимальный OrderTicketDto для мока.
     */
    private function makeOrderTicketDto(): OrderTicketDto
    {
        $data = [
            'festival_id'         => self::FESTIVAL_ID,
            'email'               => 'test@example.com',
            'phone'               => '+79991234567',
            'types_of_payment_id' => '3fcded69-4aef-4c4a-a041-52c91e5afd63',
            'ticket_type_id'      => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
            'guests'              => [['value' => 'Гость', 'festival_id' => self::FESTIVAL_ID]],
            'id_buy'              => '',
            'date'                => '',
            'status'              => 'new',
            'promo_code'          => null,
            'id'                  => self::ORDER_ID,
        ];

        return OrderTicketDto::fromState(
            $data,
            new Uuid('dddddddd-dddd-dddd-dddd-dddddddddddd'),
            new PriceDto(3800, 1, 0),
        );
    }

    /**
     * Сценарий: успешная загрузка jpeg-файла.
     * Ожидаем 200, success=true и корректный photo_url.
     *
     * @test
     */
    public function upload_photo_returns_200_and_photo_url_on_success(): void
    {
        $orderDto = $this->makeOrderTicketDto();

        $repositoryMock = Mockery::mock(OrderTicketRepositoryInterface::class);
        $repositoryMock->shouldReceive('findOrder')
            ->once()
            ->andReturn($orderDto);

        $this->app->instance(OrderTicketRepositoryInterface::class, $repositoryMock);

        $file = UploadedFile::fake()->image('badge.jpg', 200, 200);

        $response = $this->postJson(
            '/api/v1/questionnaire/uploadPhoto/' . self::ORDER_ID . '/' . self::TICKET_ID,
            ['photo' => $file],
        );

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'photo_url'])
            ->assertJson(['success' => true]);

        $photoUrl = $response->json('photo_url');
        $this->assertStringContainsString('/storage/badges/' . self::FESTIVAL_ID . '/' . self::TICKET_ID, $photoUrl);

        // Файл действительно сохранён на диске
        Storage::disk('public')->assertExists('badges/' . self::FESTIVAL_ID . '/' . self::TICKET_ID . '.jpg');
    }

    /**
     * Сценарий: заказ не найден в репозитории.
     * Ожидаем 404 и сообщение "Заказ не найден".
     *
     * @test
     */
    public function upload_photo_returns_404_when_order_not_found(): void
    {
        $repositoryMock = Mockery::mock(OrderTicketRepositoryInterface::class);
        $repositoryMock->shouldReceive('findOrder')
            ->once()
            ->andReturn(null);

        $this->app->instance(OrderTicketRepositoryInterface::class, $repositoryMock);

        $file = UploadedFile::fake()->image('badge.jpg', 200, 200);

        $response = $this->postJson(
            '/api/v1/questionnaire/uploadPhoto/' . self::ORDER_ID . '/' . self::TICKET_ID,
            ['photo' => $file],
        );

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Заказ не найден',
            ]);
    }

    /**
     * Сценарий: файл photo не передан в запросе.
     * Ожидаем 422 — ошибка валидации Laravel.
     *
     * @test
     */
    public function upload_photo_returns_422_when_photo_not_provided(): void
    {
        $response = $this->postJson(
            '/api/v1/questionnaire/uploadPhoto/' . self::ORDER_ID . '/' . self::TICKET_ID,
            [],
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['photo']);
    }

    /**
     * Сценарий: передан txt-файл вместо изображения.
     * Ожидаем 422 — валидация 'image|mimes:jpeg,jpg,png,webp' не проходит.
     *
     * @test
     */
    public function upload_photo_returns_422_when_file_is_not_an_image(): void
    {
        $file = UploadedFile::fake()->create('document.txt', 100, 'text/plain');

        $response = $this->postJson(
            '/api/v1/questionnaire/uploadPhoto/' . self::ORDER_ID . '/' . self::TICKET_ID,
            ['photo' => $file],
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['photo']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
