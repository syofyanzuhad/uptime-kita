# Simple Uptime Monitoring

## ✅ Uptime Kita

<p align='center'>
  <!-- <a href='https://github.com/syofyanzuhad/uptime-kita'>
	  <img src='https://img.shields.io/endpoint?url=https%3A%2F%2Fhits.dwyl.com%2Fsyofyanzuhad%2Fuptime-kita.json%3Fcolor%3Dgreen'>
	</a> -->
  <a href="https://github.com/syofyanzuhad/uptime-kita/releases/latest">
    <img src="https://img.shields.io/github/v/release/syofyanzuhad/uptime-kita?color=blue&label=version" alt="Version" />
  </a>
  <a target="_blank" href="https://github.com/syofyanzuhad/uptime-kita">
    <img src="https://img.shields.io/github/last-commit/syofyanzuhad/uptime-kita" />
  </a>
  <a href="https://github.com/syofyanzuhad/uptime-kita/issues">
    <img alt="GitHub Issues or Pull Requests" src="https://img.shields.io/github/issues/syofyanzuhad/uptime-kita">
  </a>
  <a href="https://github.com/syofyanzuhad/uptime-kita/pulls">
	<img src="https://img.shields.io/github/issues-pr/syofyanzuhad/uptime-kita" alt="Pull Requests Badge"/>
  </a>
  <a href="https://github.com/syofyanzuhad/uptime-kita/graphs/contributors">
	<img alt="GitHub contributors" src="https://img.shields.io/github/contributors/syofyanzuhad/uptime-kita?color=2b9348">
  </a>
  <a href='https://github.com/syofyanzuhad/uptime-kita'>
    <img src="https://github.com/syofyanzuhad/uptime-kita/actions/workflows/tests.yml/badge.svg" />
  </a>
  <a href='https://github.com/syofyanzuhad/uptime-kita'>
    <img src="https://github.com/syofyanzuhad/uptime-kita/actions/workflows/pr-checks.yml/badge.svg" />
  </a>
  <a href="https://codecov.io/github/syofyanzuhad/uptime-kita" > 
    <img src="https://codecov.io/github/syofyanzuhad/uptime-kita/graph/badge.svg?token=K2BTV0DR25"/> 
  </a>
  <a href='https://github.com/syofyanzuhad/uptime-kita'>
	<img src='https://img.shields.io/github/forks/syofyanzuhad/uptime-kita'>
  </a>
  <a href='https://github.com/syofyanzuhad/uptime-kita'>
	<img src='https://img.shields.io/github/stars/syofyanzuhad/uptime-kita'>
  </a>
  <a href='https://github.com/syofyanzuhad/uptime-kita'>
    <img src="https://visitor-badge.laobi.icu/badge?page_id=syofyanzuhad.uptime-kita" />
  </a>
  <a href="https://madewithlaravel.com/p/uptime-kita/shield-link">
    <img src="https://madewithlaravel.com/storage/repo-shields/6285-shield.svg" alt="MadeWithLaravel.com shield">
  </a>
  <a href="https://madewithvuejs.com/p/uptime-kita/shield-link">
    <img src="https://madewithvuejs.com/storage/repo-shields/5900-shield.svg" alt="MadeWithVuejs.com shield">
  </a>
    <img src="https://uptime.syofyanzuhad.dev/badge/syofyanzuhad.dev" alt="Uptime" />
</p>

<img width="2880" height="2726" alt="uptime syofyanzuhad dev" src="https://github.com/user-attachments/assets/f6e52829-489f-4ffd-9b1c-c048abb1e588" />

"Kita" is the Indonesian word that means "Us"; this means that the uptime can be used for all of us

## 🥔 Live Demo

U can try the [uptime kita demo](https://uptime.syofyanzuhad.dev) (Server located on Germany)
- Status Page Demo: [Demo Status](https://uptime.syofyanzuhad.dev/status/demo)

## ⭐ Key Features

- 🔐 Google OAuth authentication & Role-based management
- ✅ Continuous HTTP/HTTPS uptime monitoring with custom intervals & latency tracking
- 🔌 **Public Developer Health Check API (v1)** (`/api/v1/check`) with rate limiting & SSRF protection
- 🖥️ **High-Density NOC Wallboard** (`/monitors`) with live dots, bars, sound alerts & URL filtering
- 🌐 **Domain Expiration Monitoring** (RDAP + WHOIS) with proactive threshold alerts
- 🔒 SSL Certificate validation and expiry tracking
- ✨ Modern, reactive glassmorphic UI/UX with dark/light mode and telemetry popover
- 📩 Multi-channel incident alerts: Email, Telegram, Slack, and Discord webhooks
- 🔔 Real-time status updates and incident toasts via Server-Sent Events (SSE)
- 📊 Public and private custom Status Pages (`/status/{slug}`)
- 📈 Real-time server resource monitoring (CPU, Memory, Disk, DB ping)
- 🏷️ Dynamic SVG uptime badges for README and website embeds
- 🗄️ Automated encrypted database backups (S3 / Local)
- 🐳 Docker & Docker Compose support for effortless deployment

## 🔌 Public Health Check API (v1)

Uptime Kita provides a public, rate-limited API for automating health and SSL checks in CI/CD pipelines, CLI tools, or custom scripts.

### Endpoint: `GET` / `POST` `/api/v1/check`
- **Rate Limit**: 30 requests / minute per IP
- **Security**: Strict SSRF guard (blocks loopback, private subnets, cloud metadata)

#### Query / Body Parameters:
| Parameter | Type | Required | Default | Description |
| :--- | :--- | :--- | :--- | :--- |
| `url` | `string` | **Yes** | — | Target URL or domain (e.g. `example.com` or `https://laravel.com`) |
| `check_ssl` | `boolean` | Optional | `true` | Inspect SSL certificate validity and expiration |
| `timeout` | `integer` | Optional | `10` | Request timeout in seconds (1 to 15) |

#### Quick `curl` Example:
```bash
curl -X GET "https://uptime.syofyanzuhad.dev/api/v1/check?url=example.com"
```

#### JSON Response (`200 OK`):
```json
{
  "ok": true,
  "status": "up",
  "status_code": 200,
  "response_time_ms": 118,
  "url": "https://example.com",
  "host": "example.com",
  "ip": "93.184.216.34",
  "ssl": {
    "valid": true,
    "issuer": "DigiCert Global G2 TLS RSA SHA256 2020 CA1",
    "subject": "example.com",
    "valid_from": "2026-03-01T00:00:00Z",
    "valid_to": "2026-11-25T23:59:59Z",
    "days_remaining": 90
  },
  "headers": {
    "content_type": "text/html; charset=UTF-8",
    "server": "ECS (dcb/7EA3)"
  },
  "checked_at": "2026-08-27T03:22:00Z"
}
```

## Requirements

- PHP ^8.2 · Node.js ^22 · Redis · SQLite (or MySQL/PostgreSQL)

## 🔧 Installation

### Option 1 — Laravel Cloud (recommended, zero ops)

No cron or Supervisor needed — Cloud handles scheduler + queue.

1. Push repo to GitHub and [create a project on Laravel Cloud](https://cloud.laravel.com).
2. Connect your repo → Cloud auto-detects Laravel.
3. Set env vars in **Cloud Dashboard → Environment**:
   ```bash
   APP_KEY=base64:...              # php artisan key:generate --show
   APP_URL=https://your-app.cloud.laravel.cloud
   ADMIN_EMAIL=admin@example.com
   ADMIN_PASSWORD=your-password
   SCHEDULE_FREQUENCY=hourly       # everyMinute | hourly | none (see Capacity below)
   GOOGLE_CLIENT_ID=               # optional — Google OAuth
   GOOGLE_CLIENT_SECRET=
   TELEGRAM_BOT_TOKEN=             # optional — Telegram alerts
   RESEND_API_KEY=                 # optional — email alerts
   AWS_ACCESS_KEY_ID=              # optional — S3 backups (else local disk)
   AWS_SECRET_ACCESS_KEY=
   AWS_BUCKET=
   ```
   Do **not** set `QUEUE_CONNECTION` manually — Cloud sets `cloud` automatically when you add a managed queue (next step).

4. **Add managed queue** (required for background jobs):
   - Cloud Dashboard → your Environment → canvas toolbar → **Add compute → Managed queue**
   - Name: `default` (or any name — first queue becomes the default)
   - Type: **Standard** · Memory: **256 MiB** (enough for notifications/stats) · Autoscaling: **0–3 workers** (Flex Starter limit)
   - Deploy. Cloud provisions the queue, sets `QUEUE_CONNECTION=cloud`, and autoscales workers to zero when idle.

   > Requires `aws/aws-sdk-php` in `composer.json` and Laravel ≥12.63 (this repo already satisfies both). Starter plan = 1 managed queue per environment (enough — all jobs use `default`). Verify in **Monitoring → Queues**.

5. Deploy. Migrations + seeders run automatically. Done.

> Cloud runs `php artisan schedule:run` every minute for you. Managed queue workers wake in <1s when jobs arrive and scale to zero when idle — no Horizon/Supervisor needed.

### Option 2 — VPS / Local (5 steps)

```bash
git clone https://github.com/syofyanzuhad/uptime-kita && cd uptime-kita
composer install && npm install && npm run build
cp .env.example .env && php artisan key:generate
# edit .env — set ADMIN_EMAIL, ADMIN_PASSWORD, and any optional keys above
touch database/database.sqlite database/queue.sqlite database/telescope.sqlite
php artisan migrate --seed --force
```

**Scheduler** (pick one):
```bash
# cron (VPS) — runs every minute, staggered hourly avoids thundering herd
crontab -e
# add: * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1

# cronless (Docker / no cron access)
php artisan schedule:run-cronless-safe --frequency=60
```

**Queue worker** (pick one):
```bash
# Horizon (recommended)
sudo apt-get install supervisor
# /etc/supervisor/conf.d/horizon.conf → command=php /path-to-project/artisan horizon
sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl start horizon

# Plain queue (no Horizon)
# /etc/supervisor/conf.d/laravel-worker.conf → command=php /path-to-project/artisan queue:work --sleep=3 --tries=3
```

> Tune check frequency via `SCHEDULE_FREQUENCY` in `.env` — see Capacity below.

### Option 3 — Docker

```bash
docker compose up -d          # production (nginx + php-fpm + supervisor + cronless)
docker compose --profile dev up -d  # dev with hot reload
```

Image includes nginx, PHP-FPM, Supervisor, cronless scheduler, and queue workers. No extra setup needed. See [TROUBLESHOOTING-CRONLESS.md](TROUBLESHOOTING-CRONLESS.md) for cronless options.

### Custom Seed Monitors (optional)

Edit `database/seeders/monitors/monitors.php` and `collages.php` before `php artisan migrate --seed`.

## 📊 Capacity Planning

How many monitors can one instance handle? Bottleneck is `monitor:check-uptime` (Guzzle concurrent pool + DB writes), not the queue.

**Config:** `concurrent_checks` in `config/uptime-monitor.php` (default `300`, lower to `80–100` on Flex Starter to avoid OOM), `timeout_per_site=10s`, `run_interval_in_minutes=5`.

| `SCHEDULE_FREQUENCY` | Effective check | Safe monitors (Flex Starter ~1 vCPU/1GB) | Notes |
|---|---|---|---|
| `everyMinute` | every 5 min* | **800–1.5k** | ~19s/run at 1.5k, fastest detection |
| `hourly` | every 60 min | **3k–6k** | ~75s/run at 6k, DB-heavy, staggered 0/5/10/12/15/20/30 |
| `none` | disabled | — | manual `php artisan monitor:check-uptime` only |

\* Spatie filters by `run_interval_in_minutes=5` even when scheduler fires every minute. Set `UPTIME_MINIMUM_CHECK_INTERVAL=1` + `run_interval=1` to force true 1-min checks (300–600 monitors max).

**When to scale:**
- >1.5k monitors needing <5 min detection → Flex Pro or dedicated worker.
- `withoutOverlapping(10)` skips next run if previous >10 min → bump instance size.
- Watch `php artisan schedule:list`, Horizon metrics, and DB slow log.

**Tuning for Flex Starter:** set `concurrent_checks` to `80` in `config/uptime-monitor.php` (300 × 5 MB/handle ≈ 1.5 GB > RAM).

## 🤝 Contributing

We welcome contributions! See [CONTRIBUTING.md](CONTRIBUTING.md).

## 📈 Server Resources Monitoring

Monitor your server's health in real-time from the Settings page.

### Features
- **CPU Usage**: Current usage percentage and core count
- **Memory**: Total, used, and free memory with usage percentage
- **Disk**: Storage usage for the application directory
- **Server Uptime**: How long the server has been running
- **Load Average**: 1, 5, and 15-minute load averages
- **PHP Info**: Version, memory limit, loaded extensions
- **Laravel Info**: Version, environment, debug mode status
- **Database**: Connection status and size
- **Queue**: Driver, pending and failed jobs count
- **Cache**: Driver and status

### Access
Navigate to **Settings > Server Resources** to view the monitoring dashboard.

The page auto-refreshes every 5 seconds (configurable), with color-coded progress bars:
- 🟢 Green: < 70% usage
- 🟡 Yellow: 70-90% usage
- 🔴 Red: > 90% usage

## 🏷️ Embed Status Badge

Showcase your real-time service availability with dynamic SVG status badges in your GitHub `README.md`, personal portfolio, or documentation site.

### Badge Endpoint:
```
https://uptime.syofyanzuhad.dev/badge/{domain}
```

### Customization Parameters

| Parameter | Type | Default | Options / Description |
| :--- | :--- | :--- | :--- |
| `period` | `string` | `24h` | `24h`, `7d`, `30d`, `90d` — Time calculation range |
| `style` | `string` | `flat` | `flat`, `flat-square`, `for-the-badge`, `plastic` |
| `label` | `string` | `uptime` | Custom label text (e.g. `API Uptime`, `System Health`) |
| `show_period` | `boolean` | `true` | When `false`, hides the `24h`/`7d` suffix from the badge label |

---

### Badge Style Examples

| Style | Preview | Query Parameter |
| :--- | :--- | :--- |
| **Flat (Default)** | ![Flat Badge](https://uptime.syofyanzuhad.dev/badge/syofyanzuhad.dev?style=flat) | `?style=flat` |
| **Flat Square** | ![Square Badge](https://uptime.syofyanzuhad.dev/badge/syofyanzuhad.dev?style=flat-square) | `?style=flat-square` |
| **For The Badge** | ![For The Badge](https://uptime.syofyanzuhad.dev/badge/syofyanzuhad.dev?style=for-the-badge) | `?style=for-the-badge` |
| **Plastic** | ![Plastic Badge](https://uptime.syofyanzuhad.dev/badge/syofyanzuhad.dev?style=plastic) | `?style=plastic` |

---

### Time Period Examples

| Range | Preview | Query Parameter |
| :--- | :--- | :--- |
| **Last 24 Hours** | ![24h Uptime](https://uptime.syofyanzuhad.dev/badge/syofyanzuhad.dev?period=24h) | `?period=24h` |
| **Last 7 Days** | ![7d Uptime](https://uptime.syofyanzuhad.dev/badge/syofyanzuhad.dev?period=7d) | `?period=7d` |
| **Last 30 Days** | ![30d Uptime](https://uptime.syofyanzuhad.dev/badge/syofyanzuhad.dev?period=30d) | `?period=30d` |
| **Last 90 Days** | ![90d Uptime](https://uptime.syofyanzuhad.dev/badge/syofyanzuhad.dev?period=90d) | `?period=90d` |

---

### Custom Label Examples

| Use Case | Preview | URL |
| :--- | :--- | :--- |
| **Custom Label** | ![API Health](https://uptime.syofyanzuhad.dev/badge/syofyanzuhad.dev?label=API%20Health&show_period=false) | `?label=API%20Health&show_period=false` |
| **Clean Uptime** | ![Clean Uptime](https://uptime.syofyanzuhad.dev/badge/syofyanzuhad.dev?label=uptime&show_period=false) | `?show_period=false` |
| **Combined** | ![Combined](https://uptime.syofyanzuhad.dev/badge/syofyanzuhad.dev?label=Core%20API&period=30d&style=for-the-badge) | `?label=Core%20API&period=30d&style=for-the-badge` |

---

### Ready-to-Use Embed Code Snippets

#### 1. Clickable Markdown (Recommended for GitHub READMEs):
```markdown
[![Service Uptime](https://uptime.syofyanzuhad.dev/badge/syofyanzuhad.dev)](https://uptime.syofyanzuhad.dev/m/syofyanzuhad.dev)
```

#### 2. Clickable HTML (For Websites & Dashboards):
```html
<a href="https://uptime.syofyanzuhad.dev/m/syofyanzuhad.dev" target="_blank" rel="noopener noreferrer">
  <img src="https://uptime.syofyanzuhad.dev/badge/syofyanzuhad.dev?period=30d&style=flat-square" alt="Service Uptime" />
</a>
```

#### 3. Automatic Health Threshold Colors:
- 🟢 **Bright Green (`#4c1`)**: Uptime ≥ 99.0%
- 🟢 **Green (`#97ca00`)**: Uptime ≥ 97.0%
- 🟡 **Yellow-Green (`#a4a61d`)**: Uptime ≥ 95.0%
- 🟡 **Yellow (`#dfb317`)**: Uptime ≥ 90.0%
- 🟠 **Orange (`#fe7d37`)**: Uptime ≥ 80.0%
- 🔴 **Red (`#e05d44`)**: Uptime < 80.0%

## 🖼️ Dynamic Open Graph (OG) Images

Uptime Kita automatically generates dynamic and beautiful Open Graph (OG) images for your public monitors and status pages. When you share a monitor link on social media platforms (Twitter/X, Discord, Slack, etc.), it displays a custom image containing real-time information about your service.

### Features
- **Monitor Specific**: Automatically generates unique OG images using the monitor's URL (e.g., `https://uptime-instance.com/og/monitor/example.com.png`).
- **Status Pages**: Creates tailored OG images for your aggregated status pages.
- **Real-time Data**: Reflects the actual status of the monitor when shared.

## 🔔 Real-time Status Notifications

Public monitor pages display instant toast notifications when a monitor's status changes (up → down or down → up).

### How It Works

The feature uses **Server-Sent Events (SSE)** for efficient real-time updates:

1. When a monitor status changes, the event is broadcast to connected clients
2. Public pages automatically subscribe to the SSE stream
3. Toast notifications appear instantly without page refresh
4. Connections auto-reconnect with exponential backoff if disconnected

### Supported Pages

- **Public Monitor List** (`/monitors/public`) - All public monitors
- **Public Monitor Detail** (`/monitors/{id}/public`) - Specific monitor
- **Status Pages** (`/status/{slug}`) - Monitors on that status page

### Toast Appearance

- 🟢 **Green toast**: Service recovered (down → up)
- 🔴 **Red toast**: Service down (up → down)
- Auto-dismiss after 8 seconds with progress bar
- Manual dismiss via close button

### Technical Details

- SSE endpoint: `/api/monitor-status-stream`
- Heartbeat: Every 30 seconds
- Max connection duration: 5 minutes (auto-reconnect)
- Rate limited: 10 requests per minute

## 🛣️ Roadmap

- [x] Uptime monitoring
- [x] SSL Monitoring
- [x] Domain expiration monitoring
- [x] Monitoring history
- [x] Notification:
  - [x] Email
  - [x] Telegram
  - [x] Slack
  - [ ] Discord
- [x] Status page
- [x] Docker deployment
- [x] Server resources monitoring
- [x] Uptime badge for embedding
- [x] Automatic database backups
- [x] Cronless scheduler for container environments
- [x] Real-time toast notifications via SSE
- [ ] Do you have any suggestions?

## 📸 Screenshots

### Private monitors
<img width="2880" height="2168" alt="uptime syofyanzuhad private_monitor_1" src="https://github.com/user-attachments/assets/57db5086-351d-43a5-aba4-47ede7b33eda" />

<img width="2048" height="1844" alt="uptime syofyanzuhad private_dashboard" src="https://github.com/user-attachments/assets/812d9b8a-869d-4f7d-9e54-828c1e41a27c" />

### Public monitors
<img width="2880" height="2168" alt="uptime syofyanzuhad public_monitor_1" src="https://github.com/user-attachments/assets/316f10f1-945d-45f1-94d3-ae100321da68" />

<img width="2048" height="1844" alt="uptime syofyanzuhad public_dashboard" src="https://github.com/user-attachments/assets/f591c415-01d0-430d-b0b6-6d11fb57c027" />

### Detail Monitor
<img width="2880" height="2168" alt="uptime syofyanzuhad detail_monitor" src="https://github.com/user-attachments/assets/54fec7e1-e152-46c9-8058-b67a887500dd" />

### Status Page
<img width="2880" height="2168" alt="uptime syofyanzuhad detail_status_monitor" src="https://github.com/user-attachments/assets/83f154d2-4bda-4c6e-b143-cc1ce1bb8231" />

<img width="2048" height="1844" alt="uptime syofyanzuhad public_status_syofyan-zuhad" src="https://github.com/user-attachments/assets/3059f9f1-c98d-4c8b-a6e2-3e405021523f" />

### Dynamic OG Image
<img width="500" height="402" alt="image" src="https://github.com/user-attachments/assets/252bde13-55e4-4ae4-b4d5-8aa7216ca1fd" />

<img width="500" height="372" alt="image" src="https://github.com/user-attachments/assets/c3fc4c44-9c0c-483a-a40a-c282c26ea33c" />

### Notification
<img width="2048" height="1844" alt="uptime syofyanzuhad dev_status-pages_1" src="https://github.com/user-attachments/assets/f1ebd743-3003-46e0-aba2-5bb6713084cf" />

<img width="500" height="893" alt="Screenshot 2025-07-22 at 09 18 08" src="https://github.com/user-attachments/assets/ddfb62da-bacb-4a5e-ba8f-c0005114bd08" />

<img width="500" height="800" alt="image" src="https://github.com/user-attachments/assets/8f99a8ec-0462-44e2-8989-94eac140ea2c" />

## 📊 Activity
![Alt](https://repobeats.axiom.co/api/embed/3eda9cccaaf42702c26eea2632ce37357c315dc7.svg "Repobeats analytics image")

## ⭐️ Star History

<a href="https://www.star-history.com/#syofyanzuhad/uptime-kita&Date">
 <picture>
   <source media="(prefers-color-scheme: dark)" srcset="https://api.star-history.com/svg?repos=syofyanzuhad/uptime-kita&type=Date&theme=dark" />
   <source media="(prefers-color-scheme: light)" srcset="https://api.star-history.com/svg?repos=syofyanzuhad/uptime-kita&type=Date" />
   <img alt="Star History Chart" src="https://api.star-history.com/svg?repos=syofyanzuhad/uptime-kita&type=Date" />
 </picture>
</a>

---

Uptime Kita is an open-sourced software licensed under the [Apache-2.0](https://github.com/syofyanzuhad/uptime-kita/LICENSE)
