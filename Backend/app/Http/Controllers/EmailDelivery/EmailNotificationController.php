<?php

declare(strict_types=1);

namespace App\Http\Controllers\EmailDelivery;

use App\Http\Controllers\Controller;
use App\Mail\GenericTemplatedMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tickets\EmailDelivery\Application\EmailContext;
use Tickets\EmailDelivery\Application\MailDispatcher;
use Tickets\EmailDelivery\Domain\EmailEvent;
use Tickets\EmailDelivery\Repositories\EmailMessageRepositoryInterface;
use Tickets\History\Domain\ActorType;
use Tickets\TemplateBinding\Application\TemplateBindingApplication;

/**
 * S2S-приём писем от витрины qr (Ф4): qr шлёт «отправь письмо <event> на <email> с <vars>».
 * Для не-заказных писем (регистрация, сброс пароля и т.п.), инициированных на витрине.
 * Канал закрыт сервисным ключом qr (middleware qr.ingest). Slug выбирается привязкой по событию
 * (Ф1) с fallback на дефолт события; письмо отслеживается через MailDispatcher (Ф2).
 */
class EmailNotificationController extends Controller
{
    public function send(
        Request $request,
        MailDispatcher $dispatcher,
        TemplateBindingApplication $bindings,
        EmailMessageRepositoryInterface $repository,
    ): JsonResponse {
        $data = $request->toArray();

        $event = (string) ($data['event'] ?? '');
        if (! EmailEvent::isValid($event)) {
            return response()->json(['success' => false, 'message' => 'Неизвестное событие письма'], 422);
        }

        $email = trim((string) ($data['email'] ?? ''));
        if ($email === '') {
            return response()->json(['success' => false, 'message' => 'Не передан email получателя'], 422);
        }

        $externalId = isset($data['external_id']) ? (string) $data['external_id'] : null;

        // Идемпотентность: повтор того же external_id не создаёт дубль письма.
        if ($externalId !== null && $repository->existsByExternalId($externalId)) {
            return response()->json(['success' => true, 'message' => 'Уже принято ранее (идемпотентно)']);
        }

        $vars = is_array($data['vars'] ?? null) ? $data['vars'] : [];
        $festivalId = isset($data['festival_id']) ? (string) $data['festival_id'] : null;
        $orderType = isset($data['order_type']) ? (string) $data['order_type'] : null;
        $ticketTypeId = isset($data['ticket_type_id']) ? (string) $data['ticket_type_id'] : null;

        $slug = $bindings->resolveSlug('email', $event, $festivalId, $orderType, $ticketTypeId)
            ?? EmailEvent::defaultSlug($event);

        $subject = (string) ($data['subject'] ?? $vars['subject'] ?? 'Уведомление');

        $emailId = $dispatcher->send(
            $event,
            new EmailContext(
                recipient: $email,
                festivalId: $festivalId,
                orderType: $orderType,
                ticketTypeId: $ticketTypeId,
                source: 'qr_intake',
                actorType: ActorType::QR,
                aggregateType: isset($data['aggregate_id']) ? 'qr_order' : null,
                aggregateId: isset($data['aggregate_id']) ? (string) $data['aggregate_id'] : null,
                meta: $externalId !== null ? ['external_id' => $externalId] : [],
            ),
            new GenericTemplatedMail((string) $slug, $subject, $vars),
        );

        return response()->json([
            'success' => true,
            'email_id' => $emailId->value(),
            'message' => 'Письмо принято',
        ]);
    }
}
