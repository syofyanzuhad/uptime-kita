# Monitor History Databases

This document explains the dynamic SQLite database system for storing monitor history records.

## Overview

Each monitor now has its own dedicated SQLite database located at `database/monitor-histories/{monitor_id}.sqlite`. This system provides:

- **Isolation**: Each monitor's history is stored separately
- **Performance**: Faster queries for individual monitors
- **Scalability**: No single table performance bottlenecks
- **Maintenance**: Easy to clean up or backup individual monitor data

## Architecture

### Database Structure

Each monitor database contains a single table:

```sql
CREATE TABLE monitor_histories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    uptime_status VARCHAR NOT NULL,
    message TEXT,
    response_data JSON,
    response_time_ms INTEGER,
    certificate_status VARCHAR,
    certificate_expiration_date TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### File Structure

```
database/
├── monitor-histories/
│   ├── 1.sqlite      # Monitor ID 1 history
│   ├── 2.sqlite      # Monitor ID 2 history
│   ├── 3.sqlite      # Monitor ID 3 history
│   └── ...
```

## Components

### 1. MonitorHistoryDatabaseService

The main service class that handles all database operations:

```php
use App\Services\MonitorHistoryDatabaseService;

$service = new MonitorHistoryDatabaseService();

// Create database for a monitor
$service->createMonitorDatabase($monitorId);

// Insert history record
$service->insertHistory($monitorId, [
    'uptime_status' => 'up',
    'message' => 'Site is online',
    'response_time_ms' => 150,
    'certificate_status' => 'valid',
]);

// Get history records
$records = $service->getHistory($monitorId, 100, 0);

// Get latest record
$latest = $service->getLatestHistory($monitorId);

// Cleanup old records
$deleted = $service->cleanupOldHistory($monitorId, 30);
```

### 2. MonitorHistoryRecord Model

A model for working with history records:

```php
use App\Models\MonitorHistoryRecord;

// Create record for a monitor
MonitorHistoryRecord::createForMonitor($monitorId, $data);

// Get records for a monitor
$records = MonitorHistoryRecord::getForMonitor($monitorId);

// Get latest record
$latest = MonitorHistoryRecord::getLatestForMonitor($monitorId);

// Cleanup old records
$deleted = MonitorHistoryRecord::cleanupForMonitor($monitorId, 30);
```

### 3. MonitorHistoryController

API endpoints for accessing monitor history:

- `GET /api/monitor/{monitorId}/history` - Get history records
- `GET /api/monitor/{monitorId}/history/latest` - Get latest record
- `GET /api/monitor/{monitorId}/history/statistics` - Get statistics
- `POST /api/monitor/{monitorId}/history/cleanup` - Cleanup old records

## Automatic Database Creation

Databases are automatically created when:

1. **Monitor Creation**: When a new monitor is created, its database is automatically created
2. **First History Record**: If a database doesn't exist when trying to insert a record, it's created automatically

## Commands

### Management Commands

```bash
# Create databases for all monitors
php artisan monitor:history-databases create-all

# Show status of all databases
php artisan monitor:history-databases status

# Cleanup old records from all databases
php artisan monitor:history-databases cleanup-all --days=30

# Delete all databases (with confirmation)
php artisan monitor:history-databases delete-all
```

### Cleanup Commands

```bash
# Cleanup old records for all monitors
php artisan monitor:cleanup-history --days=30

# Cleanup specific monitor
php artisan monitor:cleanup-history --monitor-id=123 --days=30
```

## Scheduled Tasks

The system includes automatic cleanup:

```php
// Runs daily at 2:00 AM
Schedule::command('monitor:cleanup-history --days=30')->daily()->at('02:00');
```

## API Usage

### Get History Records

```bash
curl "https://your-domain.com/api/monitor/123/history?limit=100&offset=0"
```

Response:
```json
{
    "data": [
        {
            "id": 1,
            "uptime_status": "up",
            "message": "Site is online",
            "response_time_ms": 150,
            "certificate_status": "valid",
            "created_at": "2024-01-15T10:30:00Z"
        }
    ],
    "meta": {
        "total": 1,
        "limit": 100,
        "offset": 0,
        "monitor_id": 123,
        "monitor_url": "https://example.com"
    }
}
```

### Get Latest Record

```bash
curl "https://your-domain.com/api/monitor/123/history/latest"
```

### Get Statistics

```bash
curl "https://your-domain.com/api/monitor/123/history/statistics"
```

Response:
```json
{
    "data": {
        "total_records": 1000,
        "status_counts": {
            "up": 950,
            "down": 45,
            "not yet checked": 5
        },
        "uptime_percentage": 95.0,
        "average_response_time": 125.5,
        "last_check": "2024-01-15T10:30:00Z"
    }
}
```

### Cleanup Old Records

```bash
curl -X POST "https://your-domain.com/api/monitor/123/history/cleanup" \
     -H "Content-Type: application/json" \
     -d '{"days": 30}'
```

## Database Configuration

The system uses a dynamic database connection configured in `config/database.php`:

```php
'sqlite_monitor_history' => [
    'driver' => 'sqlite',
    'database' => null, // Set dynamically
    'prefix' => '',
    'foreign_key_constraints' => true,
    'busy_timeout' => 10000,
    'journal_mode' => 'WAL',
    'synchronous' => 'NORMAL',
],
```

## Testing

Run the tests to verify functionality:

```bash
php artisan test tests/Feature/MonitorHistoryDatabaseTest.php
```

## Migration from Existing System

If you have existing monitor history data in the main database, you can migrate it:

1. Export existing data from `monitor_histories` table
2. Create individual databases for each monitor
3. Import the data into the respective monitor databases
4. Update the Monitor model to use the new system

## Backup and Restore

### Backup Individual Monitor

```bash
# Copy the database file
cp database/monitor-histories/123.sqlite backup/monitor-123-backup.sqlite
```

### Backup All Monitors

```bash
# Create backup directory
mkdir -p backup/monitor-histories-$(date +%Y%m%d)

# Copy all databases
cp database/monitor-histories/*.sqlite backup/monitor-histories-$(date +%Y%m%d)/
```

### Restore Monitor

```bash
# Restore from backup
cp backup/monitor-123-backup.sqlite database/monitor-histories/123.sqlite
```

## Performance Considerations

- **WAL Mode**: Uses Write-Ahead Logging for better concurrency
- **Indexes**: Created on `uptime_status`, `created_at` for fast queries
- **Connection Pooling**: Each request gets a fresh connection
- **Cleanup**: Regular cleanup prevents database bloat

## Security

- **Authorization**: API endpoints check user permissions
- **File Permissions**: Database files are created with 755 permissions
- **Path Validation**: Database paths are validated to prevent directory traversal

## Troubleshooting

### Database Not Created

Check if the monitor creation event is firing:

```php
// In Monitor model
static::created(function ($monitor) {
    \App\Models\MonitorHistoryRecord::ensureMonitorDatabase($monitor->id);
});
```

### Permission Errors

Ensure the web server has write permissions to the `database/monitor-histories` directory:

```bash
chmod 755 database/monitor-histories
chown www-data:www-data database/monitor-histories
```

### Database Locked

If you get database locked errors, check for concurrent access:

```php
// Increase busy timeout in config/database.php
'busy_timeout' => 30000, // 30 seconds
```

### Cleanup Not Working

Verify the scheduled task is running:

```bash
# Check if the command works manually
php artisan monitor:cleanup-history --days=30

# Check Laravel scheduler logs
tail -f storage/logs/laravel.log | grep "monitor:cleanup-history"
```
