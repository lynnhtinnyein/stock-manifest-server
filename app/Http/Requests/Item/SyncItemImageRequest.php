<?php

namespace App\Http\Requests\Item;

use App\Services\Attachment\AttachmentService;
use Illuminate\Foundation\Http\FormRequest;

class SyncItemImageRequest extends FormRequest
{
    public function __construct(protected AttachmentService $attachmentService)
    {
        parent::__construct();
    }

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('items.update') ?? false;
    }

    public function rules(): array
    {
        $allowedMimes = implode(',', $this->attachmentService->getAllowedMimes());
        $maxFileSize = $this->attachmentService->getMaxFileSize();

        return [
            'image' => ['nullable'],
            'image.id' => ['nullable', 'integer', 'exists:attachments,id'],
            'image.file' => ['nullable', 'file', "mimes:{$allowedMimes}", "max:{$maxFileSize}"],
        ];
    }

    public function messages(): array
    {
        $allowedTypes = implode(', ', $this->attachmentService->getAllowedMimes());
        $maxSizeMB = $this->attachmentService->getMaxFileSize() / 1024;

        return [
            'image.id.exists' => 'The image document does not exist.',
            'image.file.mimes' => "The image must be a file of type: {$allowedTypes}.",
            'image.file.max' => "The image must not be larger than {$maxSizeMB}MB.",
        ];
    }

    public function withValidator(mixed $validator)
    {
        $validator->after(function ($validator) {
            $this->validateImageField($validator);
        });
    }

    private function validateImageField(mixed $validator): void
    {
        $imageData = $this->input('image');

        if ($imageData === null || $imageData === 'null' || $imageData === '') {
            return;
        }

        if (!is_array($imageData)) {
            return;
        }

        $hasId = !empty($imageData['id']) && $imageData['id'] !== null;
        $hasFile = $this->hasFile('image.file');

        if (!$hasId && !$hasFile) {
            $validator->errors()->add('image', 'You must provide either an existing image ID or upload a new file.');
        }
    }

    protected function prepareForValidation(): void
    {
        $this->convertNullStrings('image');
    }

    private function convertNullStrings(string $field): void
    {
        $value = $this->input($field);

        if ($value === 'null' || $value === null || $value === '') {
            $this->merge([$field => null]);
        }
    }
}
