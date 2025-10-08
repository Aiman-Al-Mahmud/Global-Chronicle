<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class PageController extends Controller
{
    /**
     * Display the dashboard page.
     */
    public function dashboard()
    {
        return $this->renderView('admin.dashboard');
    }

    /**
     * Display the about page.
     */
    public function about()
    {
        return $this->renderView('admin.about');
    }

    /**
     * Display the contact page.
     */
    public function contact()
    {
        return $this->renderView('admin.contact.contact');
    }

    /**
     * Display the contact details page.
     */
    public function contactDetails()
    {
        return $this->renderView('admin.contact.contact_details');
    }

    /**
     * Display the contact reply page.
     */
    public function contactReply()
    {
        return $this->renderView('admin.contact.contact_reply');
    }

    /**
     * Display the analytics page.
     */
    public function analytics()
    {
        return $this->renderView('admin.analytics');
    }

    /**
     * Display the analytics2 page.
     */
    public function analytics2()
    {
        return $this->renderView('admin.analytics2');
    }

    /**
     * Display the calendar page.
     */
    public function calendar()
    {
        return $this->renderView('admin.calendar');
    }

    /**
     * Display the gallery page.
     */
    public function gallery()
    {
        return $this->renderView('admin.gallery');
    }

    /**
     * Display the manage news page.
     */
    public function manageNews()
    {
        return $this->renderView('admin.news.manage_news');
    }

    /**
     * Display the news layout page.
     */
    public function newsLayout()
    {
        return $this->renderView('admin.news.news_layout');
    }

    /**
     * Display the newsletter page.
     */
    public function newsletter()
    {
        return $this->renderView('admin.newsletter');
    }

    /**
     * Display the newsletter2 page.
     */
    public function newsletter2()
    {
        return $this->renderView('admin.newsletter2');
    }

    /**
     * Display the newsletter3 page.
     */
    public function newsletter3()
    {
        return $this->renderView('admin.newsletter3');
    }

    /**
     * Display the profile page.
     */
    public function profile()
    {
        return $this->renderView('admin.profile');
    }

    /**
     * Display the subscribers page.
     */
    public function subscribers()
    {
        return $this->renderView('admin.subscribers');
    }

    /**
     * Display the general page.
     */
    public function general()
    {
        return $this->renderView('admin.general');
    }

    /**
     * Check if a view exists and render it.
     */
    private function renderView(string $viewName)
    {
        if (View::exists($viewName)) {
            return view($viewName);
        }

        // If view doesn't exist, return a placeholder response
        return response()->view('errors.404', [
            'message' => "View '{$viewName}' not found. This page is under development."
        ], 404);
    }

    /**
     * Handle dynamic page routing.
     */
    public function dynamicPage(Request $request, string $page)
    {
        // Sanitize the page name
        $page = preg_replace('/[^a-zA-Z0-9_\-\/]/', '', $page);
        
        // Convert URL format to view format
        $viewName = 'admin.' . str_replace('/', '.', $page);
        
        return $this->renderView($viewName);
    }

    /**
     * Get page title and breadcrumbs.
     */
    public function getPageInfo(Request $request)
    {
        $path = $request->get('path', '');
        $pathSegments = array_filter(explode('/', $path));
        
        $breadcrumbs = [
            ['name' => 'Dashboard', 'url' => route('admin.dashboard')]
        ];
        
        $title = 'Dashboard';
        
        foreach ($pathSegments as $segment) {
            $name = ucwords(str_replace(['-', '_'], ' ', $segment));
            $breadcrumbs[] = ['name' => $name, 'url' => '#'];
            $title = $name;
        }
        
        return response()->json([
            'title' => $title,
            'breadcrumbs' => $breadcrumbs
        ]);
    }

    /**
     * Get sidebar menu structure.
     */
    public function getSidebarMenu()
    {
        $menu = [
            [
                'name' => 'Dashboard',
                'icon' => 'fas fa-tachometer-alt',
                'url' => route('admin.dashboard'),
                'active' => request()->routeIs('admin.dashboard')
            ],
            [
                'name' => 'News Management',
                'icon' => 'fas fa-newspaper',
                'children' => [
                    [
                        'name' => 'All News',
                        'url' => route('admin.news.index'),
                        'active' => request()->routeIs('admin.news.*')
                    ],
                    [
                        'name' => 'Add News',
                        'url' => route('admin.news.create'),
                        'active' => request()->routeIs('admin.news.create')
                    ],
                    [
                        'name' => 'Categories',
                        'url' => route('admin.categories.index'),
                        'active' => request()->routeIs('admin.categories.*')
                    ],
                    [
                        'name' => 'Tags',
                        'url' => route('admin.tags.index'),
                        'active' => request()->routeIs('admin.tags.*')
                    ]
                ]
            ],
            [
                'name' => 'Media Library',
                'icon' => 'fas fa-images',
                'url' => route('admin.media.index'),
                'active' => request()->routeIs('admin.media.*')
            ],
            [
                'name' => 'Comments',
                'icon' => 'fas fa-comments',
                'url' => '#',
                'active' => false
            ],
            [
                'name' => 'Users',
                'icon' => 'fas fa-users',
                'url' => route('admin.users.index'),
                'active' => request()->routeIs('admin.users.*')
            ],
            [
                'name' => 'Advertisements',
                'icon' => 'fas fa-ad',
                'url' => route('admin.advertisements.index'),
                'active' => request()->routeIs('admin.advertisements.*')
            ],
            [
                'name' => 'RSS Feeds',
                'icon' => 'fas fa-rss',
                'url' => route('admin.rss.index'),
                'active' => request()->routeIs('admin.rss.*')
            ],
            [
                'name' => 'Analytics',
                'icon' => 'fas fa-chart-bar',
                'children' => [
                    [
                        'name' => 'Overview',
                        'url' => route('admin.analytics'),
                        'active' => request()->routeIs('admin.analytics')
                    ],
                    [
                        'name' => 'Detailed Reports',
                        'url' => route('admin.analytics2'),
                        'active' => request()->routeIs('admin.analytics2')
                    ]
                ]
            ],
            [
                'name' => 'Newsletter',
                'icon' => 'fas fa-envelope',
                'children' => [
                    [
                        'name' => 'Campaigns',
                        'url' => route('admin.newsletter'),
                        'active' => request()->routeIs('admin.newsletter')
                    ],
                    [
                        'name' => 'Templates',
                        'url' => route('admin.newsletter2'),
                        'active' => request()->routeIs('admin.newsletter2')
                    ],
                    [
                        'name' => 'Subscribers',
                        'url' => route('admin.subscribers'),
                        'active' => request()->routeIs('admin.subscribers')
                    ]
                ]
            ],
            [
                'name' => 'Contact',
                'icon' => 'fas fa-phone',
                'children' => [
                    [
                        'name' => 'Messages',
                        'url' => route('admin.contact'),
                        'active' => request()->routeIs('admin.contact')
                    ],
                    [
                        'name' => 'Contact Details',
                        'url' => route('admin.contact.details'),
                        'active' => request()->routeIs('admin.contact.details')
                    ]
                ]
            ],
            [
                'name' => 'Settings',
                'icon' => 'fas fa-cog',
                'url' => route('admin.settings.index'),
                'active' => request()->routeIs('admin.settings.*')
            ],
            [
                'name' => 'Profile',
                'icon' => 'fas fa-user',
                'url' => route('admin.profile'),
                'active' => request()->routeIs('admin.profile')
            ]
        ];

        return response()->json($menu);
    }

    /**
     * Get quick stats for dashboard widgets.
     */
    public function getQuickStats()
    {
        $stats = [
            'total_news' => \App\Models\News::count(),
            'published_news' => \App\Models\News::published()->count(),
            'draft_news' => \App\Models\News::draft()->count(),
            'total_users' => \App\Models\User::count(),
            'total_categories' => \App\Models\Category::count(),
            'total_tags' => \App\Models\Tag::count(),
            'total_comments' => \App\Models\Comment::count(),
            'total_media' => \App\Models\Media::count(),
        ];

        return response()->json($stats);
    }

    /**
     * Search across admin content.
     */
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:1|max:255'
        ]);

        $query = $request->get('query');
        $results = [];

        // Search news
        $news = \App\Models\News::where('title', 'like', "%{$query}%")
            ->orWhere('content', 'like', "%{$query}%")
            ->take(5)
            ->get(['id', 'title', 'slug']);

        foreach ($news as $item) {
            $results[] = [
                'type' => 'news',
                'title' => $item->title,
                'url' => route('admin.news.edit', $item->id),
                'icon' => 'fas fa-newspaper'
            ];
        }

        // Search users
        $users = \App\Models\User::where('name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->take(5)
            ->get(['id', 'name', 'email']);

        foreach ($users as $user) {
            $results[] = [
                'type' => 'user',
                'title' => $user->name . ' (' . $user->email . ')',
                'url' => route('admin.users.edit', $user->id),
                'icon' => 'fas fa-user'
            ];
        }

        // Search categories
        $categories = \App\Models\Category::where('name', 'like', "%{$query}%")
            ->take(5)
            ->get(['id', 'name']);

        foreach ($categories as $category) {
            $results[] = [
                'type' => 'category',
                'title' => $category->name,
                'url' => route('admin.categories.edit', $category->id),
                'icon' => 'fas fa-folder'
            ];
        }

        return response()->json([
            'query' => $query,
            'results' => $results,
            'total' => count($results)
        ]);
    }
}