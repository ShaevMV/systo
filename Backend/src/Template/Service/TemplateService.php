<?php

declare(strict_types=1);

namespace Tickets\Template\Service;

use Illuminate\Support\Str;
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
            $fileNames[] = preg_replace('/\..*$/', '', $file->getFilename()); // Удаляем всё, начиная с первой точки (т.е. все расширения целиком)
            // Для 'my.file.name.blade.php' вернёт 'my.file.name'
        }

        return new Collection($fileNames);
    }
}
