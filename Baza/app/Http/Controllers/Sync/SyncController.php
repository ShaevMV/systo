<?php

declare(strict_types=1);

namespace App\Http\Controllers\Sync;

use App\Http\Controllers\Controller;
use Baza\Sync\Applications\Export\ExportApplication;
use Baza\Sync\Applications\Import\ImportApplication;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;
use ZipArchive;

/**
 * UI для админов: продублированный функционал команд baza:export / baza:import.
 *
 * ЗАЧЕМ это нужно (бизнес-контекст): на месте фестиваля часто НЕТ устойчивого интернета,
 * поэтому ноутбук-сканер на КПП работает офлайн со своей копией Baza. Перенос данных
 * сервер ↔ ноутбук идёт вручную ZIP-файлом («sneakernet» — флешка/локальная сеть, не сеть).
 * Перед фестивалём выгружаем билеты на ноутбук, после — забираем отметки входа обратно.
 * Подробно — .claude/docs/BAZA.md §7.
 *
 * Экспорт: переиспользует ExportApplication, потом упаковывает .jsonl-файлы в zip
 * и стримит на скачивание (Q3-b).
 * Импорт: принимает uploaded zip с .jsonl, распаковывает во временную папку,
 * переиспользует ImportApplication (Q3-c).
 */
class SyncController extends Controller
{
    public function __construct(
        private readonly ExportApplication $exportApplication,
        private readonly ImportApplication $importApplication,
    ) {
    }

    /**
     * Базовая папка временных файлов sync. Используем системный /tmp вместо
     * storage/app — на проде у php-fpm часто нет прав на запись в storage,
     * а в /tmp права гарантированы. Persistence нам не нужна — мы всегда
     * чистим в finally.
     */
    private function syncTmpBase(): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'baza-sync-tmp';
    }

    public function index(): View
    {
        return view('sync.index');
    }

    public function export(): BinaryFileResponse
    {
        // Уникальный идентификатор сессии: timestamp с микросекундами + 8 hex случайных
        // символов. Защита от коллизий, если экспорт стартует у двух админов в одну секунду.
        $sessionId  = Carbon::now()->format('Ymd-His-u') . '-' . bin2hex(random_bytes(4));
        $sessionDir = $this->syncTmpBase() . DIRECTORY_SEPARATOR . 'export-' . $sessionId;
        $zipPath    = $sessionDir . '.zip';

        try {
            $stats = $this->exportApplication->export($sessionDir);
            $this->packZip($sessionDir, $zipPath);

            session()->flash('sync_export_stats', $stats);

            return response()
                ->download($zipPath, 'baza-export-' . $sessionId . '.zip')
                ->deleteFileAfterSend(true);
        } catch (Throwable $e) {
            // При ошибке zip мог успеть частично создаться — удаляем явно,
            // т.к. deleteFileAfterSend не сработает (response не возвращён).
            if (is_file($zipPath)) {
                @unlink($zipPath);
            }
            throw $e;
        } finally {
            // Папка-источник нужна только для упаковки в zip — удаляем всегда:
            // и при успехе (zip уже собран), и при ошибке.
            $this->removeDirectory($sessionDir);
        }
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'archive' => ['required', 'file', 'mimetypes:application/zip,application/x-zip-compressed,application/octet-stream', 'max:512000'],
        ], [
            'archive.required' => 'Файл архива обязателен',
            'archive.mimetypes' => 'Допустим только ZIP-архив',
            'archive.max' => 'Максимальный размер архива — 500 МБ',
        ]);

        $sessionId  = Carbon::now()->format('Ymd-His-u') . '-' . bin2hex(random_bytes(4));
        $sessionDir = $this->syncTmpBase() . DIRECTORY_SEPARATOR . 'import-' . $sessionId;

        try {
            if (!mkdir($sessionDir, 0775, true) && !is_dir($sessionDir)) {
                throw new RuntimeException("Не удалось создать временную директорию '{$sessionDir}'");
            }

            $this->unpackZip($request->file('archive')->getRealPath(), $sessionDir);

            $stats = $this->importApplication->import($sessionDir);

            session()->flash('sync_import_stats', $stats);
        } catch (Throwable $e) {
            session()->flash('sync_error', 'Ошибка импорта: ' . $e->getMessage());
        } finally {
            $this->removeDirectory($sessionDir);
        }

        return redirect()->route('sync.index');
    }

    private function packZip(string $sourceDir, string $zipPath): void
    {
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Не удалось создать архив '{$zipPath}'");
        }

        foreach (glob($sourceDir . DIRECTORY_SEPARATOR . '*.jsonl') ?: [] as $file) {
            $zip->addFile($file, basename($file));
        }

        $zip->close();
    }

    private function unpackZip(string $zipPath, string $targetDir): void
    {
        $zip = new ZipArchive();

        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Не удалось открыть ZIP-архив');
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);
            if ($entryName === false) {
                continue;
            }

            $basename = basename($entryName);
            if (!str_ends_with($basename, '.jsonl')) {
                continue;
            }

            $stream = $zip->getStream($entryName);
            if ($stream === false) {
                continue;
            }

            file_put_contents($targetDir . DIRECTORY_SEPARATOR . $basename, $stream);
            fclose($stream);
        }

        $zip->close();
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        @rmdir($dir);
    }
}
