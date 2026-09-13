# Uptime Kita — Improvement Roadmap

> Generated: 2026-09-11 · Based on full code audit of the Laravel 12 codebase.

---

## Table of Contents

1. [Phase 1 — Security & Reliability (Critical)](#phase-1--security--reliability-critical)
2. [Phase 2 — Weekly Report for Active Monitors](#phase-2--weekly-report-for-active-monitors)
3. [Phase 3 — Feature Improvements](#phase-3--feature-improvements)
4. [Phase 4 — Developer Experience](#phase-4--developer-experience)
5. [Phase 5 — Growth & Ecosystem](#phase-5--growth--ecosystem)
6. [Suggested Execution Order](#suggested-execution-order)

---

## Phase 1 — Security & Reliability (Critical)

These are the highest-priority items. Most are under 1–2 hours of work and directly affect security, data integrity, or production stability.

---

### 1.1 Telegram Webhook — Add Signature Verification

**Problem:** `POST /webhook/telegram` in `routes/web.php` only has `throttle:60,1` protection. Any external party can POST arbitrary payloads to the endpoint. Current handler is low-risk (only reads `/start` to return chat ID), but any future logic inherits the same exposure.

**Fix:**
1. When registering the webhook via Telegram's `setWebhook` API, pass a `secret_token` parameter.
2. Create `app/Http/Middleware/VerifyTelegramWebhookSignature.php`:
   - Read the `X-Telegram-Bot-Api-Secret-Token` header.
   - Compare it (constant-time) against `config('services.telegram.webhook_secret')`.
   - Return `403` if missing or mismatched.
3. Register the middleware in `bootstrap/app.php` and apply it to the `webhook/telegram` route.
4. Add `TELEGRAM_WEBHOOK_SECRET` to `.env.example`.

**Effort:** ~1–2 hours · **Tests:** Add a feature test for `TelegramWebhookController` covering the authorized and unauthorized path.

---

### 1.2 Enable & Schedule Automated Database Backups

**Problem:** `routes/console.php` has the Spatie backup schedule commented out. The app monitors everyone else's uptime — losing its own database would be ironic.

```php
// Currently commented out in routes/console.php:
// Schedule::command('backup:clean')->daily()->at('01:00');
// Schedule::command('backup:run')->daily()->at('01:30');
```

**Fix:**
1. Configure a backup destination disk in `config/filesystems.php` (S3, R2, or Google Drive — set `BACKUP_DESTINATION_*` env vars).
2. Uncomment the two schedule lines.
3. Optionally add `backup:monitor` to alert if a backup is missed or oversized.
4. For Laravel Cloud: Cloud snapshots cover the primary DB, but `database/queue.sqlite` and `database/telescope.sqlite` need explicit inclusion in `config/backup.php`'s `databases` array.

**Effort:** 30 min (config-only if the disk is already set up) · **Tests:** Verify `backup:run` completes without error in CI using `--only-to-disk=local`.

---

### 1.3 Disable `trace-replay` in Production

**Problem:** `iazaran/trace-replay` defaults to `TRACE_REPLAY_ENABLED=true` and `sample_rate=1.0`, tracing 100% of HTTP requests and every queued job. It writes rows into `tr_traces`, `tr_trace_steps`, `tr_projects`, `tr_workspaces` on the main DB — this is what caused the Slackbot crawler hit accumulation.

**Fix (immediate — 5 minutes):**
1. Set `TRACE_REPLAY_ENABLED=false` in the Laravel Cloud production environment dashboard.
2. Prune stale rows: `php artisan trace-replay:prune` or `TRUNCATE tr_traces, tr_trace_steps;`.

**If you want to keep it for debugging:**
- Set `sample_rate=0.1` (10% of requests) in `config/trace-replay.php`.
- Set `TRACE_REPLAY_AUTO_TRACE_JOBS=false`.
- This cuts storage growth by ~90%.

**Effort:** 5 min · **No tests needed** (config-only change).

---

### 1.4 Fix `.env.example` Queue Connection Default

**Problem:** `.env.example` defaults to `QUEUE_CONNECTION=database`, but the app is designed for Redis (`predis` is installed, config is wired). New environments provisioned from the example silently use the database driver and miss Horizon's queue introspection.

**Fix:**
- Change `.env.example` to `QUEUE_CONNECTION=redis`.
- Add a comment: `# Laravel Cloud overrides this with "cloud" automatically when a managed queue is added.`

**Effort:** 2 min.

---

## Phase 2 — Weekly Report for Active Monitors

This is a new feature that sends users a weekly digest of their monitor performance. It uses data already available in `MonitorStatistic`, `MonitorHistory`, and `MonitorIncident`.

---

### 2.1 Overview

Every Monday at 08:00 (user's local time, or a configurable timezone), users who have **at least one active monitor** receive a "Weekly Uptime Report" via their enabled notification channels (email primary, optional Telegram/Slack/Discord).

The report covers the **previous Monday → Sunday** window and includes:

- Overall fleet health (X of Y monitors up right now)
- Per-monitor summary: uptime %, avg response time, incident count
- Worst performer of the week (most downtime)
- Best performer of the week (fastest average response)
- Total downtime minutes across all monitors
- Trend vs. the previous week (↑ / ↓ / = for uptime %)
- Direct links to each monitor detail page

---

### 2.2 Implementation Plan

#### Step 1 — Migration: `weekly_report_preference` on users

Add a `weekly_report_enabled` boolean column (default `true`) and `weekly_report_timezone` string (default `UTC`) to the `users` table. This lets users opt out or pick their timezone.

```bash
php artisan make:migration add_weekly_report_preference_to_users_table --no-interaction
```

```php
// Migration up():
$table->boolean('weekly_report_enabled')->default(true)->after('remember_token');
$table->string('weekly_report_timezone', 64)->default('UTC')->after('weekly_report_enabled');
```

#### Step 2 — Notification: `WeeklyMonitorReport`

Create `app/Notifications/WeeklyMonitorReport.php` implementing `ShouldQueue`. It should:
- Accept a `User $user` and a `Collection $monitorSummaries` (pre-computed data array).
- Implement `toMail()` using a `resources/views/emails/weekly-report.blade.php` template with a styled HTML table.
- Implement `toTelegram()` with a Markdown summary (top 5 monitors + totals).
- Implement `toArray()` for the database channel (optional, for a future notification centre).
- Respect existing `EmailRateLimitService` and `TelegramRateLimitService` patterns.

The mail template should include:
- Header: "Your Weekly Uptime Report — Week of {date}"
- Summary table: Monitor name | Uptime % | Avg ms | Incidents | Trend
- "Worst performer" callout box (red border)
- "Best performer" callout box (green border)
- Footer: link to dashboard + unsubscribe preference link

#### Step 3 — Job: `SendWeeklyMonitorReportJob`

Create `app/Jobs/SendWeeklyMonitorReportJob.php`:

```php
// Pseudo-structure
class SendWeeklyMonitorReportJob implements ShouldQueue
{
    public function __construct(public User $user) {}

    public function handle(): void
    {
        // 1. Load user's active monitors with their MonitorStatistic
        $monitors = $this->user->monitors()
            ->where('uptime_check_enabled', true)
            ->with('statistic')
            ->get();

        if ($monitors->isEmpty()) {
            return; // No active monitors — skip silently
        }

        // 2. Build per-monitor summaries for the last 7 days
        //    using MonitorStatistic (uptime_7d, avg_response_time_24h, incidents_7d)
        //    and MonitorHistory for trend vs. previous week

        // 3. Compute fleet-level totals

        // 4. Dispatch the notification
        $this->user->notify(new WeeklyMonitorReport($monitors, $summaries));
    }
}
```

#### Step 4 — Schedule the Job

In `routes/console.php`, dispatch one job per eligible user:

```php
Schedule::call(function () {
    User::query()
        ->where('weekly_report_enabled', true)
        ->whereHas('monitors', fn ($q) => $q->where('uptime_check_enabled', true))
        ->chunk(100, function ($users) {
            foreach ($users as $user) {
                SendWeeklyMonitorReportJob::dispatch($user)
                    ->onQueue('notifications');
            }
        });
})->weeklyOn(1, '08:00') // Every Monday at 08:00
  ->name('send-weekly-monitor-reports')
  ->withoutOverlapping();
```

#### Step 5 — User Preference UI

Add a "Weekly Report" toggle to the existing notification settings page (`Settings/NotificationController`):

- Toggle: "Receive weekly uptime summary" (on/off)
- Timezone selector: dropdown of PHP timezones (pre-filled from `weekly_report_timezone`)
- Save button (PATCH to existing settings endpoint or new `Settings/WeeklyReportController`)

#### Step 6 — Unsubscribe Link

Include a signed URL in the email footer using `URL::signedRoute('settings.weekly-report.unsubscribe', ['user' => $user->id])`. Visiting it sets `weekly_report_enabled = false` — no login required.

---

### 2.3 Weekly Report Data Structure

```php
// Per-monitor summary shape (passed to the notification)
[
    'monitor_id'          => int,
    'display_name'        => string,
    'url'                 => string,
    'uptime_7d'           => float,   // e.g. 99.87
    'uptime_7d_prev'      => float,   // previous week, for trend arrow
    'avg_response_ms'     => int,
    'incidents_7d'        => int,
    'total_downtime_mins' => int,
    'is_up_now'           => bool,
    'monitor_url'         => string,  // link to /monitors/{id}
]
```

---

### 2.4 Tests to Write

| Test | File |
|---|---|
| Users with no active monitors do NOT receive the notification | `SendWeeklyMonitorReportJobTest` |
| Users with `weekly_report_enabled = false` are skipped | `SendWeeklyMonitorReportJobTest` |
| Notification renders correct uptime % and trend arrow | `WeeklyMonitorReportTest` |
| Unsubscribe signed URL disables the report | `WeeklyReportUnsubscribeTest` |
| Schedule fires on Monday at 08:00 | `ScheduleTest` (assert `Schedule::call` is registered) |
| Email contains "worst performer" callout | `WeeklyMonitorReportTest` |

---

## Phase 3 — Feature Improvements

Medium-effort features that make the product meaningfully more useful.

---

### 3.1 Monitor Tags / Groups

**Status:** `HasTags` trait is already on the `Monitor` model (via `spatie/laravel-tags`). The `TagController` exists. UI implementation is missing.

**Plan:**
1. Add a tag input (multi-select) to the monitor create/edit form in Vue.
2. Filter the dashboard monitor list by tag via a tag pill filter bar.
3. On the NOC wallboard (`/monitors`), support filtering by tag via URL param: `/monitors?tag=production`.
4. Wayfinder: update `TagController` to expose a `GET /tags` index endpoint for the autocomplete.

**Effort:** ~4–6 hours (mostly frontend).

---

### 3.2 Maintenance Window — CI/CD API

**Status:** `MaintenanceWindowService` (11KB) is fully implemented server-side. No API endpoint exists.

**Plan:**
1. Create `app/Http/Controllers/Api/MaintenanceWindowController.php` with:
   - `POST /api/v1/maintenance` — start a maintenance window for a monitor (bearer token auth).
   - `DELETE /api/v1/maintenance/{monitor}` — end the window early.
   - `GET /api/v1/maintenance` — list all active windows.
2. Create a `PersonalAccessToken`-based auth flow (Laravel Sanctum, already available).
3. Document in `docs/api-reference.md`.

**Effort:** ~3–4 hours.

---

### 3.3 Incident Timeline / Post-mortem View

**Status:** `MonitorIncident` model exists. No dedicated UI per incident.

**Plan:**
1. New page: `/monitors/{monitor}/incidents/{incident}` — detailed incident timeline.
2. Timeline events: detected → confirmation delay → confirmed down → first notification sent → resolved → notifications sent.
3. Duration, affected checks, response time at failure point.
4. Optional: "Add post-mortem note" text area (store in `monitor_incidents.notes`).

**Effort:** ~6–8 hours.

---

### 3.4 Alert Deduplication & Escalation Policy

**Problem:** Alerts fire on every incident. Users with many monitors get noise. No escalation — all channels are notified simultaneously.

**Plan:**
1. Add `notification_settings` JSON column fields per monitor:
   ```json
   {
     "escalation": [
       { "delay_minutes": 0,  "channels": ["slack"] },
       { "delay_minutes": 5,  "channels": ["telegram"] },
       { "delay_minutes": 15, "channels": ["email"] }
     ]
   }
   ```
2. Modify `MonitorStatusChanged` notification `via()` to check the elapsed downtime against the escalation ladder before adding a channel.
3. De-duplicate: if an incident notification was already sent within X minutes for the same monitor, skip (use existing `EmailRateLimitService` pattern as the template).

**Effort:** ~6–10 hours.

---

### 3.5 Multi-user Teams / Shared Monitors

**Problem:** The current model is single-user or admin/viewer. Organizations want to share specific monitors with teammates without granting full admin access.

**Plan:**
1. New model: `Team` + `TeamUser` pivot (with roles: `owner`, `member`, `viewer`).
2. New model: `TeamMonitor` — associates a monitor with a team (instead of or in addition to a user).
3. Policies: `MonitorPolicy` already exists — extend it to check team membership.
4. Invitation flow: invite by email → `TeamInvitation` model → signed URL → accept.
5. UI: Team settings page, member list, monitor assignment.

**Effort:** ~2–3 days (substantial, plan carefully before starting).

---

### 3.6 Status Page Subscriber — Rich Notifications

**Status:** `StatusPageSubscriber` model exists with `email` and `webhook_url` fields.

**Plan:**
1. Support configurable alert thresholds per subscriber: e.g., "only notify me if a monitor has been down for > 5 minutes".
2. Add webhook delivery with retry (dispatch a `SendStatusPageWebhookJob`).
3. Add a subscriber management page for status page owners (list, delete, test webhook).

**Effort:** ~4–6 hours.

---

### 3.7 Monitor Import / Bulk Management API

**Status:** `MonitorImportService` (15KB) and `MonitorImportController` already handle CSV/JSON imports via UI upload.

**Plan:**
1. Expose import as a REST API: `POST /api/v1/monitors/import` (JSON body, Sanctum bearer token).
2. Add `POST /api/v1/monitors` (single create) and `DELETE /api/v1/monitors/{id}` for full CRUD.
3. This enables Infrastructure-as-Code patterns — manage monitors from Terraform/Ansible/CI scripts.

**Effort:** ~3–4 hours.

---

## Phase 4 — Developer Experience

---

### 4.1 Test Coverage: 71% → 90%

The biggest coverage gaps (currently 0%):

| Class | Quick test plan |
|---|---|
| `OgImageService` | Mock GD/Imagick, assert PNG bytes returned for a known monitor |
| `OgImageController` | Assert `image/png` response with correct cache headers |
| `DebugStatsController` | Auth guard + assert JSON keys present |
| `AppearanceController` | Toggle dark/light mode, assert session/cookie set |
| `MonitorExportController` | Assert CSV/JSON download with correct `Content-Disposition` |
| `CheckDatabaseHealth` | Spy on DB ping, assert health result shape |
| `SendTelemetryPingJob` | Mock `TelemetryService`, assert ping dispatched |
| `RunCronlessSchedulerCommand` | Assert schedule runs without exit code 1 |
| `UpdateMaintenanceStatusCommand` | Factory-seed a monitor in maintenance, assert status updated |
| `MonitorDomainExpirationReminder` | Assert reminder created for expiring domains |

**After reaching 90% locally**, add the CI gate to `.github/workflows/tests.yml`:
```yaml
- run: vendor/bin/pest --coverage --min=90 --coverage-clover=storage/coverage/coverage.xml
```

**Effort:** ~35 test files, ~2–3 days spread across the features being touched.

---

### 4.2 Add Cloudflare (or Similar CDN)

**Problem:** Slackbot, crawlers, and bots hit public unauthenticated routes (badges, status pages, SSE stream) directly on the origin. Existing caching + rate limiting is solid but edge caching would cut origin load significantly.

**Plan:**
1. Route the domain through Cloudflare (DNS proxy).
2. Cache rules: `Cache-Control: public, max-age=60` already set on badge and status page routes — Cloudflare will respect these.
3. Bot fight mode: enable to block Slackbot-LinkExpanding and similar crawlers hitting the SSE stream.
4. Confirm SSE stream (`/api/monitor-status-stream`) bypasses Cloudflare cache (it should, as it uses `text/event-stream`).

**Effort:** ~1 hour (DNS + Cloudflare dashboard config). No code changes needed.

---

### 4.3 PR Coverage Gate

**Problem:** `.github/workflows/pr-checks.yml` exists but does not enforce a coverage minimum. Coverage can regress silently on PRs.

**Fix:** Add this step to `pr-checks.yml`:
```yaml
- name: Run tests with coverage
  run: vendor/bin/pest --coverage --min=90
```
Do this **after** item 4.1 brings coverage to 90%.

---

### 4.4 Fix Risky/Skipped Tests

The suite has **1 risky + 5 skipped** tests (pre-existing). Each should be:
- Properly asserted (if the test logic is valid but just missing an expectation), or
- Documented with a `// @skip reason:` comment explaining why it's temporarily skipped.

Run `vendor/bin/pest --list-tests | grep -i skip` to enumerate them.

---

### 4.5 PHP 8.4 Deprecation: `io-developer/php-whois`

`DomainExpirationService` suppresses PHP 8.4 deprecation notices from `io-developer/php-whois`. Monitor the package's release page for a PHP 8.4 compatible version. When available, update and remove the suppression.

---

## Phase 5 — Growth & Ecosystem

Long-term ideas to grow the product's reach and appeal.

---

### 5.1 Public API Documentation

Generate an OpenAPI 3.1 spec from existing routes:
- `GET /api/v1/check` (already documented in README)
- Future: `POST /api/v1/monitors`, `GET /api/v1/monitors`, maintenance window endpoints
- Serve interactive docs at `/api/docs` using Scribe or Scramble.

---

### 5.2 Notification Channel: WhatsApp (via Twilio)

Add WhatsApp as a 5th notification channel. The `NotificationChannel` model is already generic (`type`, `destination`, `is_enabled`). Add:
- `'whatsapp'` as a new `type` enum value.
- A `WhatsAppRateLimitService` (following `TelegramRateLimitService` pattern).
- `toWhatsApp()` in `MonitorStatusChanged` using `laravel-notification-channels/twilio`.

---

### 5.3 Mobile App (API-first)

The existing `GET /api/v1/check` API is stateless and public. A mobile companion (React Native or Flutter) could offer:
- Push notifications for incidents (via FCM/APNs).
- On-the-go NOC wallboard.
- Quick manual check trigger.

Prerequisite: complete the full REST API (Phase 3.7) and add OAuth token auth.

---

### 5.4 SLA Report Export

Allow users to export a PDF/CSV SLA report for a date range. Uses existing `MonitorStatistic` and `MonitorUptimeDaily` data. Useful for teams that need to report SLAs to clients or management.

---

## Suggested Execution Order

```
Week 1 (Security & Quick Wins)
├── 1.3 Disable trace-replay in production            [5 min — do NOW]
├── 1.4 Fix .env.example queue default                [2 min]
├── 1.1 Telegram webhook signature verification       [~2h]
└── 1.2 Schedule database backups                     [30 min]

Week 2 (Weekly Report — New Feature)
├── 2.1 Migration: weekly_report_preference on users
├── 2.2 WeeklyMonitorReport notification + email template
├── 2.3 SendWeeklyMonitorReportJob
├── 2.4 Schedule the job in routes/console.php
├── 2.5 User preference UI toggle + timezone
└── 2.6 Unsubscribe signed URL in email footer

Week 3–4 (Coverage Push)
└── 4.1 Add tests for 0% coverage classes → reach 90%

Week 5–6 (Feature: Tags + Maintenance API)
├── 3.1 Monitor tags/groups UI
└── 3.2 CI/CD maintenance window API

Week 7+ (Larger features as prioritized)
├── 3.3 Incident timeline / post-mortem view
├── 3.4 Alert escalation policy
├── 3.5 Multi-user teams (plan carefully first)
└── 3.7 Monitor import/bulk API

Ongoing
├── 4.2 Cloudflare setup (1h, any time)
├── 4.3 PR coverage gate (after 90% reached)
└── 5.x Growth features as appetite allows
```

---

## Notes

- All new PHP code must pass `vendor/bin/pint --dirty --format agent` before merge.
- All new features require at least one feature test (happy path + one failure path).
- Weekly Report (Phase 2) uses existing infrastructure: `MonitorStatistic`, `MonitorHistory`, `EmailRateLimitService`, `TelegramRateLimitService` — no new dependencies needed.
- Before starting Phase 3.5 (Teams), review the existing `UserMonitor` pivot model — it may be the foundation.
