# Media Storage

KM Decor should keep uploaded storefront media backend-owned and portable. The backend currently supports local public storage and S3-compatible storage through Laravel disks.

## Recommended Current cPanel Setup

Use Laravel public storage:

```env
FILESYSTEM_DISK=public
MEDIA_DISK=public
FILAMENT_FILESYSTEM_DISK=public
MEDIA_CONVERSIONS_DISK=
```

Run the deploy workflow so `php artisan storage:link` is executed by `deploy/post-deploy.php`.

Public media URLs will resolve under:

```txt
{APP_URL}/storage/...
```

## Admin Upload Policy

Use Spatie Media Library collections for admin-managed visual content:

| Content | Collection | Notes |
| --- | --- | --- |
| Brand logo | `logo` | Used by `/api/brands` and `/api/home`. |
| Service image | `images` | First image is exposed as `image_url`. |
| Service portfolio | `portfolio` | Exposed on service detail as `portfolio_images`. |
| Project gallery | `gallery` | First image is exposed as `primary_image`. |
| Product gallery | Product images remain supported through the product image relation. |

Legacy string fields such as `logo_url` and `image_url` are still read as fallbacks, so existing seeded or imported content does not break.

## Future S3 or Cloudflare R2 Setup

When cPanel storage becomes limiting, move media to S3-compatible storage by changing env only:

```env
MEDIA_DISK=s3
FILAMENT_FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=auto
AWS_BUCKET=...
AWS_URL=https://cdn.example.com
AWS_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=true
```

For AWS S3, use the real region and leave `AWS_ENDPOINT` empty unless a custom endpoint is required.

## Deployment Checks

After uploading media in admin, check:

```bash
curl -sS https://your-api-domain/api/home
curl -sS https://your-api-domain/api/services
curl -sS https://your-api-domain/api/brands
```

The returned `image_url`, `logo_url`, and `primary_image` values should be public HTTPS URLs before the frontend is deployed to production.
