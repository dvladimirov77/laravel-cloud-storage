<?php

namespace App\Services\CloudStorage;

use Illuminate\Support\ServiceProvider;

class CloudStorageServiceProvider extends ServiceProvider
{
    /**
     * Регистрация сервиса в контейнере.
     */
    public function register(): void
    {
        // Объединяем конфиг с существующим
        $this->mergeConfigFrom(
            __DIR__ . '/../config/cloud-storage.php',
            'cloud-storage'
        );

        // Привязываем интерфейс к реализации
        $this->app->bind(CloudStorageInterface::class, function ($app) {
            return new CloudStorageService(
                disk: config('cloud-storage.default_disk', 's3'),
            );
        });
    }

    /**
     * Публикация ресурсов после загрузки приложения.
     */
    public function boot(): void
    {
        // Публикация конфига
        $this->publishes([
            __DIR__ . '/../config/cloud-storage.php' => config_path('cloud-storage.php'),
        ], 'cloud-storage-config');
    }
}
