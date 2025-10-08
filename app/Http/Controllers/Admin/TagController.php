<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TagController extends Controller
{
    /**
     * Display a listing of tags.
     */
    public function index(Request $request)
    {
        $query = Tag::query();

        // Apply search filter
        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }

        // Apply status filter
        if ($request->has('status') && $request->status !== '') {
            $status = $request->status === 'active';
            $query->where('is_active', $status);
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        if ($sortBy === 'popularity') {
            $query->popular();
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $tags = $query->withCount('news')->paginate(15);

        return $this->view('admin.tags.tags', compact('tags'));
    }

    /**
     * Store a newly created tag.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:tags,slug',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $tag = Tag::create($request->all());

        if ($request->expectsJson()) {
            return $this->successResponse('Tag created successfully.', $tag);
        }

        return redirect()
            ->route('admin.tags.index')
            ->with('success', 'Tag created successfully.');
    }

    /**
     * Update the specified tag.
     */
    public function update(Request $request, Tag $tag)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('tags', 'slug')->ignore($tag->id)],
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $tag->update($request->all());

        if ($request->expectsJson()) {
            return $this->successResponse('Tag updated successfully.', $tag);
        }

        return redirect()
            ->route('admin.tags.index')
            ->with('success', 'Tag updated successfully.');
    }

    /**
     * Remove the specified tag.
     */
    public function destroy(Request $request, Tag $tag)
    {
        // Check if tag has associated news articles
        if ($tag->news()->exists()) {
            if ($request->expectsJson()) {
                return $this->errorResponse('Cannot delete tag with associated news articles.');
            }
            return redirect()->back()
                ->withErrors(['error' => 'Cannot delete tag with associated news articles.']);
        }

        $tag->delete();

        if ($request->expectsJson()) {
            return $this->successResponse('Tag deleted successfully.');
        }

        return redirect()
            ->route('admin.tags.index')
            ->with('success', 'Tag deleted successfully.');
    }

    /**
     * Get tags as JSON (for AJAX requests).
     */
    public function getTags(Request $request)
    {
        $query = Tag::active();

        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }

        $tags = $query->orderBy('title')->get(['id', 'title', 'slug']);

        return $this->successResponse('Tags retrieved successfully.', $tags);
    }

    /**
     * Get popular tags.
     */
    public function getPopular(Request $request)
    {
        $limit = $request->get('limit', 10);
        
        $tags = Tag::active()
            ->popular()
            ->take($limit)
            ->get(['id', 'title', 'slug']);

        return $this->successResponse('Popular tags retrieved successfully.', $tags);
    }

    /**
     * Bulk actions for tags.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,activate,deactivate',
            'selected' => 'required|array|min:1',
            'selected.*' => 'exists:tags,id',
        ]);

        $tags = Tag::whereIn('id', $request->selected);

        switch ($request->action) {
            case 'delete':
                // Check if any tag has associated news
                $tagsWithNews = $tags->whereHas('news')->exists();

                if ($tagsWithNews) {
                    return $this->errorResponse('Cannot delete tags with associated news articles.');
                }

                $tags->delete();
                $message = 'Selected tags deleted successfully.';
                break;

            case 'activate':
                $tags->update(['is_active' => true]);
                $message = 'Selected tags activated successfully.';
                break;

            case 'deactivate':
                $tags->update(['is_active' => false]);
                $message = 'Selected tags deactivated successfully.';
                break;
        }

        if ($request->expectsJson()) {
            return $this->successResponse($message);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Search tags for autocomplete.
     */
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:1|max:255',
        ]);

        $tags = Tag::active()
            ->where('title', 'LIKE', '%' . $request->query . '%')
            ->orderBy('title')
            ->take(10)
            ->get(['id', 'title', 'slug']);

        return $this->successResponse('Search completed.', $tags);
    }

    /**
     * Create a new tag via AJAX.
     */
    public function quickCreate(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        // Check if tag already exists
        $existingTag = Tag::where('title', $request->title)->first();
        if ($existingTag) {
            return $this->successResponse('Tag already exists.', $existingTag);
        }

        $tag = Tag::create([
            'title' => $request->title,
            'is_active' => true,
        ]);

        return $this->successResponse('Tag created successfully.', $tag);
    }
}