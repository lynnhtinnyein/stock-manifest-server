<?php

namespace App\Http\Requests\Attachment;

use App\Services\Attachment\AttachmentService;
use Illuminate\Foundation\Http\FormRequest;

class SyncMultipleAttachmentsRequest extends FormRequest
{
    public function __construct(protected AttachmentService $attachmentService)
    {
        parent::__construct();
    }

    public function rules(): array
    {
        $allowedMimes = implode(',', $this->attachmentService->getAllowedMimes());
        $maxFileSize = $this->attachmentService->getMaxFileSize();

        return [
            'attachment_ids' => ['nullable', 'array'],
            'attachment_ids.*' => ['integer', 'exists:attachments,id'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', "mimes:{$allowedMimes}", "max:{$maxFileSize}"],
        ];
    }

    public function messages(): array
    {
        $allowedTypes = implode(', ', $this->attachmentService->getAllowedMimes());
        $maxSizeMB = $this->attachmentService->getMaxFileSize() / 1024;

        return [
            'attachment_ids.array' => 'Attachment IDs must be an array.',
            'attachment_ids.*.exists' => 'One or more attachment IDs do not exist.',
            'attachments.array' => 'Attachments must be an array.',
            'attachments.*.file' => 'Each attachment must be a file.',
            'attachments.*.mimes' => "Each attachment must be a file of type: {$allowedTypes}.",
            'attachments.*.max' => "Each attachment must not be larger than {$maxSizeMB}MB.",
        ];
    }
}
