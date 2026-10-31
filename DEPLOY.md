# Deploying LabFix (free demo) — Render + TiDB Cloud + Cloudflare R2

This app is a **stateful** Laravel 12 app (Inertia + Vue), so it runs on **Render**
(Docker), with an external **MySQL** (TiDB Cloud) and **S3-compatible** upload store
(Cloudflare R2). The auto-escalate / auto-close commands are triggered by an external
scheduler hitting two token-guarded endpoints.

```
Browser / Expo app
        │  https
        ▼
   Render web service (Docker: nginx + php-fpm)
        │                │                 │
        ▼                ▼                 ▼
  TiDB Cloud       Cloudflare R2      cron-job.org
  (MySQL)          (uploads)          (hourly/daily ping → /cron/*)
```

Rationale for the odd bits:
- **R2/RDS instead of local disk** — Render's free disk is ephemeral.
- **External cron** — Render's free tier has no cron/worker.
- **`/cron/*` endpoints hit one command each** — `schedule:run` skips a task that
  isn't due at that exact minute, so a slightly-late ping would silently do nothing.

---

## 0. Prerequisites
- The repo on GitHub (public or private).
- A Render account, a Cloudflare account, a TiDB Cloud account, a cron-job.org account.

---

## 1. Database — TiDB Cloud Starter (formerly "Serverless")
> TiDB renamed its tiers. What older docs call **Serverless** is today's **Starter**
> ("POWERED BY TiDB X"). Essential / Premium / Dedicated are the paid tiers — ignore them.
1. Sign up at TiDB Cloud (email or GitHub — no card for the free tier).
2. **Create Resource → Starter**, set **Compatibility Mode = MySQL Compatible**, give it a
   name (e.g. `labfix`), pick the region nearest you. Confirm the summary shows
   `$0 / month`, then create.
3. Open the instance → **Connect** and note:
   - **Host** (e.g. `gateway01.<region>.prod.aws.tidbcloud.com`)
   - **Port**: `4000`
   - **User** + password (create a SQL user if needed)
4. In the cluster's SQL editor run: `CREATE DATABASE labfix;`
5. TLS is required. The container already trusts the public CA bundle, so the only
   thing you set for this is `MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca-certificates.crt`
   (see §3). 

Values to keep: `DB_HOST`, `DB_PORT`, `DB_DATABASE=labfix`, `DB_USERNAME`, `DB_PASSWORD`.

## 2. Uploads — Cloudflare R2
1. Cloudflare dashboard → **R2** → enable (it may ask for a card; the free allowance still applies).
2. **Create bucket** — e.g. `labfix-uploads`.
3. **R2 → Manage API Tokens → Create API token**, permission *Object Read & Write*. Copy the
   **Access Key ID** and **Secret Access Key**.
4. Note the **S3 endpoint**: `https://<account-id>.r2.cloudflarestorage.com`.

Values to keep: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_BUCKET`, `AWS_ENDPOINT`.

## 3. Host — Render
1. **New → Web Service → Build and deploy from a Git repository** → pick this repo.
2. Settings:
   - **Language/Runtime:** Docker
   - **Root Directory:** `backend`
   - **Dockerfile Path:** `backend/Dockerfile` (Render fills this once Root Directory is set)
   - **Instance Type:** Free
3. **Environment → Add** the following (secrets you paste; the rest are literals):

   | Key | Value |
   |---|---|
   | `APP_NAME` | `LabFix` |
   | `APP_ENV` | `production` |
   | `APP_DEBUG` | `false` |
   | `APP_KEY` | output of `php artisan key:generate --show` (run locally) |
   | `APP_URL` | `https://<your-service>.onrender.com` |
   | `LOG_CHANNEL` | `stderr` |
   | `QUEUE_CONNECTION` | `sync` |
   | `SESSION_DRIVER` | `database` |
   | `CACHE_STORE` | `database` |
   | `MAIL_MAILER` | `log` |
   | `DB_CONNECTION` | `mysql` |
   | `DB_HOST` | TiDB host |
   | `DB_PORT` | `4000` |
   | `DB_DATABASE` | `labfix` |
   | `DB_USERNAME` | TiDB user |
   | `DB_PASSWORD` | TiDB password |
   | `MYSQL_ATTR_SSL_CA` | `/etc/ssl/certs/ca-certificates.crt` |
   | `FILESYSTEM_PUBLIC_DRIVER` | `s3` |
   | `AWS_ACCESS_KEY_ID` | R2 key |
   | `AWS_SECRET_ACCESS_KEY` | R2 secret |
   | `AWS_DEFAULT_REGION` | `auto` |
   | `AWS_BUCKET` | `labfix-uploads` |
   | `AWS_ENDPOINT` | `https://<account-id>.r2.cloudflarestorage.com` |
   | `AWS_USE_PATH_STYLE_ENDPOINT` | `true` |
   | `CRON_SECRET` | a long random string you make up |

4. **Deploy**. The entrypoint runs migrations automatically, so watch the logs for
   `migrate` + the supervisor starting nginx/php-fpm.
5. Copy the service URL (`https://<your-service>.onrender.com`) and make sure it matches `APP_URL`.

## 4. Scheduler — cron-job.org
1. Sign up (free).
2. **Create cronjob**:
   - URL: `https://<your-service>.onrender.com/cron/escalate`
   - Schedule: **every hour** (`0 * * * *`)
   - **Advanced → Headers**: add `X-Cron-Token` = your `CRON_SECRET`
3. Create a second job:
   - URL: `https://<your-service>.onrender.com/cron/close`
   - Schedule: **daily** (e.g. `0 0 * * *`), same header.
4. Use **Test run** on each — you want a `200` with command output. A `403` means the
   header/token is wrong.

## 5. Demo data (optional, one time)
The app needs at least one superadmin. In Render → your service → **Shell**:
```
php artisan db:seed --force
```
This seeds the demo institutions/users/equipment (see `database/seeders`). Run it once;
it is **not** run automatically (so redeploys don't re-seed).

## 6. Mobile app
Point it at the deployed URL and rebuild — no code change needed:
```
EXPO_PUBLIC_API_URL=https://<your-service>.onrender.com
```
Build with your normal Expo/EAS flow.

## 7. Smoke test
- [ ] `/` and `/login` load (allow ~50s for the first hit — the free service cold-starts).
- [ ] Log in with a seeded account.
- [ ] Submit a report **with a voice note** → it plays back (proves R2 + `/storage/{path}`).
- [ ] `curl -H "X-Cron-Token: <secret>" https://<app>/cron/escalate` → `200`.

---

## Troubleshooting
- **`403` from `/cron/*`** — `CRON_SECRET` on Render doesn't match the header cron-job.org sends.
- **Uploads 404 / don't persist** — check `FILESYSTEM_PUBLIC_DRIVER=s3` and the R2 keys; R2 must allow *Object Read & Write*.
- **DB connection refused / TLS error** — confirm `DB_PORT=4000` and `MYSQL_ATTR_SSL_CA` is set to the system bundle path.
- **Config changes ignored** — env is baked at boot; **redeploy** after changing an env var.
- **First request very slow** — expected: the free instance sleeps when idle (the hourly cron ping keeps it warmer).

## Local parity (unchanged)
Local dev keeps using the local disk and the on-machine DB. Nothing here alters your
`.env`; the production differences are all supplied as Render env vars, or defaulted
safely (`FILESYSTEM_PUBLIC_DRIVER` unset → local disk).
