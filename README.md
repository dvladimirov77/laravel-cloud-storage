# Laravel Cloud Storage Service

Абстрактный слой для работы с облачными хранилищами (S3, DO Spaces, Minio, GCS).

## Возможности

- Единый интерфейс для любого S3-совместимого хранилища
- Генерация уникальных имён файлов (UUID + безопасное оригинальное имя)
- Временные подписанные ссылки для приватных файлов
- Полный CRUD: загрузка, удаление, копирование, перемещение, списки
- Строгая типизация и value objects вместо массивов
- 100% покрытие тестами с моками Storage фасада

## Установка

1. Скопируйте папку `src/` в свой проект
2. Зарегистрируйте провайдер в `config/app.php`:
```php
App\Services\CloudStorage\CloudStorageServiceProvider::class,
```
3. Опубликуйте конфиг:
```bash
php artisan vendor:publish --tag=cloud-storage-config
	```
4. Укажите нужный диск в .env:
CLOUD_STORAGE_DISK=s3
	
## Использование
```php
use App\Services\CloudStorage\CloudStorageService;

$storage = new CloudStorageService('s3');

// Загрузка
$result = $storage->upload($request->file('avatar'), 'users/avatars');

// Временная ссылка (60 минут)
$url = $storage->temporaryUrl($result->path, 60);

// Удаление
$storage->delete($result->path);
	```
## Тестирование
```bash
php artisan test --filter=CloudStorageServiceTest
```
