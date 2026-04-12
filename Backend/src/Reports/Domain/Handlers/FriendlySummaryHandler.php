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

        if ($limit) {
            $query->limit($limit);
        }

        $data = $query->get();

        if ($data->isEmpty()) {
            return [];
        }

        $userIdList = $data->pluck('id')->toArray();
        $userNameList = $data->pluck('name')->toArray();

        $liveStats = $this->getLiveStats($userIdList, $festivalId);
        $listStats = $this->getListStats($userNameList, $festivalId);

        foreach ($data as &$row) {
            $row->live_sum = $liveStats[$row->id]['sum'] ?? 0;
            $row->live_count = $liveStats[$row->id]['count'] ?? 0;
            $row->list_count = $listStats[$row->name] ?? 0;
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

    private function getLiveStats(array $userIds, ?string $festivalId): array
    {
        if (empty($userIds)) {
            return [];
        }

        $query = DB::connection('mysql')
            ->table('order_tickets as ot')
            ->join('ticket_types as tt', 'ot.ticket_type_id', '=', 'tt.id')
            ->whereIn('ot.friendly_id', $userIds)
            ->where('tt.is_live_ticket', true)
            ->selectRaw('ot.friendly_id, SUM(ot.price) as live_sum, COUNT(*) as live_count')
            ->groupBy('ot.friendly_id');

        if ($festivalId) {
            $query->where('ot.festival_id', $festivalId);
        }

        $result = [];
        foreach ($query->get() as $row) {
            $result[$row->friendly_id] = [
                'sum' => (float) $row->live_sum,
                'count' => (int) $row->live_count,
            ];
        }

        return $result;
    }

    private function getListStats(array $userNames, ?string $festivalId): array
    {
        if (empty($userNames)) {
            return [];
        }

        $query = DB::connection('mysqlBaza')
            ->table('spisok_tickets')
            ->whereIn('curator', $userNames)
            ->selectRaw('curator, COUNT(*) as list_count')
            ->groupBy('curator');

        if ($festivalId) {
            $query->where('festival_id', $festivalId);
        }

        return $query->get()->pluck('list_count', 'curator')->toArray();
    }
}
