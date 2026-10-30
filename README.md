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

| Email | Role |
|---|---|
| `admin@example.com` | Admin |
| `test@example.com` | Customer with requests at different stages, plus one unlinked request that's linked when they log in |

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

| | Customers see | Admins see |
|---|---|---|
| Files | Their own photos and files shared with them | Everything |
| Updates | Shared updates, without the author's name | Everything, with the author |
| Admin notes | Never | Yes |
| Activity log | Never | Yes |

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
- request deletions

Logging a change also updates the request's `last_activity_at`, which is how the admin list shows the most recently updated requests first.

## Admin area

| Route | Purpose |
|---|---|
| `GET /admin/requests` | List requests, most recently active first. Filter by status, category or whether it's linked to an account, and search by title, suburb, postcode, name or email. |
| `GET /admin/requests/{id}` | A request with all its files, updates, notes and the latest 100 log entries |
| `PATCH /admin/requests/{id}` | Update the details, status or notes. The contact email can't be changed, because it's what links a request to an account. |
| `DELETE /admin/requests/{id}` | Soft delete |
| `POST`, `PATCH`, `DELETE /admin/requests/{id}/files` | Upload, edit and delete files |
| `POST`, `DELETE /admin/requests/{id}/messages` | Post and delete updates |

The React pages for the admin area haven't been built yet.

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
