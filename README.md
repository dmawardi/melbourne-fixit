# Melbourne Fixit

A website for collecting home maintenance and renovation job requests in Melbourne, Australia.

- **Customers** submit a request through a public form. When they log in with a verified email matching the request, it's linked to their account, and they can then add photos and follow updates.
- **Admins** manage requests under `/admin`: change the status, keep private notes, upload files (such as vendor quotes and the quotes we give the client), post timestamped updates, and see an activity log of every change.

Built with Laravel 13, Inertia v3, React and Fortify (email verification, two-factor authentication and passkeys).

## Requirements

- PHP 8.3+ with the GD extension (used to read image dimensions and generate seed photos)
- Composer
- Node.js and npm
- SQLite for local development

## Getting started

```bash
composer setup            # install dependencies, create .env, generate a key, migrate, build assets
php artisan migrate:fresh --seed
composer dev              # run the dev processes (php artisan dev)
```

### Seeded accounts

All seeded accounts use the password `password`.

| Email               | Role                                                                                                 |
| ------------------- | ---------------------------------------------------------------------------------------------------- |
| `admin@example.com` | Admin                                                                                                |
| `test@example.com`  | Customer with requests at different stages, plus one unlinked request that's linked when they log in |

The seeder also creates guest requests from people without an account. Mail is written to the log in development (`MAIL_MAILER=log`), so verification links appear in `storage/logs/laravel.log`.

## Admin users

Admins are users with the `is_admin` flag set. The flag can't be set through any form, only with this command:

```bash
php artisan app:make-admin someone@example.com            # grant admin access
php artisan app:make-admin someone@example.com --revoke   # remove it
```

The user must register first. The email lookup is case-insensitive. Admin routes also require a verified email.

## How job requests work

### Submitting and linking requests

1. Anyone can submit a request through the public form. It's rate limited (3 a minute and 20 a day per IP) and has a honeypot field. Only Victorian postcodes (`3xxx`) are accepted.
2. Requests are identified in URLs by a ULID (`public_id`), so they can't be guessed or enumerated.
3. A request is linked to an account when the user logs in, verifies their email, or opens their request list, as long as their verified email matches the request's contact email.
4. Customers can edit and delete their request while it's **Submitted** or **Reviewing**, and upload up to 10 photos.

### Visibility

Files and updates are either **admin only** (the default) or **shared with the customer**.

|              | Customers see                               | Admins see                  |
| ------------ | ------------------------------------------- | --------------------------- |
| Files        | Their own photos and files shared with them | Everything                  |
| Updates      | Shared updates, without the author's name   | Everything, with the author |
| Admin notes  | Never                                       | Yes                         |
| Activity log | Never                                       | Yes                         |

Customers get a 404 for admin-only files, so they can't tell those files exist. They can't delete files an admin shared with them, and admin uploads don't count towards their 10-photo limit.

### Files

All files are stored privately and served through signed URLs that expire after 30 minutes. Non-image files download under their original filename.

- **Customers** upload photos (JPG, PNG or WebP, up to 10MB each).
- **Admins** upload images, PDFs, Word and Excel files, up to 20MB each and 10 at a time. Each file has a type (photo, vendor quote, client quote or other) and a visibility, and both can be changed later.

### Activity log

Every change is logged with who made it and when:

- submissions and account linking
- edits, with old and new values
- status changes and notes changes
- file uploads, edits and deletions
- updates posted and deleted
- vendors assigned and removed
- request deletions

Logging a change also updates the request's `last_activity_at`, which is how the admin list shows the most recently updated requests first.

## Customer pages

| Route                        | Page                                                                                                                        | Indexed |
| ---------------------------- | --------------------------------------------------------------------------------------------------------------------------- | ------- |
| `/`                          | Home: services, how it works, areas covered and FAQs                                                                        | Yes     |
| `/request-a-quote`           | The public quote form. `?category=plumbing` preselects a service.                                                           | Yes     |
| `/request-a-quote/submitted` | Confirmation for guests, with links to log in or register with the same email                                               | No      |
| `/requests`                  | The customer's requests. This is where users land after logging in.                                                         | No      |
| `/requests/{id}`             | A request: updates, photos, shared documents and details. Customers can edit details and manage photos while it's editable. | No      |

`/dashboard` redirects to `/requests`.

## SEO and AI crawlers

Public pages are server-rendered (Inertia SSR), so search engines and AI crawlers get complete HTML without running JavaScript.

- **Indexing is opt-in.** The `SetRobotsTag` middleware sends `X-Robots-Tag: noindex, nofollow` on every page except the routes listed in `SetRobotsTag::INDEXABLE_ROUTES`. Add a route there to make it indexable; it's also added to the sitemap.
- **Nothing is indexed outside production.** Non-production environments send `noindex` on every page, and `robots.txt` blocks all crawling.
- **`/robots.txt`, `/sitemap.xml` and `/llms.txt`** are generated by `SeoController`. `llms.txt` gives AI assistants a plain-text summary of the services, areas and FAQs.
- **Head tags.** Public pages use the `Seo` component (`resources/js/components/seo.tsx`) for the description, canonical URL, Open Graph and Twitter tags, and JSON-LD.
- **Structured data.** `App\Support\StructuredData` builds the JSON-LD: the business (`HomeAndConstructionBusiness`) with its services and service area, the website, FAQs and breadcrumbs.
- **One source for the content.** Service descriptions live on the `JobCategory` enum and FAQs in `App\Support\Faqs`, so the page, the structured data and `llms.txt` always match.

### Production checklist

1. Set `APP_URL` to the live `https://` domain. Canonical URLs, the sitemap and structured data use it, and production ignores the request's host header.
2. Fill in the business details in `.env` (see [Business details](#business-details)).
3. Build and run the SSR server (see [Deploying the SSR server](#deploying-the-ssr-server)).
4. Check the service areas in `config/business.php`.
5. Submit `https://your-domain/sitemap.xml` in Google Search Console and Bing Webmaster Tools.

### Business details

These appear in the site footer, the structured data and `llms.txt`. Leave a value empty to leave it out; nothing is published as a placeholder.

```dotenv
BUSINESS_PHONE="03 9123 4567"
BUSINESS_EMAIL=hello@melbournefixit.com.au
BUSINESS_ABN="12 345 678 901"
BUSINESS_SAME_AS="https://www.facebook.com/melbournefixit,https://www.instagram.com/melbournefixit"
```

`BUSINESS_SAME_AS` is a comma-separated list, with no spaces, of the business's official profiles on other websites. It becomes `sameAs` in the structured data, which tells Google and AI assistants that those profiles belong to this business. Good candidates:

- **Google Business Profile:** the Maps link to your listing, e.g. `https://maps.google.com/?cid=1234567890123456789` (the "Share" link on your listing gives you this)
- **Facebook, Instagram or LinkedIn** business pages
- **Trade directories** you're listed on, such as hipages, ServiceSeeking or Yelp

Only include profiles the business controls. Leave it empty if there aren't any yet.

### Deploying the SSR server

Server-side rendering runs as a separate Node.js process alongside PHP. Laravel sends each page request to it (at `http://127.0.0.1:13714`, set in `config/inertia.php`) and gets back the rendered HTML. If the process isn't running, the site still works for visitors, but every page is sent as an empty shell that's filled in by JavaScript, and crawlers that don't run JavaScript see nothing.

**Requirements:** Node.js on the server (the same major version you build with).

**1. Build on every deploy.** `build:ssr` builds both the browser assets and the SSR bundle (`bootstrap/ssr/app.js`, which isn't committed):

```bash
npm ci
npm run build:ssr
```

**2. Keep the server running with a process manager.** For example, Supervisor at `/etc/supervisor/conf.d/melbourne-fixit-ssr.conf`:

```ini
[program:melbourne-fixit-ssr]
command=php /var/www/melbourne-fixit/artisan inertia:start-ssr
directory=/var/www/melbourne-fixit
user=www-data
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
redirect_stderr=true
stdout_logfile=/var/www/melbourne-fixit/storage/logs/ssr.log
```

Adjust the paths and user for your server, then load it:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start melbourne-fixit-ssr
```

On Laravel Forge, add a daemon with the command `php artisan inertia:start-ssr` instead.

**3. Restart it after each deploy,** so it serves the new bundle. Add this to the end of your deploy script; Supervisor starts it again automatically:

```bash
php artisan inertia:stop-ssr
```

**4. Check it's working:**

```bash
php artisan inertia:check-ssr
curl -s https://your-domain/ | grep -o '<h1[^>]*>[^<]*'   # should print the home page heading
```

If `curl` shows no `<h1>`, the SSR server isn't running or can't be reached. Check `storage/logs/ssr.log`.

## Admin area

| Route                                                | Purpose                                                                                                                                                           |
| ---------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `GET /admin/requests`                                | List requests, most recently active first. Filter by status, category or whether it's linked to an account, and search by title, suburb, postcode, name or email. |
| `GET /admin/requests/{id}`                           | A request with all its files, updates, notes and the latest 100 log entries                                                                                       |
| `PATCH /admin/requests/{id}`                         | Update the details, status or notes. The contact email can't be changed, because it's what links a request to an account.                                         |
| `DELETE /admin/requests/{id}`                        | Soft delete                                                                                                                                                       |
| `POST`, `PATCH`, `DELETE /admin/requests/{id}/files` | Upload, edit and delete files                                                                                                                                     |
| `POST`, `DELETE /admin/requests/{id}/messages`       | Post and delete updates                                                                                                                                           |
| `PUT`, `DELETE /admin/requests/{id}/vendor`          | Assign and remove the request's vendor                                                                                                                            |
| `/admin/vendors`                                     | List, add, edit and archive vendors                                                                                                                               |

### Vendors

Each request can have one vendor. General vendors take any job, and specialists have a list of the job types they cover. A specialist can still be assigned outside that list: the admin page shows a warning, and the log entry records it.

Archiving a vendor keeps it on the requests it's already assigned to, but it can't be assigned to any more. Customers never see vendors.

## File storage

The disk used for request files is set by `JOB_PHOTOS_DISK`:

- **Development:** `local`, which stores files in `storage/app/private`
- **Production:** `s3`, using DigitalOcean Spaces

To set up Spaces:

```dotenv
JOB_PHOTOS_DISK=s3
AWS_ACCESS_KEY_ID=        # Spaces access key
AWS_SECRET_ACCESS_KEY=    # Spaces secret key
AWS_DEFAULT_REGION=syd1   # the Space's region
AWS_BUCKET=               # the Space's name
AWS_ENDPOINT=https://syd1.digitaloceanspaces.com   # the region endpoint, not the bucket URL
AWS_USE_PATH_STYLE_ENDPOINT=false
```

Leave `AWS_URL` empty. Files are served through signed URLs that go directly to the Space, so you don't need a CDN URL. Keep the Space's file listing private.

## Development

```bash
composer test          # Pint check, PHPStan (level 7) and the test suite
composer lint          # fix code style with Pint
php artisan test       # run only the tests
```

The project is still in development, so schema changes go into the existing migrations and are applied with `php artisan migrate:fresh --seed`, rather than through new migrations that alter tables.
