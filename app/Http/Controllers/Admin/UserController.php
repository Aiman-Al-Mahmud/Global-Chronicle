<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Apply filters
        if ($request->has('role') && $request->role) {
            $query->where('role', $request->role);
        }

        if ($request->has('status') && $request->status !== '') {
            $status = $request->status === 'active';
            $query->where('is_active', $status);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        if ($sortBy === 'latest_login') {
            $query->orderBy('last_login_at', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $users = $query->paginate(15);

        return $this->view('admin.users.users', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        return $this->view('admin.users.add_user');
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:2|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,editor,author,subscriber',
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:500',
            'website' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'facebook_url' => 'nullable|url|max:255',
            'linkedin_url' => 'nullable|url|max:255',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->all();
        $data['password'] = Hash::make($request->password);

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            $data['profile_image'] = $request->file('profile_image')
                ->store('profiles', 'public');
        }

        $user = User::create($data);

        if ($request->expectsJson()) {
            return $this->successResponse('User created successfully.', $user);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        $user->load(['news', 'comments', 'likes']);
        
        $stats = [
            'total_news' => $user->news()->count(),
            'published_news' => $user->news()->published()->count(),
            'draft_news' => $user->news()->draft()->count(),
            'total_comments' => $user->comments()->count(),
            'total_likes' => $user->likes()->count(),
        ];

        return $this->view('admin.users.user_profile', compact('user', 'stats'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        return $this->view('admin.users.edit_user', compact('user'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|min:2|max:100',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|in:admin,editor,author,subscriber',
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:500',
            'website' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'facebook_url' => 'nullable|url|max:255',
            'linkedin_url' => 'nullable|url|max:255',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->except(['password']);

        // Handle password update
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            // Delete old image if exists
            if ($user->profile_image) {
                \Storage::disk('public')->delete($user->profile_image);
            }
            $data['profile_image'] = $request->file('profile_image')
                ->store('profiles', 'public');
        }

        $user->update($data);

        if ($request->expectsJson()) {
            return $this->successResponse('User updated successfully.', $user);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(Request $request, User $user)
    {
        // Prevent deleting the last admin user
        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            if ($request->expectsJson()) {
                return $this->errorResponse('Cannot delete the last admin user.');
            }
            return redirect()->back()
                ->withErrors(['error' => 'Cannot delete the last admin user.']);
        }

        // Delete profile image if exists
        if ($user->profile_image) {
            \Storage::disk('public')->delete($user->profile_image);
        }

        $user->delete();

        if ($request->expectsJson()) {
            return $this->successResponse('User deleted successfully.');
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Quick create user (AJAX).
     */
    public function quickCreate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:2|max:100',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:admin,editor,author,subscriber',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make('password123'), // Default password
            'role' => $request->role,
            'is_active' => true,
        ]);

        return $this->successResponse('User created successfully. Default password: password123', $user);
    }

    /**
     * Bulk actions for users.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete,change_role',
            'selected' => 'required|array|min:1',
            'selected.*' => 'exists:users,id',
            'role' => 'required_if:action,change_role|in:admin,editor,author,subscriber',
        ]);

        $users = User::whereIn('id', $request->selected);

        switch ($request->action) {
            case 'activate':
                $users->update(['is_active' => true]);
                $message = 'Selected users activated successfully.';
                break;

            case 'deactivate':
                $users->update(['is_active' => false]);
                $message = 'Selected users deactivated successfully.';
                break;

            case 'change_role':
                $users->update(['role' => $request->role]);
                $message = "Selected users' role changed to {$request->role} successfully.";
                break;

            case 'delete':
                // Check if any admin user is selected and prevent deletion of last admin
                $adminUsers = $users->where('role', 'admin');
                $totalAdmins = User::where('role', 'admin')->count();
                
                if ($adminUsers->count() >= $totalAdmins) {
                    return $this->errorResponse('Cannot delete all admin users.');
                }

                // Delete profile images
                foreach ($users->get() as $user) {
                    if ($user->profile_image) {
                        \Storage::disk('public')->delete($user->profile_image);
                    }
                }

                $users->delete();
                $message = 'Selected users deleted successfully.';
                break;
        }

        if ($request->expectsJson()) {
            return $this->successResponse($message);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Get user activity statistics.
     */
    public function getUserActivity(User $user)
    {
        $activity = [
            'recent_news' => $user->news()
                ->with('category')
                ->latest()
                ->take(5)
                ->get(['id', 'title', 'status', 'created_at', 'category_id']),
            'recent_comments' => $user->comments()
                ->with('news:id,title')
                ->latest()
                ->take(5)
                ->get(['id', 'content', 'created_at', 'news_id']),
            'monthly_news_count' => $user->news()
                ->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
                ->whereYear('created_at', now()->year)
                ->groupBy('month')
                ->pluck('count', 'month')
                ->toArray(),
        ];

        return $this->successResponse('User activity retrieved successfully.', $activity);
    }

    /**
     * Reset user password.
     */
    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        if ($request->expectsJson()) {
            return $this->successResponse('Password reset successfully.');
        }

        return redirect()->back()->with('success', 'Password reset successfully.');
    }

    /**
     * Toggle user status.
     */
    public function toggleStatus(User $user)
    {
        $user->update([
            'is_active' => !$user->is_active,
        ]);

        $status = $user->is_active ? 'activated' : 'deactivated';
        
        return $this->successResponse("User {$status} successfully.", $user);
    }
}