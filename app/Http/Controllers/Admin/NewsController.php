<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class NewsController extends Controller
{
    /**
     * Display a listing of news articles.
     */
    public function index(Request $request)
    {
        $query = News::with(['author', 'category', 'featuredImage']);

        // Apply filters
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->has('category') && $request->category) {
            $query->where('category_id', $request->category);
        }

        if ($request->has('author') && $request->author) {
            $query->where('author_id', $request->author);
        }

        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $news = $query->paginate(15);

        // Get filter options
        $categories = Category::active()->ordered()->get();
        $authors = \App\Models\User::role('author')->orWhere(function($q) {
            $q->role('editor')->orWhere('role', 'admin');
        })->get();

        return $this->view('admin.news.news', compact(
            'news',
            'categories',
            'authors'
        ));
    }

    /**
     * Show the form for creating a new news article.
     */
    public function create()
    {
        $categories = Category::active()->ordered()->get();
        $tags = Tag::active()->get();

        return $this->view('admin.news.add-new', compact('categories', 'tags'));
    }

    /**
     * Store a newly created news article.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:news,slug',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'featured_image_id' => 'nullable|exists:media,id',
            'status' => 'required|in:draft,published,archived',
            'is_featured' => 'boolean',
            'allow_comments' => 'boolean',
            'language' => 'required|in:en,ar,fr,es',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string',
            'published_at' => 'nullable|date',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ]);

        $newsData = $request->except(['tags']);
        $newsData['author_id'] = auth()->id();

        // Set published_at if status is published and not provided
        if ($newsData['status'] === 'published' && !$newsData['published_at']) {
            $newsData['published_at'] = now();
        }

        // Convert meta_keywords string to array
        if ($request->meta_keywords) {
            $newsData['meta_keywords'] = array_map('trim', explode(',', $request->meta_keywords));
        }

        $news = News::create($newsData);

        // Attach tags
        if ($request->tags) {
            $news->tags()->attach($request->tags);
        }

        return redirect()
            ->route('admin.news.index')
            ->with('success', 'News article created successfully.');
    }

    /**
     * Display the specified news article.
     */
    public function show(News $news)
    {
        $news->load(['author', 'category', 'featuredImage', 'tags', 'comments']);
        
        return $this->view('admin.news.show', compact('news'));
    }

    /**
     * Show the form for editing the specified news article.
     */
    public function edit(News $news)
    {
        $categories = Category::active()->ordered()->get();
        $tags = Tag::active()->get();
        $selectedTags = $news->tags->pluck('id')->toArray();

        return $this->view('admin.news.edit', compact('news', 'categories', 'tags', 'selectedTags'));
    }

    /**
     * Update the specified news article.
     */
    public function update(Request $request, News $news)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('news', 'slug')->ignore($news->id)],
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'featured_image_id' => 'nullable|exists:media,id',
            'status' => 'required|in:draft,published,archived',
            'is_featured' => 'boolean',
            'allow_comments' => 'boolean',
            'language' => 'required|in:en,ar,fr,es',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string',
            'published_at' => 'nullable|date',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ]);

        $newsData = $request->except(['tags']);

        // Set published_at if status is published and not provided
        if ($newsData['status'] === 'published' && !$newsData['published_at']) {
            $newsData['published_at'] = now();
        }

        // Convert meta_keywords string to array
        if ($request->meta_keywords) {
            $newsData['meta_keywords'] = array_map('trim', explode(',', $request->meta_keywords));
        }

        $news->update($newsData);

        // Sync tags
        if ($request->has('tags')) {
            $news->tags()->sync($request->tags ?: []);
        }

        return redirect()
            ->route('admin.news.index')
            ->with('success', 'News article updated successfully.');
    }

    /**
     * Remove the specified news article.
     */
    public function destroy(News $news)
    {
        $news->delete();

        return redirect()
            ->route('admin.news.index')
            ->with('success', 'News article deleted successfully.');
    }

    /**
     * Bulk actions for news articles.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,publish,archive,draft',
            'selected' => 'required|array|min:1',
            'selected.*' => 'exists:news,id',
        ]);

        $news = News::whereIn('id', $request->selected);

        switch ($request->action) {
            case 'delete':
                $news->delete();
                $message = 'Selected articles deleted successfully.';
                break;
            case 'publish':
                $news->update(['status' => 'published', 'published_at' => now()]);
                $message = 'Selected articles published successfully.';
                break;
            case 'archive':
                $news->update(['status' => 'archived']);
                $message = 'Selected articles archived successfully.';
                break;
            case 'draft':
                $news->update(['status' => 'draft']);
                $message = 'Selected articles moved to draft successfully.';
                break;
        }

        if ($request->expectsJson()) {
            return $this->successResponse($message);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Display polling/surveys interface.
     */
    public function polling()
    {
        // Placeholder for polling functionality
        return $this->view('admin.news.polling');
    }

    /**
     * Display RSS sources management.
     */
    public function sources()
    {
        $rssFeeds = \App\Models\RssFeed::with('category')->paginate(15);
        
        return $this->view('admin.news.sources', compact('rssFeeds'));
    }
}