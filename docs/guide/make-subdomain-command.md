# make:subdomain Command

Scaffolds a new subdomain portal: its CSS/JS entries, Blade layout, a starter Livewire dashboard page, and a route file.

```bash
php artisan make:subdomain blog
```

This creates:

```
resources/css/blog.css
resources/js/blog.js
resources/views/layouts/blog.blade.php
resources/views/pages/blog/⚡dashboard/dashboard.php
resources/views/pages/blog/⚡dashboard/dashboard.blade.php
routes/blog.php
```

The name must be lowercase alphanumeric with hyphens (e.g. `blog`, `partner-portal`), and can't already exist in `config('multidomain.sub_domains')`.

## What it doesn't do

It won't rewrite `config/multidomain.php`, `routes/web.php`, or `vite.config.js` for you. Editing PHP/JS source programmatically is fragile — a formatting quirk or unusual structure in your own edits could break the rewrite silently. Instead, the command prints the exact lines to add:

```
1. Add 'blog' => 'blog.'.env('APP_MAIN_DOMAIN') to config/multidomain.php sub_domains array
2. Add a Route::domain(config('multidomain.sub_domains.blog'))->group(fn () => include __DIR__.'/blog.php'); block to routes/web.php
3. Add "resources/css/blog.css" and "resources/js/blog.js" to vite.config.js input array
4. Add blog.<APP_MAIN_DOMAIN> to your local hosts/Herd config
```

Three small, safe manual edits — versus a code generator quietly mangling files you'll want to hand-tune anyway.

## Stubs

Templates live in `stubs/subdomain/` at the project root:

| Stub | Produces |
|---|---|
| `css.stub` | `resources/css/{name}.css` — imports the shared `resources/css/theme.css` |
| `js.stub` | `resources/js/{name}.js` — empty, matching the existing convention |
| `layout.stub` | `resources/views/layouts/{name}.blade.php` |
| `route.stub` | `routes/{name}.php` |
| `dashboard.stub` / `dashboard.blade.stub` | the starter Livewire dashboard page |

Edit these stubs to change what every newly scaffolded portal looks like.
