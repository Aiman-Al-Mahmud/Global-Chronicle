<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\News;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    /**
     * Toggle like/dislike for a news article.
     */
    public function toggle(Request $request, News $news)
    {
        $request->validate([
            'type' => 'required|in:like,dislike',
        ]);

        $userId = auth()->id();
        $ip = $request->ip();
        $type = $request->type;

        // Create or update like
        $like = Like::createOrUpdate($news->id, $type, $userId, $ip);

        // Get updated counts
        $likesCount = $news->getLikesCount();
        $dislikesCount = $news->getDislikesCount();

        if ($request->expectsJson()) {
            return $this->successResponse('Action completed successfully.', [
                'likes_count' => $likesCount,
                'dislikes_count' => $dislikesCount,
                'user_action' => $like->wasRecentlyCreated ? $type : null,
            ]);
        }

        return redirect()->back();
    }

    /**
     * Get likes/dislikes count for a news article.
     */
    public function getCounts(News $news)
    {
        return $this->successResponse('Counts retrieved successfully.', [
            'likes_count' => $news->getLikesCount(),
            'dislikes_count' => $news->getDislikesCount(),
        ]);
    }
}