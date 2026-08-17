# Improvements

Grounded recommendations for Uptime Kita, based on a code audit done in August 2026 (post Laravel 13 upgrade). Each item lists the problem, why it matters, and the fix. Items are roughly ordered by impact.

---

## 1. Test coverage is 71% — the 90% target needs a dedicated push

**Measured:** `php artisan test --coverage` (with PCOV) reports **71.0%**, about 19 points below your 90% target. CI (`.github/workflows/tests.yml`) uploads coverage to Codecov but enforces **no minimum**, so the gap is silent.

**Biggest offenders (by coverage, from the local report):**

| Coverage | Classes |
|---|---|
| 0% | `OgImageService`, `SimpleMonitorResource`, `SendTelemetryPingJob`, `MonitorExportController`, `OgImageController`, `DebugStatsController`, `AppearanceController`, `CheckDatabaseHealth`, `OptimizeSqliteDatabase`, `RunCronlessSchedulerCommand`, `UpdateMaintenanceStatusCommand`, `MonitorDomainExpirationReminder` |
| 13–25% | `DatabaseBackupController`, `TelemetryPing`, `BatchedMonitorStatusChanged`, `MonitorStatusStreamController`, `MonitorCheckUptime`, `TelemetryDashboardController` |
| 37–57% | `TelescopeServiceProvider`, `SmartRetryService`, `ConfirmMonitorDowntimeJob`, `SmartRetryResult`, `TelegramWebhookController`, `HorizonServiceProvider`, `PublicMonitorController`, `TwitterRateLimitService` |

Plus a long tail of classes in the 66–80% band that each need a few more assertions.

**Plan of attack (roughly 35+ test files):**
1. Start with the 0% classes — they're small, self-contained, and add the most percentage points.
2. Add feature tests for the controllers (export, OG image, debug stats, appearance) using the app's existing Pest patterns.
3. Only after coverage is above 90% locally, add the CI gate:
   ```bash
   ./vendor/bin/pest --coverage --min=90 --coverage-clover=storage/coverage/coverage.xml
   ```
   Add `--min=90` to `.github/workflows/tests.yml` so the gate is enforced on every push. (Deliberately not added yet — it would fail at 71%.)

---

## 2. Database backup is configured but never scheduled

`routes/console.php` has the Spatie backup schedule **commented out**:

```php
// Schedule::command('backup:clean')->daily()->at('01:00');
// Schedule::command('backup:run')->daily()->at('01:30')
```

The app monitors *other people's* uptime — losing your own database would be ironic. `config/backup.php` is present (check `config/filesystems.php` has a backup disk configured with a real destination like S3 before enabling).

**Fix:** uncomment the two lines, configure a backup destination disk (S3/R2/Google Drive) and set `BACKUP_DESTINATION_*` env vars. Consider `backup:monitor` for alerting on backup health. (Note: the app is on Laravel Cloud — Cloud offers managed nightly database snapshots; those cover the primary DB, but the separate `database/queue.sqlite` would need its own handling.)

---

## 3. Telegram webhook has no signature verification

`routes/web.php` exposes `POST /webhook/telegram` with only `throttle:60,1` — no HMAC signature check. Current impact is low (the handler only replies to `/start` with the sender's chat ID), but the route accepts any payload and any future handler logic would inherit the same exposure.

**Fix (low effort):** verify Telegram's `X-Telegram-Bot-Api-Secret-Token` header (set when registering the webhook via `setWebhook`) against a secret env var, using a small middleware — same pattern as Stripe's webhook middleware.

---

## 4. Replace the custom uptime-check health with Laravel's first-party stack

The app runs two parallel monitoring stacks:
- A hand-rolled monitor loop (`MonitorCheckUptime` job, `CheckMonitorUptime*` commands, cronless scheduler scripts) for the core product.
- **Spatie Health** (`config/health.php`, `CheckDatabaseHealth`, `SimpleHealthCheckController`) for the app's own health.

Spatie Health is fine for what it does, but Laravel 12+/13 ships a first-party **Laravel Health** that covers database, cache, queue (incl. Horizon), storage, and schedule checks with a `/up` endpoint. Consolidating removes a dependency and gives a unified health surface. Medium effort — do only if you want fewer dependencies; otherwise keep Spatie and just fill the 0% test gaps (`CheckDatabaseHealth` currently has no tests).

---

## 5. Tighten the trace-replay defaults (data hygiene)

`iazaran/trace-replay` is enabled by default and wired globally in `bootstrap/app.php`:
- `TRACE_REPLAY_ENABLED` defaults to **true** when unset
- `sample_rate = 1.0` traces **100%** of requests, and `auto_trace.jobs = true` traces every queued job
- It writes `tr_traces`, `tr_trace_steps`, `tr_projects`, `tr_workspaces` rows into your main database — for every HTTP request and every job (this is what filled those tables with Slackbot crawler hits and job checkpoints).

`TRACE_REPLAY_ENABLED=false` is now in `.env.example` (commit pending), but **production `.env` on Laravel Cloud still needs the variable set**. For retained data, run `php artisan trace-replay:prune` or truncate the `tr_*` tables.

**If you keep it enabled:** drop `sample_rate` to e.g. `0.1` and set `TRACE_REPLAY_AUTO_TRACE_JOBS=false` to cut storage by ~90%.

---

## 6. `.env.example` defaults diverge from production

- `QUEUE_CONNECTION=database` (framework default) — production uses Laravel Cloud's managed queue (`QUEUE_CONNECTION=cloud`). New environments provisioned from `.env.example` silently use the database driver instead of Redis. Set `QUEUE_CONNECTION=redis` (the app's intended driver — `predis` is installed and the config is wired) or `cloud` for consistency.
- `APP_ENV=local` in the example is fine for local dev, but make sure deployment tooling overrides it (Laravel Cloud does this automatically).

---

## 7. Public endpoints: caching is good; consider bot/perimeter hardening

The Slackbot and crawler traffic seen in the trace data (`Slackbot-LinkExpanding`, generic Chrome UAs) all hits **public, unauthenticated routes** — home, `/m/{domain}`, status pages, badges, and the unauthenticated SSE stream `/api/monitor-status-stream`. These are already cached (`cache()->remember` with 60–3600s TTLs) and rate-limited (`throttle:10,1` / `30,1` / `60,1`), which is solid.

Worth considering:
- Cloudflare (or similar) in front for bot filtering and edge caching of the public pages/badges.
- Confirm the SSE stream and server-stats endpoints don't leak anything non-public (they currently have no auth).

---

## 8. Minor / housekeeping

- **Scheduled prune coverage:** `telescope:prune --hours=48` runs only `everyOddHour()` and `queue:prune-batches` daily — verify Telescope's 48-hour retention is intentional; the traces table grows otherwise.
- **`docs/` lacks a coverage/how-to-test doc** — worth a short note on running `php artisan test --coverage` with PCOV (installed for Herd PHP 8.4) so future contributors measure the same number.
- **No PR-time test gate:** `.github/workflows/pr-checks.yml` exists — confirm it runs the same `--min` coverage gate as CI once added, so PRs can't regress coverage.
- **Risky/skipped tests:** the suite has 1 risky + 5 skipped tests (pre-existing) — worth a pass to either assert them properly or document why they're skipped.
- **`io-developer/php-whois`** emits PHP 8.4 deprecation noise; it's suppressed inside `DomainExpirationService` — keep an eye on the package for a fixed release.

---

## Suggested order of work

1. **Coverage push to 90%** (item 1) — biggest, most concrete win; ~35 test files. Add the `--min=90` CI gate after.
2. **Telegram webhook signature** (item 3) — small, security-relevant.
3. **Backup scheduling + destination** (item 2) — config-only if the disk is already set up.
4. **Set `TRACE_REPLAY_ENABLED=false` in production env + prune** (item 5) — five minutes, stops the table growth.
5. **`.env.example` queue default** (item 6) — one-line change.
6. Items 4, 7, 8 as time allows.
