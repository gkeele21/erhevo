<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Document attachments for posts — currently PDFs (a handout, a study guide,
 * a marked-up chapter). Images go through ImageUploadController and land in
 * cover_image; this is for files a browser can't render as a picture.
 */
class AttachmentUploadController extends Controller
{
    /** Server-side cap; php.ini's upload_max_filesize / post_max_size also apply. */
    private const MAX_KB = 20480; // 20MB

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            // mimetypes (not mimes) so the check is on the sniffed content type
            // rather than a renameable extension.
            'file' => 'required|file|mimetypes:application/pdf|max:' . self::MAX_KB,
        ], [
            'file.mimetypes' => 'That file is not a PDF.',
            'file.max' => 'That PDF is too large (20MB max).',
        ]);

        $file = $request->file('file');
        // Per-user folder so deletes can be scoped to the owner.
        $path = $file->store('post-attachments/' . $request->user()->id, 'public');

        return response()->json([
            'url' => Storage::disk('public')->url($path),
            'path' => $path,
            'name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'path' => 'required|string',
        ]);

        $prefix = 'post-attachments/' . $request->user()->id . '/';
        if (! str_starts_with($validated['path'], $prefix)) {
            abort(403);
        }

        Storage::disk('public')->delete($validated['path']);

        return response()->json(['success' => true]);
    }
}
