<?php

declare(strict_types=1);

namespace Tickets\EmailDelivery\Repositories;

use App\Models\EmailDelivery\EmailMessageModel;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Shared\Domain\Criteria\Filters;
use Shared\Domain\Criteria\Order;
use Shared\Domain\Filter\FilterBuilder;
use Shared\Domain\ValueObject\Uuid;
use Tickets\EmailDelivery\Domain\ValueObject\EmailStatus;
use Tickets\EmailDelivery\Dto\EmailMessageDto;
use Tickets\EmailDelivery\Responses\EmailMessageItemForListResponse;

final class InMemoryMySqlEmailMessageRepository implements EmailMessageRepositoryInterface
{
    public function __construct(
        private EmailMessageModel $model,
    ) {
    }

    public function create(EmailMessageDto $dto, ?string $mailableBlob): bool
    {
        // create() (а не insert) — чтобы сработал каст 'meta' => 'array' (JSON кодируется один раз).
        return (bool) $this->model::create(array_merge(
            $dto->toArrayForCreate(),
            ['mailable' => $mailableBlob],
        ));
    }

    public function findById(Uuid $id): ?EmailMessageDto
    {
        $row = $this->model::whereId($id->value())->first();

        return $row === null ? null : EmailMessageDto::fromState($row->toArray());
    }

    public function findByToken(string $token): ?EmailMessageDto
    {
        $row = $this->model::query()->where('tracking_token', $token)->first();

        return $row === null ? null : EmailMessageDto::fromState($row->toArray());
    }

    public function getMailableBlob(Uuid $id): ?string
    {
        $row = $this->model::query()->select('mailable')->whereId($id->value())->first();

        return $row?->mailable;
    }

    public function markSending(Uuid $id): bool
    {
        $row = $this->model::whereId($id->value())->first();
        if ($row === null) {
            return false;
        }
        $row->status = EmailStatus::SENDING;
        $row->attempts = (int) $row->attempts + 1;

        return $row->save();
    }

    public function markSent(Uuid $id, ?string $providerMessageId): bool
    {
        return (bool) $this->model::whereId($id->value())->update([
            'status' => EmailStatus::SENT,
            'sent_at' => Carbon::now(),
            'error' => null,
            'provider_message_id' => $providerMessageId,
        ]);
    }

    public function markFailed(Uuid $id, string $error): bool
    {
        return (bool) $this->model::whereId($id->value())->update([
            'status' => EmailStatus::FAILED,
            'error' => mb_substr($error, 0, 2000),
        ]);
    }

    public function requeue(Uuid $id): bool
    {
        return (bool) $this->model::whereId($id->value())->update([
            'status' => EmailStatus::QUEUED,
            'error' => null,
        ]);
    }

    public function markOpened(Uuid $id): bool
    {
        // Идемпотентно: opened_at ставим один раз (первое открытие).
        return (bool) $this->model::whereId($id->value())
            ->whereNull('opened_at')
            ->update([
                'status' => EmailStatus::OPENED,
                'opened_at' => Carbon::now(),
            ]);
    }

    public function getList(Filters $filters, Order $orderBy, int $page, int $perPage): Collection
    {
        $build = FilterBuilder::build($this->model::query(), $filters);

        if ($orderBy->orderBy()->value()) {
            $build = $build->orderBy($orderBy->orderBy()->value(), $orderBy->orderType()->value());
        } else {
            $build = $build->orderByDesc('created_at');
        }

        return $build->forPage($page, $perPage)
            ->get()
            ->map(fn (EmailMessageModel $model) => EmailMessageItemForListResponse::fromState($model->toArray()));
    }

    public function countList(Filters $filters): int
    {
        return FilterBuilder::build($this->model::query(), $filters)->count();
    }

    public function getByAggregate(string $aggregateType, Uuid $aggregateId): Collection
    {
        return $this->model::query()
            ->where('aggregate_type', $aggregateType)
            ->where('aggregate_id', $aggregateId->value())
            ->orderBy('created_at')
            ->get()
            ->map(fn (EmailMessageModel $model) => EmailMessageItemForListResponse::fromState($model->toArray()));
    }
}
