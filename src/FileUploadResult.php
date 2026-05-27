<?php

namespace App\Services\CloudStorage;

/**
 * Value Object с результатом загрузки файла.
 */
readonly class FileUploadResult
{
    public function __construct(
        public string $path,
        public string $url,
        public string $name,
        public int $size,
        public string $mimeType,
    ) {}

    /**
     * Преобразовать в массив.
     */
    public function toArray(): array
    {
        return [
            'path'      => $this->path,
            'url'       => $this->url,
            'name'      => $this->name,
            'size'      => $this->size,
            'mime_type' => $this->mimeType,
        ];
    }
}
