<?php

namespace App\Services\CloudStorage;

/**
 * Value Object с информацией о файле.
 */
readonly class FileInfo
{
    public function __construct(
        public string $path,
        public string $url,
        public int $size,
        public int $lastModified,
    ) {}

    /**
     * Преобразовать в массив.
     */
    public function toArray(): array
    {
        return [
            'path'          => $this->path,
            'url'           => $this->url,
            'size'          => $this->size,
            'last_modified' => $this->lastModified,
        ];
    }
}
