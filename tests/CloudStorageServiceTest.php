<?php

namespace Tests\Unit;

use App\Services\CloudStorage\CloudStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\TestCase;

class CloudStorageServiceTest extends TestCase
{
    private CloudStorageService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Подменяем реальное хранилище на фейковое
        Storage::fake('s3');

        $this->service = new CloudStorageService('s3');
    }

    /** @test */
    public function it_uploads_a_file(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg');

        $result = $this->service->upload($file, 'avatars');

        Storage::disk('s3')->assertExists($result->path);
        $this->assertStringStartsWith('avatars/', $result->path);
        $this->assertEquals('image/jpeg', $result->mimeType);
    }

    /** @test */
    public function it_uploads_string_content(): void
    {
        $result = $this->service->upload('Hello, World!', 'texts');

        Storage::disk('s3')->assertExists($result->path);
        $this->assertEquals(13, $result->size);
        $this->assertEquals('text/plain', $result->mimeType);
    }

    /** @test */
    public function it_generates_unique_filenames(): void
    {
        $file = UploadedFile::fake()->create('report.pdf', 100);

        $result1 = $this->service->upload($file, 'docs');
        $result2 = $this->service->upload($file, 'docs');

        $this->assertNotEquals($result1->path, $result2->path);
    }

    /** @test */
    public function it_preserves_original_name_in_filename(): void
    {
        $file = UploadedFile::fake()->create('contract.pdf', 100);

        $result = $this->service->upload($file, 'docs', [
            'original_name' => 'contract.pdf',
        ]);

        $this->assertStringContainsString('contract_', $result->name);
    }

    /** @test */
    public function it_deletes_a_file(): void
    {
        $file = UploadedFile::fake()->image('test.jpg');
        $result = $this->service->upload($file, 'temp');

        $deleted = $this->service->delete($result->path);

        $this->assertTrue($deleted);
        Storage::disk('s3')->assertMissing($result->path);
    }

    /** @test */
    public function delete_returns_false_for_missing_file(): void
    {
        $deleted = $this->service->delete('nonexistent/file.txt');

        $this->assertFalse($deleted);
    }

    /** @test */
    public function it_checks_file_existence(): void
    {
        $file = UploadedFile::fake()->image('test.jpg');
        $result = $this->service->upload($file, 'temp');

        $this->assertTrue($this->service->exists($result->path));
        $this->assertFalse($this->service->exists('nonexistent/file.txt'));
    }

    /** @test */
    public function it_generates_temporary_url(): void
    {
        $file = UploadedFile::fake()->image('test.jpg');
        $result = $this->service->upload($file, 'private');

        $url = $this->service->temporaryUrl($result->path, 30);

        $this->assertNotNull($url);
    }

    /** @test */
    public function it_returns_null_for_missing_file_temporary_url(): void
    {
        $url = $this->service->temporaryUrl('missing/file.pdf');

        $this->assertNull($url);
    }

    /** @test */
    public function it_copies_a_file(): void
    {
        $file = UploadedFile::fake()->image('original.jpg');
        $source = $this->service->upload($file, 'source');

        $copied = $this->service->copy($source->path, 'destination/copy.jpg');

        $this->assertTrue($copied);
        Storage::disk('s3')->assertExists('destination/copy.jpg');
        Storage::disk('s3')->assertExists($source->path); // оригинал остался
    }

    /** @test */
    public function copy_returns_false_for_missing_source(): void
    {
        $copied = $this->service->copy('missing/file.txt', 'destination/file.txt');

        $this->assertFalse($copied);
    }

    /** @test */
    public function it_moves_a_file(): void
    {
        $file = UploadedFile::fake()->image('movable.jpg');
        $source = $this->service->upload($file, 'source');

        $moved = $this->service->move($source->path, 'destination/moved.jpg');

        $this->assertTrue($moved);
        Storage::disk('s3')->assertMissing($source->path);
        Storage::disk('s3')->assertExists('destination/moved.jpg');
    }

    /** @test */
    public function move_returns_false_for_missing_source(): void
    {
        $moved = $this->service->move('missing/file.txt', 'destination/file.txt');

        $this->assertFalse($moved);
    }

    /** @test */
    public function it_lists_files_in_directory(): void
    {
        $file1 = UploadedFile::fake()->image('1.jpg');
        $file2 = UploadedFile::fake()->image('2.jpg');
        $this->service->upload($file1, 'gallery');
        $this->service->upload($file2, 'gallery');

        $files = $this->service->files('gallery');

        $this->assertCount(2, $files);
    }

    /** @test */
    public function it_gets_file_info(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 1024);
        $result = $this->service->upload($file, 'docs');

        $info = $this->service->info($result->path);

        $this->assertNotNull($info);
        $this->assertEquals(1024, $info->size);
        $this->assertEquals($result->path, $info->path);
    }

    /** @test */
    public function info_returns_null_for_missing_file(): void
    {
        $info = $this->service->info('missing/file.pdf');

        $this->assertNull($info);
    }

    /** @test */
    public function it_deletes_directory_with_all_files(): void
    {
        $file1 = UploadedFile::fake()->image('1.jpg');
        $file2 = UploadedFile::fake()->image('2.jpg');
        $this->service->upload($file1, 'bulk');
        $this->service->upload($file2, 'bulk');

        $this->service->deleteDirectory('bulk');

        $this->assertEmpty(Storage::disk('s3')->allFiles('bulk'));
    }
}
