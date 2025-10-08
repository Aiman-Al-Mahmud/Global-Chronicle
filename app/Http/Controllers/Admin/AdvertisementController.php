<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Models\Media;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AdvertisementController extends Controller
{
    /**
     * Display a listing of advertisements.
     */
    public function index(Request $request)
    {
        $query = Advertisement::with('media');

        // Apply filters
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        if ($request->has('position') && $request->position) {
            $query->where('position', $request->position);
        }

        if ($request->has('status') && $request->status !== '') {
            $status = $request->status === 'active';
            $query->where('is_active', $status);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('client_name', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        if ($sortBy === 'clicks') {
            $query->orderBy('clicks', $sortOrder);
        } elseif ($sortBy === 'impressions') {
            $query->orderBy('impressions', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $advertisements = $query->paginate(15);

        return $this->view('admin.advertisments.advertisments', compact('advertisements'));
    }

    /**
     * Show the form for creating a new advertisement.
     */
    public function create()
    {
        $mediaFiles = Media::where('type', 'image')->latest()->get();

        return $this->view('admin.advertisments.add_advertisement', compact('mediaFiles'));
    }

    /**
     * Store a newly created advertisement in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'type' => 'required|in:banner,sidebar,popup,inline',
            'position' => 'required|string|max:50',
            'url' => 'nullable|url|max:500',
            'target' => 'required|in:_blank,_self',
            'media_id' => 'nullable|exists:media,id',
            'html_content' => 'nullable|string',
            'client_name' => 'nullable|string|max:255',
            'client_email' => 'nullable|email|max:255',
            'start_date' => 'nullable|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
            'daily_budget' => 'nullable|numeric|min:0|max:999999.99',
            'max_impressions' => 'nullable|integer|min:0',
            'max_clicks' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'priority' => 'required|integer|min:1|max:10',
        ]);

        $advertisement = Advertisement::create($request->all());

        if ($request->expectsJson()) {
            return $this->successResponse('Advertisement created successfully.', $advertisement->load('media'));
        }

        return redirect()
            ->route('admin.advertisements.index')
            ->with('success', 'Advertisement created successfully.');
    }

    /**
     * Display the specified advertisement.
     */
    public function show(Advertisement $advertisement)
    {
        $advertisement->load('media');
        
        // Get performance statistics
        $stats = [
            'total_impressions' => $advertisement->impressions,
            'total_clicks' => $advertisement->clicks,
            'click_through_rate' => $advertisement->impressions > 0 
                ? round(($advertisement->clicks / $advertisement->impressions) * 100, 2) 
                : 0,
            'daily_impressions' => $advertisement->getTodayImpressions(),
            'daily_clicks' => $advertisement->getTodayClicks(),
            'remaining_budget' => $advertisement->getRemainingBudget(),
            'remaining_impressions' => $advertisement->getRemainingImpressions(),
            'remaining_clicks' => $advertisement->getRemainingClicks(),
        ];

        return $this->view('admin.advertisments.advertisement_details', compact('advertisement', 'stats'));
    }

    /**
     * Show the form for editing the specified advertisement.
     */
    public function edit(Advertisement $advertisement)
    {
        $mediaFiles = Media::visible()->images()->get(['id', 'title', 'file_path']);
        return $this->view('admin.advertisments.edit_advertisement', compact('advertisement', 'mediaFiles'));
    }

    /**
     * Update the specified advertisement in storage.
     */
    public function update(Request $request, Advertisement $advertisement)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'type' => 'required|in:banner,sidebar,popup,inline',
            'position' => 'required|string|max:50',
            'url' => 'nullable|url|max:500',
            'target' => 'required|in:_blank,_self',
            'media_id' => 'nullable|exists:media,id',
            'html_content' => 'nullable|string',
            'client_name' => 'nullable|string|max:255',
            'client_email' => 'nullable|email|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'daily_budget' => 'nullable|numeric|min:0|max:999999.99',
            'max_impressions' => 'nullable|integer|min:0',
            'max_clicks' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'priority' => 'required|integer|min:1|max:10',
        ]);

        $advertisement->update($request->all());

        if ($request->expectsJson()) {
            return $this->successResponse('Advertisement updated successfully.', $advertisement->load('media'));
        }

        return redirect()
            ->route('admin.advertisements.index')
            ->with('success', 'Advertisement updated successfully.');
    }

    /**
     * Remove the specified advertisement from storage.
     */
    public function destroy(Request $request, Advertisement $advertisement)
    {
        $advertisement->delete();

        if ($request->expectsJson()) {
            return $this->successResponse('Advertisement deleted successfully.');
        }

        return redirect()
            ->route('admin.advertisements.index')
            ->with('success', 'Advertisement deleted successfully.');
    }

    /**
     * Quick create advertisement (AJAX).
     */
    public function quickCreate(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:banner,sidebar,popup,inline',
            'position' => 'required|string|max:50',
            'url' => 'nullable|url|max:500',
            'media_id' => 'nullable|exists:media,id',
        ]);

        $advertisement = Advertisement::create([
            'title' => $request->title,
            'type' => $request->type,
            'position' => $request->position,
            'url' => $request->url,
            'media_id' => $request->media_id,
            'target' => '_blank',
            'is_active' => true,
            'priority' => 5,
        ]);

        return $this->successResponse('Advertisement created successfully.', $advertisement->load('media'));
    }

    /**
     * Bulk actions for advertisements.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete,reset_stats',
            'selected' => 'required|array|min:1',
            'selected.*' => 'exists:advertisements,id',
        ]);

        $advertisements = Advertisement::whereIn('id', $request->selected);

        switch ($request->action) {
            case 'activate':
                $advertisements->update(['is_active' => true]);
                $message = 'Selected advertisements activated successfully.';
                break;

            case 'deactivate':
                $advertisements->update(['is_active' => false]);
                $message = 'Selected advertisements deactivated successfully.';
                break;

            case 'reset_stats':
                $advertisements->update([
                    'impressions' => 0,
                    'clicks' => 0,
                ]);
                $message = 'Statistics reset for selected advertisements.';
                break;

            case 'delete':
                $advertisements->delete();
                $message = 'Selected advertisements deleted successfully.';
                break;
        }

        if ($request->expectsJson()) {
            return $this->successResponse($message);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Track advertisement impression.
     */
    public function trackImpression(Request $request, Advertisement $advertisement)
    {
        $advertisement->recordImpression();

        return response()->json(['status' => 'success']);
    }

    /**
     * Track advertisement click.
     */
    public function trackClick(Request $request, Advertisement $advertisement)
    {
        $advertisement->recordClick();

        if ($advertisement->url) {
            return redirect($advertisement->url);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Get advertisements by position.
     */
    public function getByPosition(Request $request)
    {
        $request->validate([
            'position' => 'required|string|max:50',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        $advertisements = Advertisement::active()
            ->byPosition($request->position)
            ->with('media')
            ->inRandomOrder()
            ->take($request->get('limit', 5))
            ->get();

        return $this->successResponse('Advertisements retrieved successfully.', $advertisements);
    }

    /**
     * Get advertisement performance report.
     */
    public function getPerformanceReport(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'type' => 'nullable|in:banner,sidebar,popup,inline',
        ]);

        $query = Advertisement::query();

        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        if ($request->has('start_date') && $request->start_date) {
            $query->where('created_at', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->where('created_at', '<=', $request->end_date);
        }

        $advertisements = $query->get();

        $report = [
            'total_advertisements' => $advertisements->count(),
            'active_advertisements' => $advertisements->where('is_active', true)->count(),
            'total_impressions' => $advertisements->sum('impressions'),
            'total_clicks' => $advertisements->sum('clicks'),
            'average_ctr' => $advertisements->avg(function ($ad) {
                return $ad->impressions > 0 ? ($ad->clicks / $ad->impressions) * 100 : 0;
            }),
            'top_performing' => $advertisements->sortByDesc('clicks')->take(5)->values(),
            'by_type' => $advertisements->groupBy('type')->map(function ($ads) {
                return [
                    'count' => $ads->count(),
                    'impressions' => $ads->sum('impressions'),
                    'clicks' => $ads->sum('clicks'),
                ];
            }),
        ];

        return $this->successResponse('Performance report generated successfully.', $report);
    }

    /**
     * Toggle advertisement status.
     */
    public function toggleStatus(Advertisement $advertisement)
    {
        $advertisement->update([
            'is_active' => !$advertisement->is_active,
        ]);

        $status = $advertisement->is_active ? 'activated' : 'deactivated';
        
        return $this->successResponse("Advertisement {$status} successfully.", $advertisement);
    }

    /**
     * Clone an advertisement.
     */
    public function clone(Advertisement $advertisement)
    {
        $clonedAd = $advertisement->replicate();
        $clonedAd->title = $clonedAd->title . ' (Copy)';
        $clonedAd->is_active = false;
        $clonedAd->impressions = 0;
        $clonedAd->clicks = 0;
        $clonedAd->save();

        return $this->successResponse('Advertisement cloned successfully.', $clonedAd->load('media'));
    }
}