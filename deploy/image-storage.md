# Image Storage

The app supports three upload storage modes without frontend changes. APIs return public image URLs, so the storefront only consumes URL fields.

## No SSH cPanel

Use direct public uploads. No `php artisan storage:link` is required.

```env
FILESYSTEM_DISK=local
MEDIA_DISK=uploads
FILAMENT_FILESYSTEM_DISK=uploads
UPLOADS_ROOT=/home/ACCOUNT/public_html/uploads
UPLOADS_URL=https://example.com/uploads
```

If Laravel's `public` directory is already the web root, `UPLOADS_ROOT` and `UPLOADS_URL` can stay empty and default to `public/uploads`.

## Local Development

Use the same cPanel-safe path:

```env
FILESYSTEM_DISK=local
MEDIA_DISK=uploads
FILAMENT_FILESYSTEM_DISK=uploads
```

Or use Laravel's normal public disk when `storage:link` is available:

```env
FILESYSTEM_DISK=local
MEDIA_DISK=public
FILAMENT_FILESYSTEM_DISK=public
```

## S3 / R2 / Spaces

Switch media uploads to S3-compatible storage:

```env
MEDIA_DISK=s3
FILAMENT_FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=auto
AWS_BUCKET=...
AWS_URL=https://cdn.example.com
AWS_ENDPOINT=https://ACCOUNT_ID.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=true
```

Keep existing uploaded files in their original disk unless you intentionally migrate them.
