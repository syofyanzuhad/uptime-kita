# Troubleshooting schedule:run-cronless

## Penyebab Command Berhenti

### 1. Uncaught Exceptions
Command menggunakan ReactPHP event loop yang akan stop jika ada unhandled exception.

**Solusi:**
- Wrap semua scheduled tasks dengan try-catch
- Gunakan monitoring untuk detect crashes
- Check logs: `tail -f storage/logs/laravel.log`

### 2. Memory Leak
Command berjalan terus-menerus dan bisa mengalami memory leak.

**Solusi:**
- Monitor memory usage: `watch -n 1 'ps aux | grep schedule:run-cronless'`
- Increase PHP memory limit di php.ini
- Restart command secara periodik dengan supervisor

### 3. Database Connection Issues
Telescope atau database lain bisa disconnect/corrupt.

**Solusi:**
- Pastikan semua database file exists
- Fix Telescope: `php artisan migrate --database=sqlite_telescope`
- Disable Telescope di production jika tidak perlu

### 4. Process Manager Restart
Command bisa dimatikan oleh system atau process manager.

**Solusi:**
- Gunakan Supervisor untuk auto-restart
- Check system logs: `dmesg | grep -i killed`

## Monitoring Command

### Check if running:
```bash
ps aux | grep schedule:run-cronless
```

### Monitor memory:
```bash
watch -n 5 'ps aux | grep schedule:run-cronless | grep -v grep | awk "{print \$6/1024\" MB\"}"'
```

### Check logs in real-time:
```bash
tail -f storage/logs/laravel.log | grep -i "schedule\|error\|exception"
```

## Setup Supervisor (Recommended)

Create file: `/etc/supervisor/conf.d/laravel-scheduler.conf`

```ini
[program:laravel-scheduler]
process_name=%(program_name)s
command=php /path/to/your/project/artisan schedule:run-cronless
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/scheduler.log
stopwaitsecs=3600
```

Reload supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-scheduler
```

## Alternative: Use systemd

Create file: `/etc/systemd/system/laravel-scheduler.service`

```ini
[Unit]
Description=Laravel Cronless Scheduler
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/your/project
ExecStart=/usr/bin/php artisan schedule:run-cronless
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Enable and start:
```bash
sudo systemctl daemon-reload
sudo systemctl enable laravel-scheduler
sudo systemctl start laravel-scheduler
sudo systemctl status laravel-scheduler
```

## Quick Fixes

### Fix Telescope Database:
```bash
# Check if file exists
ls -la database/telescope.sqlite

# If not, create it
touch database/telescope.sqlite
php artisan migrate --database=sqlite_telescope
```

### Disable Telescope in Production:
Edit `app/Providers/TelescopeServiceProvider.php`:
```php
public function register(): void
{
    if ($this->app->environment('local')) {
        Telescope::night();
    }
}
```

### Add Error Handling to Scheduled Tasks:
In `routes/console.php`, wrap critical tasks:
```php
Schedule::command(CheckUptime::class)
    ->everyMinute()
    ->onSuccess(function () {
        info('UPTIME-CHECK: SUCCESS');
    })
    ->onFailure(function () {
        info('UPTIME-CHECK: FAILED');
    });
```

## Debug Mode

Run with verbose output:
```bash
php artisan schedule:run-cronless --verbose
```

Check schedule list:
```bash
php artisan schedule:list
```

Manual run to test:
```bash
php artisan schedule:run
```
