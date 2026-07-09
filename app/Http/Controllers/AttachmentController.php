<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Routing\Controller;
use App\Services\Attachment\AttachmentService;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Storage;

class AttachmentController extends Controller
{
    protected AttachmentService $attachmentService;

    public function __construct(AttachmentService $attachmentService)
    {
        $this->attachmentService = $attachmentService;
    }

    public function show(int $id): StreamedResponse
    {
        $attachment = Attachment::findOrFail($id);

        if (!$this->attachmentService->exists($attachment->path)) {
            abort(404, 'File not found.');
        }

        $mimeType = $attachment->mime_type ?? Storage::disk('private')->mimeType($attachment->path);

        return Storage::disk('private')->response(
            $attachment->path,
            $attachment->original_name,
            [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . $attachment->original_name . '"'
            ]
        );
    }

    public function download(int $id): StreamedResponse
    {
        $attachment = Attachment::findOrFail($id);

        if (!$this->attachmentService->exists($attachment->path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('private')->download(
            $attachment->path,
            $attachment->original_name
        );
    }
}
