<?php

namespace App\Http\Requests\Attachment;

use App\Services\Attachment\AttachmentService;
use Illuminate\Foundation\Http\FormRequest;

class SyncMultipleAttachmentsBulkRequest extends FormRequest
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
            'data' => ['required', 'array', 'min:1'],
            'data.*.id' => ['required', 'integer', 'exists:job_request_certificates,id'],
            'data.*.attachment_ids' => ['nullable', 'array'],
            'data.*.attachment_ids.*' => ['integer', 'exists:attachments,id'],
            'data.*.attachments' => ['nullable', 'array'],
            'data.*.attachments.*' => ['file', "mimes:{$allowedMimes}", "max:{$maxFileSize}"],
        ];
    }

    public function messages(): array
    {
        $allowedTypes = implode(', ', $this->attachmentService->getAllowedMimes());
        $maxSizeMB = $this->attachmentService->getMaxFileSize() / 1024;

        return [
            'data.required' => 'Data array is required.',
            'data.array' => 'data must be an array.',
            'data.min' => 'At least one certificate is required.',
            'data.*.id.required' => 'Certificate ID is required.',
            'data.*.id.exists' => 'One or more certificate IDs do not exist.',
            'data.*.attachment_ids.array' => 'Attachment IDs must be an array.',
            'data.*.attachment_ids.*.exists' => 'One or more attachment IDs do not exist.',
            'data.*.attachments.array' => 'Attachments must be an array.',
            'data.*.attachments.*.file' => 'Each attachment must be a file.',
            'data.*.attachments.*.mimes' => "Each attachment must be a file of type: {$allowedTypes}.",
            'data.*.attachments.*.max' => "Each attachment must not be larger than {$maxSizeMB}MB.",
        ];
    }
}
