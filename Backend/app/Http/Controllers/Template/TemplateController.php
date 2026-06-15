<?php

declare(strict_types=1);

namespace App\Http\Controllers\Template;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Shared\Domain\Criteria\Order;
use Shared\Domain\ValueObject\Uuid;
use Throwable;
use Tickets\Template\Application\GetList\TemplateGetListQuery;
use Tickets\Template\Application\Preview\PreviewTemplateQuery;
use Tickets\Template\Application\TemplateApplication;
use Tickets\Template\Domain\TemplateKind;
use Tickets\Template\Dto\TemplateDto;
use Tickets\Template\Dto\TemplateVersionDto;

/**
 * CRUD шаблонов писем/PDF (AF-3). Только admin (auth:api + admin).
 * Чтение списка — через QueryBus (whitelist), запись — тонким слоем TemplateApplication.
 */
class TemplateController extends Controller
{
    public function getList(
        Request $request,
        TemplateApplication $application,
    ): JsonResponse {
        $data = $request->toArray();

        // Order::fromState кидает на чужих значениях orderBy — оборачиваем (как в qrOrder/getList).
        try {
            $orderBy = Order::fromState($data['orderBy'] ?? []);
        } catch (InvalidArgumentException) {
            $orderBy = Order::none();
        }

        $collection = $application->getList(
            new TemplateGetListQuery($data['filter'] ?? [], $orderBy),
        )->getCollection();

        return response()->json([
            'success' => true,
            'list' => $collection->map(fn (TemplateDto $dto) => $dto->toArray())->values()->all(),
        ]);
    }

    /**
     * Предпросмотр шаблона на ТЕСТОВЫХ данных (без ПДн). Рендерит переданный body:
     * email → JSON { html } (фронт в iframe), pdf → поток application/pdf (через DomPDF, как в проде).
     * Ошибка синтаксиса Mustache → 422 (не 500).
     */
    public function preview(
        Request $request,
        TemplateApplication $application,
    ): JsonResponse|HttpResponse {
        $data = $request->toArray();
        $kind = (($data['kind'] ?? '') === TemplateKind::PDF) ? TemplateKind::PDF : TemplateKind::EMAIL;
        $slug = (string) ($data['slug'] ?? $kind);
        $body = (string) ($data['body'] ?? '');

        try {
            $response = $application->getPreview(new PreviewTemplateQuery($kind, $slug, $body));
        } catch (Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка рендера шаблона: ' . $exception->getMessage(),
            ], 422);
        }

        if ($kind === TemplateKind::PDF) {
            $pdf = Pdf::loadHTML($response->getHtml());

            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="preview.pdf"',
            ]);
        }

        return response()->json([
            'success' => true,
            'html' => $response->getHtml(),
        ]);
    }

    public function getItem(
        string $id,
        TemplateApplication $application,
    ): JsonResponse {
        try {
            return response()->json([
                'success' => true,
                'item' => $application->getItem(new Uuid($id))->toArray(),
            ]);
        } catch (DomainException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 404);
        }
    }

    /**
     * @throws Throwable
     */
    public function create(
        Request $request,
        TemplateApplication $application,
    ): JsonResponse {
        $data = TemplateDto::fromState($request->toArray()['data']);

        return response()->json([
            'success' => $application->create($data),
            'item' => $application->getItem($data->getId())->toArray(),
            'message' => 'Шаблон создан',
        ]);
    }

    /**
     * @throws Throwable
     */
    public function edit(
        string $id,
        Request $request,
        TemplateApplication $application,
    ): JsonResponse {
        try {
            return response()->json([
                'success' => $application->edit(
                    new Uuid($id),
                    TemplateDto::fromState($request->toArray()['data']),
                ),
                'item' => $application->getItem(new Uuid($id))->toArray(),
                'message' => 'Шаблон сохранён',
            ]);
        } catch (DomainException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 404);
        }
    }

    public function activate(
        string $id,
        Request $request,
        TemplateApplication $application,
    ): JsonResponse {
        $active = (bool) ($request->toArray()['active'] ?? true);

        try {
            return response()->json([
                'success' => $application->activate(new Uuid($id), $active),
                'item' => $application->getItem(new Uuid($id))->toArray(),
                'message' => $active ? 'Шаблон активирован' : 'Шаблон деактивирован',
            ]);
        } catch (DomainException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 404);
        }
    }

    /** Id текущего админа строкой (Auth::id() в проекте может вернуть Uuid VO). */
    private function authorId(): ?string
    {
        $id = Auth::id();

        if ($id === null) {
            return null;
        }

        return $id instanceof Uuid ? $id->value() : (string) $id;
    }

    /** Сохранить черновик (draft_body) — прод (body) не затрагивается. */
    public function saveDraft(
        string $id,
        Request $request,
        TemplateApplication $application,
    ): JsonResponse {
        $draftBody = (string) ($request->toArray()['draft_body'] ?? '');

        return response()->json([
            'success' => $application->saveDraft(new Uuid($id), $draftBody),
            'message' => 'Черновик сохранён',
        ]);
    }

    /** Опубликовать переданный body: body ← body, + снапшот в историю версий. */
    public function publish(
        string $id,
        Request $request,
        TemplateApplication $application,
    ): JsonResponse {
        $data = $request->toArray();

        try {
            $success = $application->publish(
                new Uuid($id),
                (string) ($data['body'] ?? ''),
                $this->authorId(),
                $data['comment'] ?? null,
            );

            return response()->json([
                'success' => $success,
                'item' => $application->getItem(new Uuid($id))->toArray(),
                'message' => 'Шаблон опубликован',
            ]);
        } catch (DomainException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 404);
        }
    }

    /** Палитра плейсхолдеров для редактора (вставка кликом). kind — query-параметр (email/pdf). */
    public function variables(
        string $slug,
        Request $request,
        TemplateApplication $application,
    ): JsonResponse {
        $kind = ($request->query('kind') === TemplateKind::PDF) ? TemplateKind::PDF : TemplateKind::EMAIL;

        return response()->json([
            'success' => true,
            'kind' => $kind,
            'slug' => $slug,
            'variables' => $application->getVariables($kind, $slug),
        ]);
    }

    /** История версий шаблона (новые сверху). */
    public function versions(
        string $id,
        TemplateApplication $application,
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'versions' => $application->getVersions(new Uuid($id))
                ->map(fn (TemplateVersionDto $dto) => $dto->toArray())
                ->values()
                ->all(),
        ]);
    }

    /** Откатить body шаблона к версии (создаёт новую версию-«откат»). */
    public function rollback(
        string $id,
        string $versionId,
        TemplateApplication $application,
    ): JsonResponse {
        try {
            $success = $application->rollback(new Uuid($id), new Uuid($versionId), $this->authorId());

            return response()->json([
                'success' => $success,
                'item' => $application->getItem(new Uuid($id))->toArray(),
                'message' => 'Откат выполнен',
            ]);
        } catch (DomainException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 404);
        }
    }
}
