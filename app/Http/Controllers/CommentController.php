<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    /**
     * Store a new comment.
     */
    public function store(Request $request, News $news)
    {
        // Check if comments are allowed for this article
        if (!$news->allowsComments()) {
            return $this->errorResponse('Comments are not allowed for this article.', null, 403);
        }

        $rules = [
            'content' => 'required|string|min:3|max:1000',
            'parent_id' => 'nullable|exists:comments,id',
        ];

        // If user is not authenticated, require guest info
        if (!Auth::check()) {
            $rules['guest_name'] = 'required|string|max:100';
            $rules['guest_email'] = 'required|email|max:255';
        }

        $request->validate($rules);

        // Check if parent comment belongs to the same news article
        if ($request->parent_id) {
            $parentComment = Comment::find($request->parent_id);
            if ($parentComment->news_id !== $news->id) {
                return $this->errorResponse('Invalid parent comment.', null, 400);
            }
        }

        $commentData = [
            'news_id' => $news->id,
            'content' => $request->content,
            'parent_id' => $request->parent_id,
            'ip_address' => $request->ip(),
        ];

        if (Auth::check()) {
            $commentData['user_id'] = Auth::id();
            // Auto-approve comments from trusted users
            if (auth()->user()->isAdmin() || auth()->user()->isEditor()) {
                $commentData['status'] = 'approved';
            } else {
                $commentData['status'] = setting('moderate_comments', true) ? 'pending' : 'approved';
            }
        } else {
            $commentData['guest_name'] = $request->guest_name;
            $commentData['guest_email'] = $request->guest_email;
            $commentData['status'] = setting('moderate_comments', true) ? 'pending' : 'approved';
        }

        $comment = Comment::create($commentData);

        if ($request->expectsJson()) {
            return $this->successResponse('Comment submitted successfully.', [
                'comment' => $comment->load('user'),
                'status' => $comment->status,
            ]);
        }

        $message = $comment->status === 'approved' 
            ? 'Comment posted successfully.' 
            : 'Comment submitted and is awaiting moderation.';

        return redirect()->back()->with('success', $message);
    }

    /**
     * Update a comment.
     */
    public function update(Request $request, Comment $comment)
    {
        // Check if user can edit this comment
        if (!$comment->canEdit()) {
            return $this->errorResponse('You cannot edit this comment.', null, 403);
        }

        $request->validate([
            'content' => 'required|string|min:3|max:1000',
        ]);

        $comment->update([
            'content' => $request->content,
        ]);

        if ($request->expectsJson()) {
            return $this->successResponse('Comment updated successfully.', [
                'comment' => $comment,
            ]);
        }

        return redirect()->back()->with('success', 'Comment updated successfully.');
    }

    /**
     * Delete a comment.
     */
    public function destroy(Request $request, Comment $comment)
    {
        // Check if user can delete this comment
        if (!$comment->canDelete()) {
            return $this->errorResponse('You cannot delete this comment.', null, 403);
        }

        $comment->delete();

        if ($request->expectsJson()) {
            return $this->successResponse('Comment deleted successfully.');
        }

        return redirect()->back()->with('success', 'Comment deleted successfully.');
    }

    /**
     * Like a comment.
     */
    public function like(Request $request, Comment $comment)
    {
        if (!Auth::check()) {
            return $this->errorResponse('You must be logged in to like comments.', null, 401);
        }

        // For simplicity, we'll just increment the likes count
        // You can implement a separate CommentLike model if needed
        $comment->incrementLikes();

        if ($request->expectsJson()) {
            return $this->successResponse('Comment liked successfully.', [
                'likes_count' => $comment->likes_count,
            ]);
        }

        return redirect()->back();
    }

    /**
     * Get comments for a news article (AJAX).
     */
    public function getComments(News $news)
    {
        $comments = $news->approvedComments()
            ->topLevel()
            ->with(['user', 'children.user'])
            ->latest()
            ->get();

        return $this->successResponse('Comments retrieved successfully.', [
            'comments' => $comments,
        ]);
    }
}