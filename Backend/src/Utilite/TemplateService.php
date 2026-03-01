<?php

declare(strict_types=1);

namespace Tickets\Utility;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class TemplateService
{
    public function getList(string $path): Collection
    {
// Полный путь к папке views
        $path = resource_path($path);
        // Получаем все файлы (только в этой папке, без подпапок)
        $files = File::files($path);

        // Получаем только имена файлов
        $fileNames = [];
        foreach ($files as $file) {
            $fileNames[] = $file->getFilename(); // только имя с расширением
            // или $file->getBasename() - тоже имя файла
            // или $file->getPathname() - полный путь
        }

        return new Collection($fileNames);
    }
}
