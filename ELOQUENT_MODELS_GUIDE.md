# News System Eloquent Models - Laravel 12 (newsman)

## Overview
This document outlines all the Eloquent models created for the newsman news management system, based on the migration structure and inspired by the old Laranews project with significant modern improvements.

## Models Created

### 1. **User Model** (`app/Models/User.php`)
Enhanced user authentication and management model.

**Key Features:**
- Role-based access control (admin, editor, author, subscriber)
- User status management (active, inactive, banned)
- Multi-language support
- Social media links and preferences storage
- Soft deletes and email verification
- Login tracking with IP addresses

**Relationships:**
- `hasMany(News::class)` - Articles authored by user
- `hasMany(Comment::class)` - Comments made by user
- `hasMany(Like::class)` - Likes given by user
- `hasMany(NewsView::class)` - News views by user

**Key Methods:**
- `hasRole()`, `isAdmin()`, `isEditor()`, `isAuthor()` - Role checking
- `canPublish()` - Permission checking
- `getAvatarUrlAttribute()` - Avatar URL with Gravatar fallback
- `getPreference()`, `setPreference()` - User preferences management

### 2. **Category Model** (`app/Models/Category.php`)
Hierarchical category system for organizing news articles.

**Key Features:**
- Hierarchical structure (parent-child relationships)
- SEO-friendly slugs
- Multi-language support
- Image and icon support
- Sort ordering
- JSON metadata storage

**Relationships:**
- `belongsTo(Category::class, 'parent_id')` - Parent category
- `hasMany(Category::class, 'parent_id')` - Child categories
- `hasMany(News::class)` - News articles in category
- `hasMany(RssFeed::class)` - RSS feeds for category

**Key Methods:**
- `isRoot()`, `hasChildren()` - Hierarchy checks
- `getDescendantIds()` - Get all descendant category IDs
- `getTotalNewsCount()` - Count including child categories
- `getBreadcrumbAttribute()` - Breadcrumb navigation

### 3. **Tag Model** (`app/Models/Tag.php`)
Tagging system for news articles.

**Key Features:**
- SEO-friendly slugs
- Soft deletes
- Activity status
- Auto-slug generation

**Relationships:**
- `belongsToMany(News::class)` - News articles with this tag

**Key Methods:**
- `getPublishedNewsCount()` - Count of published articles
- `hasPublishedNews()` - Check if tag has published content

### 4. **Media Model** (`app/Models/Media.php`)
Comprehensive media management system.

**Key Features:**
- Multiple media types (image, video, audio, document)
- File metadata storage (size, dimensions, mime type)
- Multiple disk support
- Visibility controls
- Alt text for accessibility

**Relationships:**
- `hasMany(News::class, 'featured_image_id')` - News using as featured image
- `hasMany(Advertisement::class)` - Advertisements using media

**Key Methods:**
- `getUrlAttribute()` - Full media URL
- `exists()` - Check if file exists on disk
- `deleteFile()` - Remove file from storage
- `getThumbnailUrl()` - Generate thumbnail URLs

### 5. **News Model** (`app/Models/News.php`)
Main news article model with comprehensive features.

**Key Features:**
- SEO optimization (slug, meta fields)
- Multi-language support
- Publication workflow (draft, published, archived)
- View counting and analytics
- Featured article support
- Comment controls
- External RSS integration

**Relationships:**
- `belongsTo(User::class, 'author_id')` - Article author
- `belongsTo(Category::class)` - Article category
- `belongsTo(Media::class, 'featured_image_id')` - Featured image
- `belongsToMany(Tag::class)` - Article tags
- `hasMany(Like::class)` - Likes/dislikes
- `hasMany(Comment::class)` - Comments
- `hasMany(NewsView::class)` - View analytics

**Key Scopes:**
- `published()` - Only published articles
- `featured()` - Featured articles only
- `trending()` - Popular articles by timeframe
- `related()` - Related articles by category

**Key Methods:**
- `recordView()` - Track article views
- `publish()`, `archive()`, `makeDraft()` - Status management
- `getRelatedArticles()` - Find similar content
- `isLikedBy()` - Check user like status

### 6. **Like Model** (`app/Models/Like.php`)
Like/dislike system for news articles.

**Key Features:**
- Support for both registered and anonymous users
- Like/dislike toggle functionality
- IP address tracking for anonymous users
- User agent tracking

**Relationships:**
- `belongsTo(News::class)` - Liked article
- `belongsTo(User::class)` - User who liked (if registered)

**Key Methods:**
- `createOrUpdate()` - Smart like/dislike handling
- `toggle()` - Switch between like and dislike
- `isLike()`, `isDislike()` - Type checking

### 7. **Comment Model** (`app/Models/Comment.php`)
Threaded comment system with moderation.

**Key Features:**
- Threaded comments (replies support)
- Guest and registered user comments
- Comment moderation (pending, approved, rejected, spam)
- Like counting for comments
- Pin functionality for important comments

**Relationships:**
- `belongsTo(News::class)` - Commented article
- `belongsTo(User::class)` - Commenter (if registered)
- `belongsTo(Comment::class, 'parent_id')` - Parent comment
- `hasMany(Comment::class, 'parent_id')` - Child comments

**Key Methods:**
- `approve()`, `reject()`, `markAsSpam()` - Moderation
- `pin()`, `unpin()` - Highlighting
- `getThread()` - Get comment hierarchy
- `canEdit()`, `canDelete()` - Permission checking

### 8. **Advertisement Model** (`app/Models/Advertisement.php`)
Advanced advertisement management system.

**Key Features:**
- Multiple ad positions (header, sidebar, content, etc.)
- Scheduling (start/end dates)
- Performance tracking (impressions, clicks, CTR)
- Multiple pricing models (CPM, CPC, fixed)
- Display rules (JSON-based conditions)

**Relationships:**
- `belongsTo(Media::class)` - Advertisement media

**Key Methods:**
- `recordImpression()`, `recordClick()` - Analytics tracking
- `calculateCost()` - Cost calculation by pricing model
- `shouldDisplay()` - Check display rules
- `activate()`, `deactivate()` - Status management

### 9. **Setting Model** (`app/Models/Setting.php`)
Flexible application settings system.

**Key Features:**
- Key-value storage with JSON values
- Type-aware value handling (string, boolean, integer, array)
- Grouping and categorization
- Public/private setting distinction
- Autoload functionality for performance
- Comprehensive caching

**Key Methods:**
- `get()`, `set()` - Value management
- `getGroup()` - Get settings by group
- `getPublic()` - Frontend-safe settings
- `initializeDefaults()` - Setup default settings

### 10. **NewsView Model** (`app/Models/NewsView.php`)
Detailed analytics and view tracking.

**Key Features:**
- User and anonymous view tracking
- Device and browser information
- Referrer tracking
- Time-based analytics
- Performance optimizations for high volume

**Relationships:**
- `belongsTo(News::class)` - Viewed article
- `belongsTo(User::class)` - Viewer (if registered)

**Key Methods:**
- `recordView()` - Track article view
- `getStatsForNews()` - Article statistics
- `getTrendingArticles()` - Popular content
- `cleanOldRecords()` - Data maintenance

### 11. **RssFeed Model** (`app/Models/RssFeed.php`)
RSS feed management and automation.

**Key Features:**
- Automated feed fetching with scheduling
- Error tracking and monitoring
- Parsing rules for different feed formats
- Success rate monitoring
- Connection testing

**Relationships:**
- `belongsTo(Category::class)` - Default category for imported articles
- `hasMany(News::class)` - Imported articles

**Key Methods:**
- `testConnection()` - Validate RSS feed
- `recordSuccessfulFetch()`, `recordFailedFetch()` - Status tracking
- `getFeedsToFetch()` - Get feeds ready for update
- `getStatistics()` - Overall RSS statistics

## Model Relationships Summary

### Primary Relationships
```
User
├── hasMany(News) [author_id]
├── hasMany(Comment)
├── hasMany(Like)
└── hasMany(NewsView)

Category
├── belongsTo(Category) [parent_id]
├── hasMany(Category) [parent_id] 
├── hasMany(News)
└── hasMany(RssFeed)

News (Central Model)
├── belongsTo(User) [author_id]
├── belongsTo(Category)
├── belongsTo(Media) [featured_image_id]
├── belongsToMany(Tag) [news_tags pivot]
├── hasMany(Like)
├── hasMany(Comment)
└── hasMany(NewsView)

Media
├── hasMany(News) [featured_image_id]
└── hasMany(Advertisement)
```

## Key Features Implemented

### 1. **Performance Optimizations**
- Strategic database indexing
- Query scopes for common operations
- Caching for settings and frequently accessed data
- Efficient relationship loading

### 2. **SEO Features**
- Auto-generated slugs for URLs
- Meta title, description, and keywords
- Full-text search capabilities
- Structured data preparation

### 3. **User Experience**
- Comprehensive permission system
- Multi-language support throughout
- Responsive design considerations
- Analytics and insights

### 4. **Content Management**
- Workflow management (draft → published → archived)
- Hierarchical organization (categories, threaded comments)
- Media management with metadata
- Automated RSS import system

### 5. **Analytics & Insights**
- Detailed view tracking
- Advertisement performance metrics
- Comment engagement statistics
- RSS feed health monitoring

### 6. **Security Features**
- IP address tracking for anonymous actions
- User agent logging for security
- Soft deletes for data recovery
- Permission-based access control

## Usage Examples

### Creating News Article
```php
$news = News::create([
    'title' => 'Breaking News Title',
    'content' => 'Article content...',
    'author_id' => auth()->id(),
    'category_id' => 1,
    'status' => 'published',
    'published_at' => now(),
]);

// Add tags
$news->tags()->attach([1, 2, 3]);
```

### Recording Article View
```php
$news = News::find(1);
$news->recordView([
    'user_id' => auth()->id(),
    'device_info' => ['type' => 'mobile'],
]);
```

### Managing Settings
```php
// Set a setting
Setting::set('site_name', 'My News Site', 'string', [
    'group' => 'general',
    'is_public' => true,
]);

// Get a setting
$siteName = Setting::get('site_name', 'Default Site Name');
```

### Working with Comments
```php
$comment = Comment::create([
    'news_id' => 1,
    'user_id' => auth()->id(),
    'content' => 'Great article!',
    'status' => 'approved',
]);

// Reply to comment
$reply = Comment::create([
    'news_id' => 1,
    'parent_id' => $comment->id,
    'user_id' => auth()->id(),
    'content' => 'Thanks for reading!',
]);
```

## Next Steps

1. **Create Model Factories** for testing and seeding
2. **Set up Form Requests** for validation
3. **Create API Resources** for JSON responses
4. **Implement Observers** for model events
5. **Add Model Policies** for authorization
6. **Create Custom Collections** for specialized queries

This model structure provides a solid foundation for a modern, scalable news management system with comprehensive features for content creation, user management, analytics, and automation.