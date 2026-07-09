<?php

namespace App\Services\Attachment;

use Illuminate\Http\UploadedFile;
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
        'pdf',
        'doc',
        'docx',
        'xls',
        'xlsx',
        'txt',
        'zip',
        'rar'
    ];

    protected int $maxFileSize = 10240;

    protected int $maxFiles = 5;

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

            $extension = strtolower($file->getClientOriginalExtension());
            if (!in_array($extension, $this->allowedMimes)) {
                throw ValidationException::withMessages([
                    "attachments.{$index}" => ["The file must be a file of type: " . implode(', ', $this->allowedMimes) . "."]
                ]);
            }

            $mimeType = $file->getMimeType();
            if (!$this->isValidMimeType($mimeType)) {
                throw ValidationException::withMessages([
                    "attachments.{$index}" => ["Invalid file type."]
                ]);
            }
        }
    }

    protected function isValidMimeType(string $mimeType): bool
    {
        $allowedMimeTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'application/zip',
            'application/x-zip-compressed',
            'application/x-rar-compressed',
            'application/octet-stream',
        ];

        return in_array($mimeType, $allowedMimeTypes);
    }

    public function upload(UploadedFile $file, string $path = ''): array
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $storedName = Str::uuid() . '.' . $extension;

        $fullPath = $path ? $path . '/' . $storedName : $storedName;

        Storage::disk($this->disk)->putFileAs($path, $file, $storedName);

        return [
            'original_name' => $originalName,
            'stored_name' => $storedName,
            'path' => $fullPath,
            'mime_type' => $file->getMimeType(),
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
