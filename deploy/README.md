# GitHub Actions → cPanel FTP Deployment

Auto-deploys the Laravel API to cPanel shared hosting on every push to `main`.
No SSH or terminal access required — only FTP credentials.

---

## How it works

```
git push main
  │
  ├─ Install Composer deps (--no-dev --optimize-autoloader)
  ├─ Build Vite/Filament assets (npm run build)
  ├─ FTP upload — only changed files, .env is NEVER uploaded
  │   uses: SamKirkland/FTP-Deploy-Action
  │
  └─ POST https://yourapi.com/deploy/post-deploy.php
       ├─ Authenticate with DEPLOY_SECRET (read from server's .env)
       ├─ php artisan down
       ├─ php artisan migrate --force
       ├─ php artisan config:cache / route:cache / view:cache
       ├─ php artisan storage:link
       ├─ php artisan filament:upgrade
       └─ php artisan up
```

> **The `.env` file lives only on the server.** It is created once manually
> via cPanel File Manager and is never touched by CI. GitHub Secrets only
> hold FTP credentials and the deploy authentication secret.

---

## One-time setup

### 1. Create `.env` on the server

1. In cPanel → **File Manager**, navigate to your deployment directory (e.g. `/public_html/api/`)
2. Upload or create `.env` based on `.env.example`
3. Fill in all production values:

```dotenv
APP_NAME="KM Decor API"
APP_ENV=production
APP_KEY=base64:...          # php artisan key:generate --show
APP_DEBUG=false
APP_URL=https://api.kmdecor.com
FRONTEND_URL=https://kmdecor.com

LOG_CHANNEL=single
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=cpanelusername_kmdecor
DB_USERNAME=cpanelusername_kmduser
DB_PASSWORD=your_db_password

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

FILESYSTEM_DISK=local
MEDIA_DISK=uploads
FILAMENT_FILESYSTEM_DISK=uploads
UPLOADS_ROOT=/home/cpanelusername/public_html/api/public/uploads
UPLOADS_URL=https://api.kmdecor.com/uploads

MAIL_MAILER=smtp
MAIL_HOST=mail.kmdecor.com
MAIL_PORT=465
MAIL_USERNAME=noreply@kmdecor.com
MAIL_PASSWORD=your_mail_password
MAIL_FROM_ADDRESS="noreply@kmdecor.com"
MAIL_FROM_NAME="KM Decor"

SANCTUM_STATEFUL_DOMAINS=kmdecor.com,www.kmdecor.com

# Used to authenticate the post-deploy.php script
# Generate: openssl rand -hex 32
DEPLOY_SECRET=your_long_random_secret_here
```

---

### 2. GitHub Secrets

Go to **GitHub repo → Settings → Secrets and variables → Actions → New repository secret**.

Only these 6 secrets are needed — everything else lives in the server's `.env`:

| Secret | Example | Description |
|--------|---------|-------------|
| `FTP_HOST` | `ftp.kmdecor.com` | FTP hostname from cPanel → FTP Accounts |
| `FTP_USERNAME` | `deployer@kmdecor.com` | FTP username |
| `FTP_PASSWORD` | `ftppassword` | FTP password |
| `FTP_PORT` | `21` | `21` for FTP/FTPS, `22` for SFTP |
| `FTP_SERVER_DIR` | `/public_html/api/` | Remote path where files are uploaded |
| `APP_URL` | `https://api.kmdecor.com` | Used to call post-deploy.php after upload |
| `DEPLOY_SECRET` | `abc123...` (32+ chars) | Must match `DEPLOY_SECRET` in server's `.env` |

> **Tip:** Create a dedicated FTP sub-account in cPanel for deployments and
> restrict it to only the deployment directory.

---

### 3. cPanel: Create the MySQL database

1. cPanel → **MySQL Databases**
2. Create a new database, e.g. `cpanelusername_kmdecor`
3. Create a user, assign full privileges
4. Add the credentials to `.env` on the server (Step 1 above)

---

### 4. cPanel: Set the document root

Laravel must serve from `public/`, not the project root.

**Recommended — Addon domain or subdomain:**
1. cPanel → **Domains** or **Subdomains**
2. Point `api.kmdecor.com` document root → `/home/cpanelusername/public_html/api/public`

---

### 5. Make post-deploy.php reachable

The workflow calls:

```text
POST https://yourapi.com/deploy/post-deploy.php
```

The real script lives at `deploy/post-deploy.php`, above `public/`. The committed
`public/deploy/post-deploy.php` file is a tiny proxy that includes the real script,
so the URL works even when Laravel routes are cached.

This is safe because the real script validates `DEPLOY_SECRET` before doing
anything.

---

### 6. First deploy

On the very first deploy:
1. Set `dangerous-clean-slate: true` in `deploy.yml` (forces full upload)
2. Push to `main` and watch the Actions tab
3. After the first successful deploy, set `dangerous-clean-slate` back to `false`

Subsequent deploys only upload changed files — typically 2–5 minutes.

### 7. Seed data without SSH or cPanel Terminal

If the host disables SSH and Terminal, use GitHub Actions:

1. Go to **GitHub → Actions → Deploy to cPanel (FTP)**
2. Click **Run workflow**
3. Enable **Run database seeders after migration**
4. Click **Run workflow**

This sends `"seed": true` to the authenticated post-deploy script, which runs:

```bash
php artisan db:seed --force --no-interaction
```

Do not enable this checkbox for every deploy unless you intentionally want to
refresh seeded catalog/admin data. The seeders use `updateOrCreate` for core
data, but production content should still be treated carefully.

---

## File reference

| File | Purpose |
|------|---------|
| `.github/workflows/deploy.yml` | Main workflow — triggers on push to `main` |
| `deploy/post-deploy.php` | Runs migrations and rebuilds caches on the server |
| `.ftpignore` | Excludes dev files and `.env` from FTP upload |
| `.ftp-deploy-sync-state.json` | Auto-generated upload state file (not committed to git) |

---

## Troubleshooting

**Post-deploy returns 403**
- `DEPLOY_SECRET` in GitHub Secrets does not match the value in the server's `.env`
- Check for extra quotes or spaces in the `.env` value

**Post-deploy returns 500 "`.env` not found"**
- The `.env` file has not been created on the server yet — do Step 1 above

**Post-deploy returns 500 "PHP CLI not found"**
- This server uses `/usr/local/bin/ea-php83` (PHP 8.3, EasyApache, domain: api-kmd.kimmex.com.kh)
- That path is already first in the `$phpBinaries` list in `deploy/post-deploy.php`
- If the host changes the PHP version, check cPanel → MultiPHP Manager and update the path accordingly

**FTP upload is very slow on first deploy**
- Expected — it uploads `vendor/` (~100MB+) on first run
- Set `dangerous-clean-slate: true` for the first deploy only, then revert

**`storage:link` fails**
- Some shared hosts don't support symlinks
- Use `UPLOADS_ROOT` / `UPLOADS_URL` in `.env` to serve media from the uploads folder directly

**Migrations fail**
- Check the Actions log for the specific error
- Connect to the database via cPanel → phpMyAdmin to inspect the state
