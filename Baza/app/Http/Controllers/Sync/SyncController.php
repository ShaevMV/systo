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
 * Экспорт: переиспользует ExportApplication, потом упаковывает .jsonl-файлы в zip
 * и стримит на скачивание (Q3-b).
 * Импорт: принимает uploaded zip с .jsonl, распаковывает во временную папку,
 * переиспользует ImportApplication (Q3-c).
 */
class SyncController extends Controller
{
    private const SYNC_TMP_DIR = 'baza-sync-tmp';

    public function __construct(
        private readonly ExportApplication $exportApplication,
        private readonly ImportApplication $importApplication,
    ) {
    }

    public function index(): View
    {
        return view('sync.index');
    }

    public function export(): BinaryFileResponse
    {
        $sessionDir = storage_path('app/' . self::SYNC_TMP_DIR . '/export-' . Carbon::now()->format('Ymd-His'));

        $stats = $this->exportApplication->export($sessionDir);

        $zipPath = $sessionDir . '.zip';
        $this->packZip($sessionDir, $zipPath);

        $this->removeDirectory($sessionDir);

        session()->flash('sync_export_stats', $stats);

        return response()
            ->download($zipPath, 'baza-export-' . Carbon::now()->format('Ymd-His') . '.zip')
            ->deleteFileAfterSend(true);
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

        $sessionDir = storage_path('app/' . self::SYNC_TMP_DIR . '/import-' . Carbon::now()->format('Ymd-His'));

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
