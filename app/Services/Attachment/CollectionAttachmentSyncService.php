<?php

namespace App\Services\Attachment;

use App\Services\Attachment\AttachmentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

class CollectionAttachmentSyncService
{
    public function __construct(
        private readonly AttachmentService $attachmentService
    ) {}

    /**
     * Sync attachments for a collection of child models (e.g. additionalSurveys, exemptionCertificates).
     *
     * @param Model  $parent        The parent model (e.g. JobRequestCertificate)
     * @param string $relation      Eloquent relation name on parent (e.g. 'additionalSurveys')
     * @param array  $collectionData  Keyed by child record ID: [id => ['id' => ?, 'file' => ?] | null]
     * @param string $documentType  Value stored in attachments.document_type
     * @param string $storagePath   Storage path prefix e.g. 'jr_certificates/1/additional_surveys'
     */
    public function sync(
        Model $parent,
        string $relation,
        array $collectionData,
        string $documentType,
        string $storagePath
    ): void {
        foreach ($collectionData as $key => $itemData) {
            $record = $parent->{$relation}()->where('id', $key)->first();

            if (!$record) {
                continue;
            }

            if ($itemData === null) {
                $this->deleteAttachment($record);
                continue;
            }

            if (!isset($itemData['id']) && !isset($itemData['file'])) {
                continue;
            }

            $hasFile = isset($itemData['file']) && $itemData['file'] instanceof UploadedFile;

            if ($hasFile) {
                $this->deleteAttachment($record);
                $this->createAttachment($record, $itemData['file'], $documentType, $storagePath);
            }
            // hasId && !hasFile → keep existing, do nothing
        }
    }

    private function deleteAttachment(Model $record): void
    {
        if ($record->attachment) {
            $this->attachmentService->delete($record->attachment->path);
            $record->attachment->delete();
        }
    }

    private function createAttachment(
        Model $record,
        UploadedFile $file,
        string $documentType,
        string $storagePath
    ): void {
        $uploaded = $this->attachmentService->upload($file, $storagePath);

        $record->attachment()->create([
            'uploaded_by'   => Auth::id() ?? 1,
            'original_name' => $uploaded['original_name'],
            'stored_name'   => $uploaded['stored_name'],
            'path'          => $uploaded['path'],
            'mime_type'     => $uploaded['mime_type'],
            'size'          => $uploaded['size'],
            'document_type' => $documentType,
        ]);
    }
}
