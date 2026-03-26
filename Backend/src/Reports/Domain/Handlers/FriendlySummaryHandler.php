<?php

namespace Tickets\Reports\Domain\Handlers;

use Illuminate\Support\Facades\DB;
use Tickets\Reports\Domain\ReportHandlerInterface;

class FriendlySummaryHandler implements ReportHandlerInterface
{
    public function getType(): string
    {
        return 'friendly_summary';
    }

    public function getName(): string
    {
        return 'Френдли (сводный по пушерам)';
    }

    public function getHeaders(): array
    {
        return [
            '#',
            'Email',
            'Имя',
            'Проект',
            'Сумма за френдли',
            'Кол-во за френдли',
            'Сумма за живые',
            'Кол-во за живые',
            'Кол-во за списки',
        ];
    }

    public function getData(array $filters): array
    {
        $festivalId = $filters['festival_id'] ?? null;
        $limit = $filters['limit'] ?? null;

        $query = DB::connection('mysql')
            ->table('users')
            ->select([
                'users.id',
                'users.email',
                'users.name',
                'top.name as project',
                DB::raw('COALESCE(SUM(ot.price), 0) as friendly_sum'),
                DB::raw('COALESCE(COUNT(DISTINCT ot.id), 0) as friendly_count'),
            ])
            ->leftJoin('types_of_payment as top', 'top.user_external_id', '=', 'users.id')
            ->leftJoin('order_tickets as ot', 'ot.friendly_id', '=', 'users.id')
            ->where('users.role', 'pusher')
            ->groupBy('users.id', 'users.email', 'users.name', 'top.name');

        if ($festivalId) {
            $query->where('ot.festival_id', $festivalId);
        }

        $data = $query->get();

        foreach ($data as &$row) {
            $row->live_sum = $this->getLiveSum($row->id, $festivalId);
            $row->live_count = $this->getLiveCount($row->id, $festivalId);
            $row->list_count = $this->getListCount($row->name, $festivalId);
        }

        if ($limit) {
            $data = $data->take($limit);
        }

        return $data->toArray();
    }

    public function formatRow(object $row, int $index): array
    {
        return [
            $index + 1,
            $row->email,
            $row->name,
            $row->project ?? 'Без проекта',
            number_format($row->friendly_sum, 2, '.', ''),
            $row->friendly_count,
            number_format($row->live_sum, 2, '.', ''),
            $row->live_count,
            $row->list_count,
        ];
    }

    private function getLiveSum(string $userId, ?string $festivalId): float
    {
        $query = DB::connection('mysql')
            ->table('order_tickets as ot')
            ->join('ticket_types as tt', 'ot.ticket_type_id', '=', 'tt.id')
            ->where('ot.friendly_id', $userId)
            ->where('tt.is_live_ticket', true);

        if ($festivalId) {
            $query->where('ot.festival_id', $festivalId);
        }

        return (float) $query->sum('ot.price');
    }

    private function getLiveCount(string $userId, ?string $festivalId): int
    {
        $query = DB::connection('mysql')
            ->table('order_tickets as ot')
            ->join('ticket_types as tt', 'ot.ticket_type_id', '=', 'tt.id')
            ->where('ot.friendly_id', $userId)
            ->where('tt.is_live_ticket', true);

        if ($festivalId) {
            $query->where('ot.festival_id', $festivalId);
        }

        return (int) $query->count('ot.id');
    }

    private function getListCount(string $userName, ?string $festivalId): int
    {
        $query = DB::connection('baza')
            ->table('spisok_tickets')
            ->where('curator', $userName);

        if ($festivalId) {
            $query->where('festival_id', $festivalId);
        }

        return (int) $query->count();
    }
}
