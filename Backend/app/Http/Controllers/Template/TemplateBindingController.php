<?php

declare(strict_types=1);

namespace App\Http\Controllers\Template;

use App\Http\Controllers\Controller;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Shared\Domain\ValueObject\Uuid;
use Tickets\TemplateBinding\Application\TemplateBindingApplication;
use Tickets\TemplateBinding\Dto\TemplateBindingDto;

/**
 * CRUD привязок шаблонов к (festival, order_type, ticket_type) → email/pdf шаблон + дефолт.
 * Только admin. Резолв применяется в рендере билетов (InMemoryMySqlTicketsRepository::getTicket).
 */
class TemplateBindingController extends Controller
{
    public function getList(TemplateBindingApplication $application): JsonResponse
    {
        return response()->json([
            'success' => true,
            'list' => $application->getList()
                ->map(fn (TemplateBindingDto $dto) => $dto->toArray())
                ->values()
                ->all(),
        ]);
    }

    public function getItem(string $id, TemplateBindingApplication $application): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'item' => $application->getItem(new Uuid($id))->toArray(),
            ]);
        } catch (DomainException $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 404);
        }
    }

    public function create(Request $request, TemplateBindingApplication $application): JsonResponse
    {
        $data = $request->toArray()['data'] ?? [];

        if ($error = $this->validateBinding($data, $application)) {
            return response()->json(['success' => false, 'message' => $error], 422);
        }

        $dto = TemplateBindingDto::fromState($data);

        return response()->json([
            'success' => $application->create($dto),
            'item' => $application->getItem($dto->getId())->toArray(),
            'message' => 'Привязка создана',
        ]);
    }

    public function edit(string $id, Request $request, TemplateBindingApplication $application): JsonResponse
    {
        $data = array_merge($request->toArray()['data'] ?? [], ['id' => $id]);

        if ($error = $this->validateBinding($data, $application, $id)) {
            return response()->json(['success' => false, 'message' => $error], 422);
        }

        try {
            $application->edit(new Uuid($id), TemplateBindingDto::fromState($data));

            return response()->json([
                'success' => true,
                'item' => $application->getItem(new Uuid($id))->toArray(),
                'message' => 'Привязка сохранена',
            ]);
        } catch (DomainException $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 404);
        }
    }

    public function delete(string $id, TemplateBindingApplication $application): JsonResponse
    {
        return response()->json([
            'success' => $application->delete(new Uuid($id)),
            'message' => 'Привязка удалена',
        ]);
    }

    /** Валидация: нужен хотя бы один шаблон; не больше одной активной дефолт-привязки. */
    private function validateBinding(array $data, TemplateBindingApplication $application, ?string $excludeId = null): ?string
    {
        $hasTemplate = ! empty($data['email_template_id']) || ! empty($data['pdf_template_id']);
        if (! $hasTemplate) {
            return 'Укажите хотя бы один шаблон (письма или PDF)';
        }

        $isDefault = (bool) ($data['is_default'] ?? false);
        $active = (bool) ($data['active'] ?? true);
        if ($isDefault && $active && $application->hasActiveDefault($excludeId)) {
            return 'Активная привязка по умолчанию уже существует';
        }

        return null;
    }
}
