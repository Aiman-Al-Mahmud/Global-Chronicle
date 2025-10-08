<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RssFeed;
use App\Models\News;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class RssController extends Controller
{
    /**
     * Display a listing of RSS feeds.
     */
    public function index(Request $request)
    {
        $query = RssFeed::query();

        // Apply filters
        if ($request->has('status') && $request->status !== '') {
            $status = $request->status === 'active';
            $query->where('is_active', $status);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('url', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        if ($sortBy === 'last_fetched') {
            $query->orderBy('last_fetched_at', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $rssFeeds = $query->paginate(15);

        return $this->view('admin.rss.rss', compact('rssFeeds'));
    }

    /**
     * Show the form for creating a new RSS feed.
     */
    public function create()
    {
        $categories = Category::active()->get(['id', 'name']);
        return $this->view('admin.rss.add_rss', compact('categories'));
    }

    /**
     * Store a newly created RSS feed in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'url' => 'required|url|unique:rss_feeds,url',
            'category_id' => 'nullable|exists:categories,id',
            'fetch_interval' => 'required|integer|min:1|max:1440', // Max 24 hours in minutes
            'max_items' => 'required|integer|min:1|max:100',
            'auto_publish' => 'boolean',
            'is_active' => 'boolean',
            'priority' => 'required|integer|min:1|max:10',
        ]);

        $rssFeed = RssFeed::create($request->all());

        if ($request->expectsJson()) {
            return $this->successResponse('RSS feed created successfully.', $rssFeed);
        }

        return redirect()
            ->route('admin.rss.index')
            ->with('success', 'RSS feed created successfully.');
    }

    /**
     * Display the specified RSS feed.
     */
    public function show(RssFeed $rssFeed)
    {
        $rssFeed->load('category');
        
        $stats = [
            'total_items_fetched' => $rssFeed->total_items_fetched,
            'last_fetched' => $rssFeed->last_fetched_at ? $rssFeed->last_fetched_at->diffForHumans() : 'Never',
            'success_rate' => $rssFeed->getSuccessRate(),
            'recent_errors' => $rssFeed->getRecentErrors(),
        ];

        return $this->view('admin.rss.rss_details', compact('rssFeed', 'stats'));
    }

    /**
     * Show the form for editing the specified RSS feed.
     */
    public function edit(RssFeed $rssFeed)
    {
        $categories = Category::active()->get(['id', 'name']);
        return $this->view('admin.rss.edit_rss', compact('rssFeed', 'categories'));
    }

    /**
     * Update the specified RSS feed in storage.
     */
    public function update(Request $request, RssFeed $rssFeed)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'url' => 'required|url|unique:rss_feeds,url,' . $rssFeed->id,
            'category_id' => 'nullable|exists:categories,id',
            'fetch_interval' => 'required|integer|min:1|max:1440',
            'max_items' => 'required|integer|min:1|max:100',
            'auto_publish' => 'boolean',
            'is_active' => 'boolean',
            'priority' => 'required|integer|min:1|max:10',
        ]);

        $rssFeed->update($request->all());

        if ($request->expectsJson()) {
            return $this->successResponse('RSS feed updated successfully.', $rssFeed);
        }

        return redirect()
            ->route('admin.rss.index')
            ->with('success', 'RSS feed updated successfully.');
    }

    /**
     * Remove the specified RSS feed from storage.
     */
    public function destroy(Request $request, RssFeed $rssFeed)
    {
        $rssFeed->delete();

        if ($request->expectsJson()) {
            return $this->successResponse('RSS feed deleted successfully.');
        }

        return redirect()
            ->route('admin.rss.index')
            ->with('success', 'RSS feed deleted successfully.');
    }

    /**
     * Fetch RSS feed manually.
     */
    public function fetch(Request $request, RssFeed $rssFeed)
    {
        try {
            $result = $this->fetchRssFeed($rssFeed);

            if ($request->expectsJson()) {
                return $this->successResponse('RSS feed fetched successfully.', $result);
            }

            return redirect()->back()->with('success', "RSS feed fetched successfully. {$result['items_imported']} new items imported.");
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return $this->errorResponse('Failed to fetch RSS feed: ' . $e->getMessage());
            }

            return redirect()->back()->withErrors(['error' => 'Failed to fetch RSS feed: ' . $e->getMessage()]);
        }
    }

    /**
     * Bulk fetch all active RSS feeds.
     */
    public function bulkFetch(Request $request)
    {
        $activeFeeds = RssFeed::active()->get();
        $results = [];

        foreach ($activeFeeds as $feed) {
            try {
                $result = $this->fetchRssFeed($feed);
                $results[] = [
                    'feed' => $feed->title,
                    'status' => 'success',
                    'items_imported' => $result['items_imported'],
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'feed' => $feed->title,
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        if ($request->expectsJson()) {
            return $this->successResponse('Bulk fetch completed.', $results);
        }

        $successCount = collect($results)->where('status', 'success')->count();
        $errorCount = collect($results)->where('status', 'error')->count();

        return redirect()->back()->with('success', "Bulk fetch completed. {$successCount} feeds successful, {$errorCount} errors.");
    }

    /**
     * Test RSS feed URL.
     */
    public function testFeed(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
        ]);

        try {
            $response = Http::timeout(30)->get($request->url);

            if (!$response->successful()) {
                return $this->errorResponse('Failed to fetch RSS feed. HTTP status: ' . $response->status());
            }

            $xml = simplexml_load_string($response->body());

            if ($xml === false) {
                return $this->errorResponse('Invalid RSS/XML format.');
            }

            // Try to parse as RSS or Atom
            $feedInfo = $this->parseRssFeedInfo($xml);

            return $this->successResponse('RSS feed is valid and accessible.', $feedInfo);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to test RSS feed: ' . $e->getMessage());
        }
    }

    /**
     * Bulk actions for RSS feeds.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete,fetch',
            'selected' => 'required|array|min:1',
            'selected.*' => 'exists:rss_feeds,id',
        ]);

        $rssFeeds = RssFeed::whereIn('id', $request->selected);

        switch ($request->action) {
            case 'activate':
                $rssFeeds->update(['is_active' => true]);
                $message = 'Selected RSS feeds activated successfully.';
                break;

            case 'deactivate':
                $rssFeeds->update(['is_active' => false]);
                $message = 'Selected RSS feeds deactivated successfully.';
                break;

            case 'fetch':
                $results = [];
                foreach ($rssFeeds->get() as $feed) {
                    try {
                        $this->fetchRssFeed($feed);
                        $results[] = ['feed' => $feed->title, 'status' => 'success'];
                    } catch (\Exception $e) {
                        $results[] = ['feed' => $feed->title, 'status' => 'error'];
                    }
                }
                $successCount = collect($results)->where('status', 'success')->count();
                $message = "Fetched {$successCount} RSS feeds successfully.";
                break;

            case 'delete':
                $rssFeeds->delete();
                $message = 'Selected RSS feeds deleted successfully.';
                break;
        }

        if ($request->expectsJson()) {
            return $this->successResponse($message);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Generate RSS feed for the website.
     */
    public function generateFeed(Request $request)
    {
        $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $query = News::published()->with(['category', 'user']);

        if ($request->has('category_id') && $request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        $news = $query->latest()
            ->take($request->get('limit', 20))
            ->get();

        $rssContent = $this->buildRssXml($news, $request->category_id);

        return response($rssContent)
            ->header('Content-Type', 'application/rss+xml; charset=utf-8');
    }

    /**
     * Fetch and parse RSS feed.
     */
    private function fetchRssFeed(RssFeed $rssFeed): array
    {
        $response = Http::timeout(30)->get($rssFeed->url);

        if (!$response->successful()) {
            $rssFeed->recordError('HTTP error: ' . $response->status());
            throw new \Exception('Failed to fetch RSS feed. HTTP status: ' . $response->status());
        }

        $xml = simplexml_load_string($response->body());

        if ($xml === false) {
            $rssFeed->recordError('Invalid XML format');
            throw new \Exception('Invalid RSS/XML format.');
        }

        $items = $this->parseRssItems($xml, $rssFeed);
        $itemsImported = 0;

        foreach ($items as $item) {
            // Check if item already exists
            $existingNews = News::where('source_url', $item['link'])->first();

            if (!$existingNews) {
                News::create([
                    'title' => $item['title'],
                    'content' => $item['description'],
                    'excerpt' => \Str::limit(strip_tags($item['description']), 200),
                    'slug' => \Str::slug($item['title']),
                    'category_id' => $rssFeed->category_id,
                    'user_id' => 1, // Default admin user
                    'status' => $rssFeed->auto_publish ? 'published' : 'draft',
                    'published_at' => $rssFeed->auto_publish ? now() : null,
                    'source_url' => $item['link'],
                    'source_name' => $rssFeed->title,
                ]);

                $itemsImported++;
            }

            if ($itemsImported >= $rssFeed->max_items) {
                break;
            }
        }

        $rssFeed->update([
            'last_fetched_at' => now(),
            'total_items_fetched' => $rssFeed->total_items_fetched + $itemsImported,
            'last_error' => null,
        ]);

        return [
            'items_imported' => $itemsImported,
            'total_items_found' => count($items),
        ];
    }

    /**
     * Parse RSS feed info.
     */
    private function parseRssFeedInfo($xml): array
    {
        if (isset($xml->channel)) {
            // RSS format
            return [
                'title' => (string) $xml->channel->title,
                'description' => (string) $xml->channel->description,
                'link' => (string) $xml->channel->link,
                'type' => 'RSS',
                'items_count' => count($xml->channel->item ?? []),
            ];
        } elseif (isset($xml->entry)) {
            // Atom format
            return [
                'title' => (string) $xml->title,
                'description' => (string) $xml->subtitle,
                'link' => (string) $xml->link['href'],
                'type' => 'Atom',
                'items_count' => count($xml->entry),
            ];
        }

        throw new \Exception('Unsupported feed format.');
    }

    /**
     * Parse RSS items.
     */
    private function parseRssItems($xml, RssFeed $rssFeed): array
    {
        $items = [];

        if (isset($xml->channel->item)) {
            // RSS format
            foreach ($xml->channel->item as $item) {
                $items[] = [
                    'title' => (string) $item->title,
                    'description' => (string) $item->description,
                    'link' => (string) $item->link,
                    'published_at' => isset($item->pubDate) ? Carbon::parse((string) $item->pubDate) : now(),
                ];
            }
        } elseif (isset($xml->entry)) {
            // Atom format
            foreach ($xml->entry as $entry) {
                $items[] = [
                    'title' => (string) $entry->title,
                    'description' => (string) ($entry->summary ?? $entry->content),
                    'link' => (string) $entry->link['href'],
                    'published_at' => isset($entry->published) ? Carbon::parse((string) $entry->published) : now(),
                ];
            }
        }

        return array_slice($items, 0, $rssFeed->max_items);
    }

    /**
     * Build RSS XML content.
     */
    private function buildRssXml($news, $categoryId = null): string
    {
        $siteName = config('app.name', 'News Website');
        $siteUrl = config('app.url', 'http://localhost');
        $description = 'Latest news from ' . $siteName;

        if ($categoryId) {
            $category = Category::find($categoryId);
            if ($category) {
                $description = 'Latest news from ' . $category->name . ' - ' . $siteName;
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0">' . "\n";
        $xml .= '<channel>' . "\n";
        $xml .= '<title>' . htmlspecialchars($siteName) . '</title>' . "\n";
        $xml .= '<description>' . htmlspecialchars($description) . '</description>' . "\n";
        $xml .= '<link>' . htmlspecialchars($siteUrl) . '</link>' . "\n";
        $xml .= '<lastBuildDate>' . now()->toRssString() . '</lastBuildDate>' . "\n";

        foreach ($news as $item) {
            $xml .= '<item>' . "\n";
            $xml .= '<title>' . htmlspecialchars($item->title) . '</title>' . "\n";
            $xml .= '<description>' . htmlspecialchars($item->excerpt) . '</description>' . "\n";
            $xml .= '<link>' . htmlspecialchars($siteUrl . '/news/' . $item->slug) . '</link>' . "\n";
            $xml .= '<pubDate>' . $item->published_at->toRssString() . '</pubDate>' . "\n";
            $xml .= '<guid>' . htmlspecialchars($siteUrl . '/news/' . $item->slug) . '</guid>' . "\n";
            $xml .= '</item>' . "\n";
        }

        $xml .= '</channel>' . "\n";
        $xml .= '</rss>';

        return $xml;
    }

    /**
     * Toggle RSS feed status.
     */
    public function toggleStatus(RssFeed $rssFeed)
    {
        $rssFeed->update([
            'is_active' => !$rssFeed->is_active,
        ]);

        $status = $rssFeed->is_active ? 'activated' : 'deactivated';
        
        return $this->successResponse("RSS feed {$status} successfully.", $rssFeed);
    }
}