<?php

namespace App\Services\CloudStorage;

use Illuminate\Http\UploadedFile;

interface CloudStorageInterface
{
    /**
     * Загрузить файл.
     *
     * @param UploadedFile|string $file Объект файла или содержимое строкой
     * @param string $directory Целевая директория
     * @param array{visibility?: string, original_name?: string} $options
     * @return FileUploadResult
     */
    public function upload(UploadedFile|string $file, string $directory, array $options = []): FileUploadResult;

    /**
     * Получить временную подписанную ссылку.
     *
     * @param string $path Путь к файлу
     * @param int $minutes Время жизни ссылки в минутах
     * @return string|null
     */
    public function temporaryUrl(string $path, int $minutes = 60): ?string;

    /**
     * Удалить файл.
     *
     * @param string $path Путь к файлу
     * @return bool
     */
    public function delete(string $path): bool;

    /**
     * Проверить существование файла.
     *
     * @param string $path Путь к файлу
     * @return bool
     */
    public function exists(string $path): bool;

    /**
     * Получить список всех файлов в директории (рекурсивно).
     *
     * @param string $directory Путь к директории
     * @return array
     */
    public function files(string $directory): array;

    /**
     * Получить информацию о файле.
     *
     * @param string $path Путь к файлу
     * @return FileInfo|null
     */
    public function info(string $path): ?FileInfo;

    /**
     * Скопировать файл.
     *
     * @param string $from Исходный путь
     * @param string $to Целевой путь
     * @return bool
     */
    public function copy(string $from, string $to): bool;

    /**
     * Переместить файл.
     *
     * @param string $from Исходный путь
     * @param string $to Целевой путь
     * @return bool
     */
    public function move(string $from, string $to): bool;

    /**
     * Удалить директорию со всем содержимым.
     *
     * @param string $directory Путь к директории
     * @return bool
     */
    public function deleteDirectory(string $directory): bool;
}
