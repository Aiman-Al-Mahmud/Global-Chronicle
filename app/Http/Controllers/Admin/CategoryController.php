<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories.
     */
    public function index(Request $request)
    {
        $query = Category::with('parent');

        // Apply search filter
        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }

        // Apply language filter
        if ($request->has('language') && $request->language) {
            $query->language($request->language);
        }

        // Apply status filter
        if ($request->has('status') && $request->status !== '') {
            $status = $request->status === 'active';
            $query->where('is_active', $status);
        }

        $categories = $query->ordered()->paginate(15);

        // Get parent categories for filter
        $parentCategories = Category::root()->active()->ordered()->get();

        return $this->view('admin.categories.categories', compact(
            'categories',
            'parentCategories'
        ));
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'icon' => 'nullable|string|max:100',
            'color' => 'nullable|string|size:7|regex:/^#[a-fA-F0-9]{6}$/',
            'sort_order' => 'nullable|integer|min:0',
            'language' => 'required|in:en,ar,fr,es',
            'is_active' => 'boolean',
            'meta' => 'nullable|json',
        ]);

        $categoryData = $request->except(['image']);

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('categories', 'public');
            $categoryData['image'] = $imagePath;
        }

        // Validate parent hierarchy (prevent circular references)
        if ($request->parent_id) {
            $parent = Category::find($request->parent_id);
            if ($parent && $this->wouldCreateCircularReference($parent, null)) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['parent_id' => 'This would create a circular reference.']);
            }
        }

        Category::create($categoryData);

        if ($request->expectsJson()) {
            return $this->successResponse('Category created successfully.');
        }

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($category->id)],
            'description' => 'nullable|string',
            'parent_id' => ['nullable', 'exists:categories,id', Rule::notIn([$category->id])],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'icon' => 'nullable|string|max:100',
            'color' => 'nullable|string|size:7|regex:/^#[a-fA-F0-9]{6}$/',
            'sort_order' => 'nullable|integer|min:0',
            'language' => 'required|in:en,ar,fr,es',
            'is_active' => 'boolean',
            'meta' => 'nullable|json',
        ]);

        $categoryData = $request->except(['image']);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }
            
            $imagePath = $request->file('image')->store('categories', 'public');
            $categoryData['image'] = $imagePath;
        }

        // Validate parent hierarchy (prevent circular references)
        if ($request->parent_id && $request->parent_id != $category->parent_id) {
            $parent = Category::find($request->parent_id);
            if ($parent && $this->wouldCreateCircularReference($parent, $category)) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['parent_id' => 'This would create a circular reference.']);
            }
        }

        $category->update($categoryData);

        if ($request->expectsJson()) {
            return $this->successResponse('Category updated successfully.');
        }

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified category.
     */
    public function destroy(Request $request, Category $category)
    {
        // Check if category has children
        if ($category->children()->exists()) {
            if ($request->expectsJson()) {
                return $this->errorResponse('Cannot delete category with subcategories.');
            }
            return redirect()->back()
                ->withErrors(['error' => 'Cannot delete category with subcategories.']);
        }

        // Check if category has news articles
        if ($category->news()->exists()) {
            if ($request->expectsJson()) {
                return $this->errorResponse('Cannot delete category with associated news articles.');
            }
            return redirect()->back()
                ->withErrors(['error' => 'Cannot delete category with associated news articles.']);
        }

        // Delete image
        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }

        $category->delete();

        if ($request->expectsJson()) {
            return $this->successResponse('Category deleted successfully.');
        }

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    /**
     * Get categories as JSON (for AJAX requests).
     */
    public function getCategories(Request $request)
    {
        $query = Category::active();

        if ($request->has('parent_id')) {
            if ($request->parent_id === 'null' || $request->parent_id === '') {
                $query->root();
            } else {
                $query->where('parent_id', $request->parent_id);
            }
        }

        if ($request->has('language')) {
            $query->language($request->language);
        }

        $categories = $query->ordered()->get(['id', 'name', 'slug', 'parent_id']);

        return $this->successResponse('Categories retrieved successfully.', $categories);
    }

    /**
     * Get category hierarchy as JSON.
     */
    public function getHierarchy()
    {
        $categories = Category::active()
            ->with('allChildren')
            ->root()
            ->ordered()
            ->get();

        return $this->successResponse('Category hierarchy retrieved successfully.', $categories);
    }

    /**
     * Bulk actions for categories.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,activate,deactivate',
            'selected' => 'required|array|min:1',
            'selected.*' => 'exists:categories,id',
        ]);

        $categories = Category::whereIn('id', $request->selected);

        switch ($request->action) {
            case 'delete':
                // Check if any category has children or news
                $categoriesWithChildren = $categories->whereHas('children')->exists();
                $categoriesWithNews = $categories->whereHas('news')->exists();

                if ($categoriesWithChildren || $categoriesWithNews) {
                    return $this->errorResponse('Cannot delete categories with subcategories or associated news articles.');
                }

                $categories->delete();
                $message = 'Selected categories deleted successfully.';
                break;

            case 'activate':
                $categories->update(['is_active' => true]);
                $message = 'Selected categories activated successfully.';
                break;

            case 'deactivate':
                $categories->update(['is_active' => false]);
                $message = 'Selected categories deactivated successfully.';
                break;
        }

        if ($request->expectsJson()) {
            return $this->successResponse($message);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Check if setting a parent would create a circular reference.
     */
    private function wouldCreateCircularReference(Category $parent, ?Category $category): bool
    {
        if (!$category) {
            return false;
        }

        $currentParent = $parent;
        while ($currentParent) {
            if ($currentParent->id === $category->id) {
                return true;
            }
            $currentParent = $currentParent->parent;
        }

        return false;
    }
}