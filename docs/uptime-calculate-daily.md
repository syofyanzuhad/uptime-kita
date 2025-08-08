# Uptime Calculation Command

This document describes the `uptime:calculate-daily` artisan command that allows you to calculate daily uptime for monitors on specific dates.

## Command Overview

The command calculates daily uptime percentages for monitors based on their history data and stores the results in the `monitor_uptime_dailies` table.

## Usage

### Basic Usage

```bash
# Calculate uptime for all monitors for today
php artisan uptime:calculate-daily

# Calculate uptime for all monitors for a specific date
php artisan uptime:calculate-daily 2024-01-15

# Calculate uptime for a specific monitor for a specific date
php artisan uptime:calculate-daily 2024-01-15 --monitor-id=1

# Force recalculation even if data already exists
php artisan uptime:calculate-daily 2024-01-15 --force
```

### Parameters

- `date` (optional): The date to calculate uptime for in Y-m-d format (e.g., 2024-01-15)
  - If not provided, defaults to today's date
  - Must be in Y-m-d format

### Options

- `--monitor-id`: Calculate uptime for a specific monitor only
  - If not provided, calculates for all monitors
- `--force`: Force recalculation even if data already exists for the date
  - By default, skips monitors that already have calculations for the specified date

## Examples

### Calculate for all monitors on a specific date
```bash
php artisan uptime:calculate-daily 2024-01-15
```

### Calculate for a specific monitor
```bash
php artisan uptime:calculate-daily 2024-01-15 --monitor-id=1
```

### Force recalculation for all monitors
```bash
php artisan uptime:calculate-daily 2024-01-15 --force
```

### Force recalculation for a specific monitor
```bash
php artisan uptime:calculate-daily 2024-01-15 --monitor-id=1 --force
```

## How it Works

1. **Date Validation**: The command validates that the provided date is in the correct Y-m-d format
2. **Monitor Selection**: 
   - If `--monitor-id` is provided, it validates the monitor exists and processes only that monitor
   - Otherwise, it processes all monitors
3. **Duplicate Check**: By default, it skips monitors that already have uptime calculations for the specified date
4. **Job Dispatch**: It dispatches `CalculateSingleMonitorUptimeJob` for each monitor that needs processing
5. **Queue Processing**: The actual calculation happens in the background via Laravel's queue system

## Output

The command provides informative output about:
- The date being processed
- Number of monitors found
- Number of monitors being processed
- Whether jobs were dispatched successfully
- Any errors that occur

## Error Handling

- **Invalid Date Format**: Returns error if date is not in Y-m-d format
- **Monitor Not Found**: Returns error if specified monitor ID doesn't exist
- **Database Errors**: Logs errors and returns appropriate exit codes

## Integration with Scheduled Jobs

This command can be used alongside the existing scheduled job that runs every 5 minutes:
- The scheduled job (`CalculateMonitorUptimeDailyJob`) runs automatically
- This command allows manual execution for specific dates or monitors
- Useful for backfilling missing data or recalculating specific periods

## Queue Configuration

The calculation jobs are dispatched to the `uptime-calculations` queue. Make sure your queue worker is running:

```bash
php artisan queue:work --queue=uptime-calculations
```

## Monitoring

You can monitor the job execution through:
- Laravel Horizon dashboard (if configured)
- Queue logs
- Application logs for detailed job execution information
