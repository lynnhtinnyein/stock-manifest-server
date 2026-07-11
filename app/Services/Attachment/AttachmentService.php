<?php

namespace App\Services\Attachment;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttachmentService
{
    protected string $disk = 'private';

    protected array $allowedMimes = [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'heic',
        'heif',
        'pdf',
        'doc',
        'docx',
        'xls',
        'xlsx',
        'txt',
        'zip',
        'rar'
    ];

    protected int $maxFileSize;

    protected int $maxFiles;

    public function __construct()
    {
        $this->maxFileSize = (int) config('attachments.max_file_size_kb', 10240);
        $this->maxFiles = (int) config('attachments.max_files', 5);
    }

    public function validateFiles(array $files): void
    {
        if (count($files) > $this->maxFiles) {
            throw ValidationException::withMessages([
                'attachments' => ["You can upload a maximum of {$this->maxFiles} files."]
            ]);
        }

        foreach ($files as $index => $file) {
            if (!$file instanceof UploadedFile) {
                throw ValidationException::withMessages([
                    "attachments.{$index}" => ["Invalid file upload."]
                ]);
            }

            if (!$file->isValid()) {
                throw ValidationException::withMessages([
                    "attachments.{$index}" => ["The file failed to upload."]
                ]);
            }

            $fileSizeKB = $file->getSize() / 1024;
            if ($fileSizeKB > $this->maxFileSize) {
                throw ValidationException::withMessages([
                    "attachments.{$index}" => ["The file must not be larger than {$this->maxFileSize}KB."]
                ]);
            }

            $mimeType = $file->getMimeType();
            $extension = $this->resolveExtension($file, $mimeType);

            if (!in_array($extension, $this->allowedMimes)) {
                Log::warning('Attachment rejected: extension not allowed', [
                    'original_name' => $file->getClientOriginalName(),
                    'extension' => $extension,
                    'mime' => $mimeType,
                    'size' => $file->getSize(),
                ]);

                throw ValidationException::withMessages([
                    "attachments.{$index}" => ["The file must be a file of type: " . implode(', ', $this->allowedMimes) . "."]
                ]);
            }

            if (!$this->isValidMimeType($mimeType)) {
                Log::warning('Attachment rejected: mime type not allowed', [
                    'original_name' => $file->getClientOriginalName(),
                    'extension' => $extension,
                    'mime' => $mimeType,
                    'size' => $file->getSize(),
                ]);

                throw ValidationException::withMessages([
                    "attachments.{$index}" => ["Invalid file type."]
                ]);
            }
        }
    }

    /**
     * Resolve a reliable extension for the file.
     * iOS/Safari and some upload flows omit the client extension entirely,
     * so fall back to deriving it from the detected MIME type.
     */
    protected function resolveExtension(UploadedFile $file, string $mimeType): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension !== '') {
            return $extension;
        }

        $mimeToExtension = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/heic' => 'heic',
            'image/heif' => 'heif',
            'image/heic-sequence' => 'heic',
            'image/heif-sequence' => 'heif',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'text/plain' => 'txt',
            'application/zip' => 'zip',
            'application/x-zip-compressed' => 'zip',
            'application/x-rar-compressed' => 'rar',
        ];

        return $mimeToExtension[$mimeType] ?? '';
    }

    protected function isValidMimeType(string $mimeType): bool
    {
        $allowedMimeTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/heic',
            'image/heif',
            'image/heic-sequence',
            'image/heif-sequence',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'application/zip',
            'application/x-zip-compressed',
            'application/x-rar-compressed',
            'application/octet-stream', // fallback — fileinfo often can't reliably ID HEIC
        ];

        return in_array($mimeType, $allowedMimeTypes);
    }

    public function upload(UploadedFile $file, string $path = ''): array
    {
        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $extension = $this->resolveExtension($file, $mimeType);
        $storedName = Str::uuid() . '.' . $extension;

        $fullPath = $path ? $path . '/' . $storedName : $storedName;

        Storage::disk($this->disk)->putFileAs($path, $file, $storedName);

        return [
            'original_name' => $originalName,
            'stored_name' => $storedName,
            'path' => $fullPath,
            'mime_type' => $mimeType,
            'size' => $file->getSize(),
        ];
    }

    public function delete(string $path): bool
    {
        if (Storage::disk($this->disk)->exists($path)) {
            return Storage::disk($this->disk)->delete($path);
        }

        return false;
    }

    public function getUrl(string $path): string
    {
        return Storage::disk($this->disk)->url($path);
    }

    public function exists(string $path): bool
    {
        return Storage::disk($this->disk)->exists($path);
    }

    public function getMaxFiles(): int
    {
        return $this->maxFiles;
    }

    public function getMaxFileSize(): int
    {
        return $this->maxFileSize;
    }

    public function getAllowedMimes(): array
    {
        return $this->allowedMimes;
    }
}
