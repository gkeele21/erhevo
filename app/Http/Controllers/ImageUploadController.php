<?php

namespace App\Http\Controllers;

use App\Models\PostImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageUploadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,gif,webp|max:5120', // 5MB max
        ]);

        $file = $request->file('image');
        $path = $file->store('post-images', 'public');

        $image = PostImage::create([
            'user_id' => $request->user()->id,
            'path' => $path,
            'filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        return response()->json([
            'id' => $image->id,
            'url' => $image->url,
        ]);
    }

    public function destroy(PostImage $image): JsonResponse
    {
        if ($image->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $image->delete();

        return response()->json(['success' => true]);
    }
}
