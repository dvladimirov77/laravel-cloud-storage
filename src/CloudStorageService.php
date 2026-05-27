<?php

namespace App\Services\CloudStorage;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Абстрактный облачный файловый сервис.
 *
 * Не привязан к конкретному диску или структуре путей.
 * Диск внедряется через конструктор.
 *
 * Пример:
 *   $storage = new CloudStorageService('s3');
 *   $result = $storage->upload($file, 'users/avatars');
 */
class CloudStorageService implements CloudStorageInterface
{
    /**
     * @param string $disk Имя диска Laravel (s3, spaces, minio и т.д.)
     * @param callable|null $pathResolver Кастомная функция для построения пути (опционально)
     */
    public function __construct(
        private readonly string $disk = 's3',
        private $pathResolver = null
    ) {}

    // ----------------------------------------------------------------
    // Публичный API
    // ----------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function upload(UploadedFile|string $file, string $directory, array $options = []): FileUploadResult
    {
        $extension    = $this->resolveExtension($file);
        $filename     = $this->generateUniqueName($extension, $options['original_name'] ?? null);
        $fullPath     = rtrim($directory, '/') . '/' . $filename;
        $visibility   = $options['visibility'] ?? 'private';

        $this->store($fullPath, $file, $visibility);

        return new FileUploadResult(
            path:     $fullPath,
            url:      Storage::disk($this->disk)->url($fullPath),
            name:     $filename,
            size:     $this->resolveSize($file),
            mimeType: $this->resolveMimeType($file),
        );
    }

    /**
     * @inheritDoc
     */
    public function temporaryUrl(string $path, int $minutes = 60): ?string
    {
        if (! $this->exists($path)) {
            return null;
        }

        return Storage::disk($this->disk)->temporaryUrl($path, now()->addMinutes($minutes));
    }

    /**
     * @inheritDoc
     */
    public function delete(string $path): bool
    {
        if (! $this->exists($path)) {
            return false;
        }

        return Storage::disk($this->disk)->delete($path);
    }

    /**
     * @inheritDoc
     */
    public function exists(string $path): bool
    {
        return Storage::disk($this->disk)->exists($path);
    }

    /**
     * @inheritDoc
     */
    public function files(string $directory): array
    {
        return Storage::disk($this->disk)->allFiles($directory);
    }

    /**
     * @inheritDoc
     */
    public function info(string $path): ?FileInfo
    {
        if (! $this->exists($path)) {
            return null;
        }

        return new FileInfo(
            path:         $path,
            url:          Storage::disk($this->disk)->url($path),
            size:         Storage::disk($this->disk)->size($path),
            lastModified: Storage::disk($this->disk)->lastModified($path),
        );
    }

    /**
     * @inheritDoc
     */
    public function copy(string $from, string $to): bool
    {
        if (! $this->exists($from)) {
            return false;
        }

        return Storage::disk($this->disk)->copy($from, $to);
    }

    /**
     * @inheritDoc
     */
    public function move(string $from, string $to): bool
    {
        if (! $this->copy($from, $to)) {
            return false;
        }

        return $this->delete($from);
    }

    /**
     * @inheritDoc
     */
    public function deleteDirectory(string $directory): bool
    {
        return Storage::disk($this->disk)->deleteDirectory($directory);
    }

    // ----------------------------------------------------------------
    // Приватные методы
    // ----------------------------------------------------------------

    /**
     * Сохранить файл на диск.
     */
    private function store(string $path, UploadedFile|string $file, string $visibility): void
    {
        if (is_string($file)) {
            // Содержимое передано строкой
            Storage::disk($this->disk)->put($path, $file, [
                'visibility' => $visibility,
            ]);
        } else {
            // Объект UploadedFile
            $resource = fopen($file->getRealPath(), 'r+');

            Storage::disk($this->disk)->put($path, $resource, [
                'visibility' => $visibility,
            ]);

            if (is_resource($resource)) {
                fclose($resource);
            }
        }
    }

    /**
     * Сгенерировать уникальное имя файла.
     */
    private function generateUniqueName(string $extension, ?string $originalName): string
    {
        $uuid = Str::uuid();

        if (! $originalName) {
            return "{$uuid}.{$extension}";
        }

        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));

        return "{$safeName}_{$uuid}.{$extension}";
    }

    /**
     * Определить расширение файла.
     */
    private function resolveExtension(UploadedFile|string $file): string
    {
        if (is_string($file)) {
            return 'txt';
        }

        return $file->getClientOriginalExtension() ?: 'bin';
    }

    /**
     * Определить размер файла.
     */
    private function resolveSize(UploadedFile|string $file): int
    {
        if (is_string($file)) {
            return strlen($file);
        }

        return $file->getSize();
    }

    /**
     * Определить MIME-тип файла.
     */
    private function resolveMimeType(UploadedFile|string $file): string
    {
        if (is_string($file)) {
            return 'text/plain';
        }

        return $file->getMimeType() ?: 'application/octet-stream';
    }
}
