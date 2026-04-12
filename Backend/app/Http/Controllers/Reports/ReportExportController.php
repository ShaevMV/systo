<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\ReportConfig;
use Google\Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Tickets\Reports\Domain\ReportHandlerRegistry;
use Tickets\Reports\Infrastructure\GoogleSheetsClient;

class ReportExportController extends Controller
{
    public function __construct(
        private ReportHandlerRegistry $registry,
        private GoogleSheetsClient $googleClient,
    ) {}

    public function getConfigs(): JsonResponse
    {
        $configs = ReportConfig::all();

        return response()->json([
            'configs' => $configs,
        ]);
    }

    public function saveConfig(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'report_type' => 'nullable|string|max:255',
            'spreadsheet_id' => 'required|string|regex:/^[a-zA-Z0-9_-]+$/',
            'sheet_name' => 'nullable|string|max:255',
            'start_row' => 'nullable|integer|min:1',
            'filters' => 'nullable|array',
            'cron_expression' => 'required|string',
            'timezone' => 'required|string',
            'is_active' => 'nullable|boolean',
        ]);

        $config = ReportConfig::create([
            'id' => Str::uuid(),
            'name' => $validated['name'],
            'report_type' => $validated['report_type'] ?? 'friendly_summary',
            'spreadsheet_id' => $validated['spreadsheet_id'],
            'sheet_name' => $validated['sheet_name'] ?? 'Sheet1',
            'start_row' => $validated['start_row'] ?? 1,
            'filters' => $validated['filters'] ?? [],
            'cron_expression' => $validated['cron_expression'],
            'timezone' => $validated['timezone'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'config' => $config,
        ]);
    }

    public function updateConfig(Request $request, string $id): JsonResponse
    {
        $config = ReportConfig::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'report_type' => 'nullable|string|max:255',
            'spreadsheet_id' => 'required|string|regex:/^[a-zA-Z0-9_-]+$/',
            'sheet_name' => 'nullable|string|max:255',
            'start_row' => 'nullable|integer|min:1',
            'filters' => 'nullable|array',
            'cron_expression' => 'required|string',
            'timezone' => 'required|string',
            'is_active' => 'nullable|boolean',
        ]);

        $config->update([
            'name' => $validated['name'],
            'report_type' => $validated['report_type'] ?? 'friendly_summary',
            'spreadsheet_id' => $validated['spreadsheet_id'],
            'sheet_name' => $validated['sheet_name'] ?? 'Sheet1',
            'start_row' => $validated['start_row'] ?? 1,
            'filters' => $validated['filters'] ?? [],
            'cron_expression' => $validated['cron_expression'],
            'timezone' => $validated['timezone'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'config' => $config,
        ]);
    }

    public function deleteConfig(string $id): JsonResponse
    {
        $config = ReportConfig::findOrFail($id);
        $config->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    public function export(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'config_id' => 'required|uuid|exists:report_configs,id',
            'festival_id' => 'nullable|uuid',
            'limit' => 'nullable|integer|min:1',
        ]);

        $config = ReportConfig::findOrFail($validated['config_id']);
        $handler = $this->registry->get($config->report_type);

        if (! $handler) {
            return response()->json([
                'error' => "Обработчик для типа '{$config->report_type}' не найден",
            ], 400);
        }

        $filters = array_merge(
            $config->filters ?? [],
            array_filter([
                'festival_id' => $validated['festival_id'] ?? null,
                'limit' => $validated['limit'] ?? null,
            ])
        );

        try {
            $data = $handler->getData($filters);

            if (empty($data)) {
                return response()->json([
                    'error' => 'Нет данных для экспорта',
                ], 400);
            }

            $rows = [$handler->getHeaders()];
            foreach ($data as $index => $row) {
                $rows[] = $handler->formatRow($row, $index);
            }

            $clearRange = "{$config->sheet_name}!A{$config->start_row}:Z";
            $this->googleClient->clearRange($config->spreadsheet_id, $clearRange);

            $range = "{$config->sheet_name}!A{$config->start_row}";
            $this->googleClient->appendRows(
                $config->spreadsheet_id,
                $range,
                $rows
            );

            $rowCount = count($rows) - 1;
            $config->updateLastRun('success', $rowCount, "Выгружено {$rowCount} строк");

            return response()->json([
                'success' => true,
                'exportedRows' => $rowCount,
                'googleSheetUrl' => "https://docs.google.com/spreadsheets/d/{$config->spreadsheet_id}",
            ]);

        } catch (Exception $e) {
            \Log::error('Google Sheets export failed', [
                'config_id' => $config->id,
                'error' => $e->getMessage(),
            ]);

            $config->updateLastRun('failed', 0, $e->getMessage());

            return response()->json([
                'error' => 'Не удалось экспортировать в Google Sheets',
            ], 500);

        } catch (\Throwable $e) {
            \Log::error('Report export failed', [
                'config_id' => $config->id,
                'error' => $e->getMessage(),
            ]);

            $config->updateLastRun('failed', 0, $e->getMessage());

            return response()->json([
                'error' => 'Ошибка при экспорте отчёта',
            ], 500);
        }
    }
}
