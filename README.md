# Global Chronicle — Open Source Newspaper Portal Website 



[![License: Apache-2.0](https://img.shields.io/badge/License-Apache_2.0-blue.svg)](./LICENSE)
[![Built with Laravel](https://img.shields.io/badge/Laravel-10%2B-red?logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.x-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com)
[![Vite](https://img.shields.io/badge/Vite-%F0%9F%94%A5-646CFF?logo=vite&logoColor=white)](https://vitejs.dev)
[![Open Source](https://img.shields.io/badge/Open%20Source-Yes-brightgreen)](#)

A modern, responsive, and SEO-friendly open source news portal built with Laravel — designed for reading, managing, and publishing daily news efficiently. Ideal as a Laravel news CMS for editors, journalists, and community-driven news sites. Built by a Bangladesh developer and open to contributions from the global developer community.

---

## Table of Contents

- [Overview](#overview)
- [Key Features](#key-features)
- [Tech Stack / Technologies Used](#tech-stack--technologies-used)
- [Quick Installation](#quick-installation)
- [Folder Structure](#folder-structure)
- [Contributing](#contributing)
- [Development & Testing](#development--testing)
- [Deployment](#deployment)
- [License](#license)
- [Contact & Links](#contact--links)
- [SEO & Keywords](#seo--keywords)

---

## Overview

Global Chronicle is a full-featured newspaper portal website (news portal) and open source Laravel news CMS that focuses on editorial workflow, content quality, and discoverability. It enables editors to publish daily news, curate categories, manage media and advertisements, moderate comments, and deliver real-time updates to readers across mobile and desktop.

Designed for newsrooms and indie publishers, Global Chronicle aims to be:

- Fast and SEO-friendly (server-rendered pages, clean meta tags, sitemaps, RSS)
- Responsive and mobile-first for modern readers
- Extensible and developer-friendly (Laravel-based codebase)

Use cases:

- Local and national newspaper websites
- Community news portals
- Tech, sports, and niche vertical publications
- Educational demos and open-source portfolios

---

## Key Features

1. Category-based News Management
	- Group articles into categories (Politics, Sports, Tech, Business, Entertainment, Opinion)
	- Category pages with paginated lists, structured URLs, and breadcrumb navigation

2. Admin Dashboard for Editors & Admins
	- Create/edit/publish/unpublish news items
	- Drafts, scheduled publishing, and revision history
	- Manage users, roles (editor, author, admin), and permissions

3. Real-time News Updates
	- Optional real-time feed (broadcasting/websockets) for live headlines and breaking news
	- Live ticker widgets and auto-refreshing sections

4. Commenting & Reaction System
	- Nested comments, moderation queue, spam protections
	- Likes/reactions with per-article counts

5. User Login & Registration
	- Secure authentication (Laravel auth), email verification, password resets
	- Role-based access control for editorial workflows

6. Search & Filter Functionality
	- Full-text search and filters (category, tag, date, author)
	- SEO-friendly result pages with rich snippets

7. Advertisement Management
	- Ad zones for banners (header, sidebar, in-article)
	- Simple dashboard to add/rotate ad units and embed third-party code

8. Analytics & Trending Section
	- Track article views and generate trending lists
	- Lightweight analytics for editors (top articles, categories, tags)

9. Responsive & Mobile-first Layout
	- Mobile-optimized templates, accessible navigation, fast-loading images
	- Progressive enhancement with optional SPA components (Vue/React)

10. API, RSS & Integrations
	- REST endpoints for content syndication and integrations
	- Built-in RSS feeds for categories and sitewide updates

> Internally, the project models include: `News`, `Category`, `Tag`, `Comment`, `Like`, `Media`, `Advertisement`, `NewsView`, `RssFeed`, `Setting`, and `User`.

---

## Tech Stack / Technologies Used

Badges:

[![Laravel](https://img.shields.io/badge/Laravel-Framework-red?logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white)](https://www.php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.x-4479A1?logo=mysql&logoColor=white)](https://mysql.com)
[![SQLite](https://img.shields.io/badge/SQLite-3-003B57?logo=sqlite&logoColor=white)](https://www.sqlite.org)
[![HTML5](https://img.shields.io/badge/HTML5-E34F26?logo=html5&logoColor=white)](#)
[![CSS3](https://img.shields.io/badge/CSS3-1572B6?logo=css3&logoColor=white)](#)
[![TailwindCSS](https://img.shields.io/badge/TailwindCSS-38B2AC?logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-7952B3?logo=bootstrap&logoColor=white)](https://getbootstrap.com)
[![JavaScript](https://img.shields.io/badge/JavaScript-ES6-F7DF1E?logo=javascript&logoColor=black)](#)
[![jQuery](https://img.shields.io/badge/jQuery-0769AD?logo=jquery&logoColor=white)](https://jquery.com)
[![Vue](https://img.shields.io/badge/Vue.js-35495E?logo=vuedotjs&logoColor=4FC08D)](https://vuejs.org)
[![React](https://img.shields.io/badge/React-20232A?logo=react&logoColor=61DAFB)](https://react.dev)
[![Vite](https://img.shields.io/badge/Vite-646CFF?logo=vite&logoColor=white)](https://vitejs.dev)

---

## Quick Installation

These instructions assume you have Git, PHP (8.x), Composer, Node.js (LTS), and a database (MySQL or SQLite) installed. Adapt DB steps as needed.

1) Clone the repository

```bash
git clone https://github.com/Aiman-Al-Mahmud/Global-Chronicle.git
cd Global-Chronicle
```

2) Install PHP dependencies

```bash
composer install
```

3) Copy environment file and configure

```bash
cp .env.example .env
# Edit .env (APP_NAME, APP_URL, DB_*, MAIL_*)
```

4) Generate application key

```bash
php artisan key:generate
```

5) Configure your database in `.env`

- MySQL example:
  - `DB_CONNECTION=mysql`
  - `DB_HOST=127.0.0.1`
  - `DB_PORT=3306`
  - `DB_DATABASE=global_chronicle`
  - `DB_USERNAME=your_user`
  - `DB_PASSWORD=your_password`

- Or use SQLite (simple local setup):

```bash
touch database/database.sqlite
# Then in .env set:
# DB_CONNECTION=sqlite
# DB_DATABASE="$(pwd)/database/database.sqlite"
```

6) Run migrations and seeders

```bash
php artisan migrate --seed
```

7) Link storage and install frontend dependencies

```bash
php artisan storage:link
npm install
npm run dev    # development build with hot reload
# or
npm run build  # production build
```

8) Run the development server

```bash
php artisan serve --host=127.0.0.1 --port=8000
# Visit http://127.0.0.1:8000
```

Notes:

- For broadcasting (real-time), configure Pusher or Laravel Websockets in `.env`.
- Ensure queues are configured for email/notifications if enabled.

---

## Folder Structure

An overview to help you navigate the codebase:

```
app/
  Http/
	 Controllers/
	 Middleware/
  Models/
	 Advertisement.php
	 Category.php
	 Comment.php
	 Like.php
	 Media.php
	 News.php
	 NewsView.php
	 RssFeed.php
	 Setting.php
	 Tag.php
	 User.php
bootstrap/
config/
database/
  database.sqlite
  migrations/
  seeders/
public/
resources/
  views/
  js/
  css/
routes/
  web.php
  console.php
tests/
```

---

## Contributing

We welcome improvements, bug fixes, and community-driven features. Whether you're a Bangladesh developer or part of the global community, your contributions are appreciated!

How to contribute:

1. Fork the repository
2. Create a feature branch: `git checkout -b feat/my-new-feature`
3. Make focused commits with clear messages
4. Add tests for new features or bug fixes (PHPUnit/Laravel test suite)
5. Submit a pull request describing:
	- What problem you solved
	- How you tested it
	- Any migration or env changes

Guidelines:

- Follow PSR-12 and Laravel conventions
- Keep PRs small and descriptive
- Run tests and lints before opening PRs

Support the project:

- Star the repo ⭐
- Open issues for bugs or features
- Share with teammates and friends

---

## Development & Testing

Run the test suite:

```bash
php vendor/bin/phpunit
```

Helpful artisan cache clears:

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

---

## Deployment

Checklist:

- Set `APP_ENV=production`, `APP_DEBUG=false`
- Build frontend: `npm run build`
- Run DB migrations: `php artisan migrate --force`
- Configure queue workers (Supervisor) if using queues
- Enable PHP OPcache, and configure Nginx/Apache caching and gzip
- Use a CDN for static assets and images

---

## License

Global Chronicle is released under the Apache License 2.0 — see the [LICENSE](./LICENSE) file for details. Free for personal, educational, and commercial use with attribution and compliance with the license terms.

---

## Contact & Links

- Author: Aiman Al Mahmud
- GitHub: https://github.com/Aiman-Al-Mahmud/Global-Chronicle


If you use this project for a public demo or deployment, please link back or drop a message — I’d love to see where it’s used!

---


This repository is an open source news portal built with Laravel — ideal as a Laravel news CMS and modern newspaper website. If you're searching for a robust news portal, news portal CMS, or an open source news portal example maintained by a Bangladesh developer, Global Chronicle is a production-ready starting point.

Keywords included naturally: news portal, open source, Laravel news CMS, modern newspaper website, Bangladesh developer, news portal website, responsive news CMS.

---

Thank you for checking out Global Chronicle! If you found this project useful, please star the repo ⭐, open issues for bugs or features, and consider contributing.


## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
