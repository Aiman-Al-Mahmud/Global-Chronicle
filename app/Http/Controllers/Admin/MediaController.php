<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    /**
     * Display a listing of media files.
     */
    public function index(Request $request)
    {
        $query = Media::query();

        // Apply filters
        if ($request->has('type') && $request->type) {
            $query->type($request->type);
        }

        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }

        if ($request->has('visibility') && $request->visibility !== '') {
            $visibility = $request->visibility === 'visible';
            $query->where('is_visible', $visibility);
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        if ($sortBy === 'size') {
            $query->orderBySize($sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $media = $query->paginate(24);

        return $this->view('admin.media.media', compact('media'));
    }

    /**
     * Upload new media files.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'files' => 'required|array|min:1|max:10',
            'files.*' => 'required|file|mimes:jpeg,png,jpg,gif,svg,webp,mp4,avi,mov,pdf,doc,docx|max:51200', // 50MB max
            'title.*' => 'nullable|string|max:255',
            'description.*' => 'nullable|string|max:500',
            'alt_text.*' => 'nullable|string|max:255',
        ]);

        $uploadedFiles = [];

        foreach ($request->file('files') as $index => $file) {
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $filename = Str::uuid() . '.' . $extension;
            
            // Determine media type based on file extension
            $type = $this->getMediaType($extension);
            
            // Store file
            $filePath = $file->storeAs('media', $filename, 'public');
            
            // Get file size
            $fileSize = $file->getSize();
            
            // Get dimensions for images
            $dimensions = null;
            if ($type === 'image' && in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $imagePath = $file->getRealPath();
                $imageInfo = getimagesize($imagePath);
                if ($imageInfo) {
                    $dimensions = [
                        'width' => $imageInfo[0],
                        'height' => $imageInfo[1],
                    ];
                }
            }

            $media = Media::create([
                'type' => $type,
                'title' => $request->input("title.{$index}") ?: pathinfo($originalName, PATHINFO_FILENAME),
                'description' => $request->input("description.{$index}"),
                'original_name' => $originalName,
                'file_name' => $filename,
                'file_path' => $filePath,
                'disk' => 'public',
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $fileSize,
                'dimensions' => $dimensions,
                'alt_text' => $request->input("alt_text.{$index}"),
                'is_visible' => true,
            ]);

            $uploadedFiles[] = $media;
        }

        if ($request->expectsJson()) {
            return $this->successResponse('Files uploaded successfully.', $uploadedFiles);
        }

        return redirect()
            ->route('admin.media.index')
            ->with('success', count($uploadedFiles) . ' file(s) uploaded successfully.');
    }

    /**
     * Update the specified media file.
     */
    public function update(Request $request, Media $media)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'alt_text' => 'nullable|string|max:255',
            'is_visible' => 'boolean',
        ]);

        $media->update($request->all());

        if ($request->expectsJson()) {
            return $this->successResponse('Media updated successfully.', $media);
        }

        return redirect()
            ->route('admin.media.index')
            ->with('success', 'Media updated successfully.');
    }

    /**
     * Remove the specified media file.
     */
    public function destroy(Request $request, Media $media)
    {
        // Check if media is being used
        $isUsed = $media->newsAsFeaturedImage()->exists() || $media->advertisements()->exists();

        if ($isUsed) {
            if ($request->expectsJson()) {
                return $this->errorResponse('Cannot delete media file that is currently in use.');
            }
            return redirect()->back()
                ->withErrors(['error' => 'Cannot delete media file that is currently in use.']);
        }

        // Delete the file from storage
        $media->deleteFile();

        // Delete the database record
        $media->delete();

        if ($request->expectsJson()) {
            return $this->successResponse('Media deleted successfully.');
        }

        return redirect()
            ->route('admin.media.index')
            ->with('success', 'Media deleted successfully.');
    }

    /**
     * Get media files for selection (AJAX).
     */
    public function getFiles(Request $request)
    {
        $query = Media::visible();

        if ($request->has('type') && $request->type) {
            $query->type($request->type);
        }

        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }

        $media = $query->orderBy('created_at', 'desc')
            ->take($request->get('limit', 20))
            ->get(['id', 'title', 'file_path', 'type', 'alt_text']);

        return $this->successResponse('Media files retrieved successfully.', $media);
    }

    /**
     * Bulk actions for media files.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,show,hide',
            'selected' => 'required|array|min:1',
            'selected.*' => 'exists:media,id',
        ]);

        $mediaFiles = Media::whereIn('id', $request->selected);

        switch ($request->action) {
            case 'delete':
                // Check if any media file is being used
                $mediaInUse = $mediaFiles->where(function ($query) {
                    $query->whereHas('newsAsFeaturedImage')
                          ->orWhereHas('advertisements');
                })->exists();

                if ($mediaInUse) {
                    return $this->errorResponse('Cannot delete media files that are currently in use.');
                }

                // Delete files from storage
                foreach ($mediaFiles->get() as $media) {
                    $media->deleteFile();
                }

                $mediaFiles->delete();
                $message = 'Selected media files deleted successfully.';
                break;

            case 'show':
                $mediaFiles->update(['is_visible' => true]);
                $message = 'Selected media files are now visible.';
                break;

            case 'hide':
                $mediaFiles->update(['is_visible' => false]);
                $message = 'Selected media files are now hidden.';
                break;
        }

        if ($request->expectsJson()) {
            return $this->successResponse($message);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Generate thumbnail for an image.
     */
    public function generateThumbnail(Request $request, Media $media)
    {
        if (!$media->is_image) {
            return $this->errorResponse('Thumbnails can only be generated for images.');
        }

        $width = $request->get('width', 300);
        $height = $request->get('height', 200);

        $thumbnailUrl = $media->getThumbnailUrl($width, $height);

        return $this->successResponse('Thumbnail generated successfully.', [
            'thumbnail_url' => $thumbnailUrl,
        ]);
    }

    /**
     * Get media storage statistics.
     */
    public function getStatistics()
    {
        $stats = [
            'total_files' => Media::count(),
            'total_size' => Media::sum('file_size'),
            'images_count' => Media::images()->count(),
            'videos_count' => Media::videos()->count(),
            'documents_count' => Media::type('document')->count(),
            'audio_count' => Media::type('audio')->count(),
        ];

        return $this->successResponse('Statistics retrieved successfully.', $stats);
    }

    /**
     * Determine media type based on file extension.
     */
    private function getMediaType(string $extension): string
    {
        $imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'bmp', 'tiff'];
        $videoTypes = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv'];
        $audioTypes = ['mp3', 'wav', 'ogg', 'aac', 'flac', 'm4a'];
        $documentTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'rtf'];

        $extension = strtolower($extension);

        if (in_array($extension, $imageTypes)) {
            return 'image';
        } elseif (in_array($extension, $videoTypes)) {
            return 'video';
        } elseif (in_array($extension, $audioTypes)) {
            return 'audio';
        } elseif (in_array($extension, $documentTypes)) {
            return 'document';
        }

        return 'document'; // Default fallback
    }
}