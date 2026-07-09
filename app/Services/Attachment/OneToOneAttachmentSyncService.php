<?php

namespace App\Services\Attachment;

use App\Services\Attachment\AttachmentService;
use App\Models\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

class OneToOneAttachmentSyncService
{
    public function __construct(
        private readonly AttachmentService $attachmentService
    ) {}

    /**
     * Sync a single document attachment on any Eloquent model.
     *
     * @param Model  $model        The attachable model (Stakeholder, Company, etc.)
     * @param array|null $documentData  ['id' => int|null, 'file' => UploadedFile|null]
     * @param string $documentType The document_type value stored in the attachments table
     * @param string $storagePath  Storage path prefix e.g. 'stakeholders/42'
     */
    public function syncDocument(
        Model $model,
        ?array $documentData,
        string $documentType,
        string $storagePath
    ): void {
        if ($documentData === null) {
            $this->deleteDocument($model, $documentType);
            return;
        }

        $hasId   = !empty($documentData['id']);
        $hasFile = isset($documentData['file']) && $documentData['file'] instanceof UploadedFile;

        if ($hasId && $hasFile) {
            $this->updateDocument($model, $documentData['id'], $documentData['file'], $documentType, $storagePath);
        } elseif (!$hasId && $hasFile) {
            $this->deleteDocument($model, $documentType);
            $this->createDocument($model, $documentData['file'], $documentType, $storagePath);
        }
        // hasId && !hasFile → keep existing, do nothing
    }

    /**
     * Convenience method to sync multiple documents in one call.
     *
     * @param Model  $model
     * @param array  $documents  [['data' => [...], 'type' => 'signature', 'path' => 'stakeholders/1'], ...]
     */
    public function syncDocuments(Model $model, array $documents): void
    {
        foreach ($documents as $document) {
            $this->syncDocument(
                $model,
                $document['data'],
                $document['type'],
                $document['path']
            );
        }
    }

    private function deleteDocument(Model $model, string $documentType): void
    {
        $attachment = $this->findAttachment($model, $documentType);

        if ($attachment) {
            $this->attachmentService->delete($attachment->path);
            $attachment->delete();
        }
    }

    private function updateDocument(
        Model $model,
        int $attachmentId,
        UploadedFile $file,
        string $documentType,
        string $storagePath
    ): void {
        $attachment = $this->findAttachment($model, $documentType, $attachmentId);

        if ($attachment) {
            $this->attachmentService->delete($attachment->path);
            $attachment->update($this->uploadFileData($file, $storagePath));
        }
    }

    private function createDocument(
        Model $model,
        UploadedFile $file,
        string $documentType,
        string $storagePath
    ): void {
        $model->attachments()->create([
            ...$this->uploadFileData($file, $storagePath),
            'uploaded_by'   => Auth::id(),
            'document_type' => $documentType,
        ]);
    }

    private function findAttachment(Model $model, string $documentType, ?int $attachmentId = null): ?Attachment
    {
        return Attachment::query()
            ->where('attachable_id', $model->getKey())
            ->where('attachable_type', $model->getMorphClass())
            ->where('document_type', $documentType)
            ->when($attachmentId, fn($q) => $q->where('id', $attachmentId))
            ->first();
    }

    private function uploadFileData(UploadedFile $file, string $storagePath): array
    {
        $uploaded = $this->attachmentService->upload($file, $storagePath);

        return [
            'original_name' => $uploaded['original_name'],
            'stored_name'   => $uploaded['stored_name'],
            'path'          => $uploaded['path'],
            'mime_type'     => $uploaded['mime_type'],
            'size'          => $uploaded['size'],
        ];
    }
}
