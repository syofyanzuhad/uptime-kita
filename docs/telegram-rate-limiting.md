# Telegram Rate Limiting

This document explains the Telegram rate limiting implementation to prevent 429 "Too Many Requests" errors.

## Overview

The system implements a sophisticated rate limiting mechanism for Telegram notifications with the following features:

- **Minute-based rate limiting**: Maximum 20 messages per minute
- **Hour-based rate limiting**: Maximum 100 messages per hour  
- **Exponential backoff**: Automatic backoff when 429 errors are received
- **Cache-based tracking**: Uses Laravel's cache system for persistence

## Implementation Details

### Rate Limits

- **Minute limit**: 20 messages per minute (Telegram's limit is 30, but we use 20 for safety)
- **Hour limit**: 100 messages per hour
- **Backoff period**: Exponential backoff starting at 2 minutes, up to 60 minutes maximum

### Cache Keys

Rate limit data is stored using cache keys in the format:
```
telegram_rate_limit:{user_id}:{telegram_destination}
```

### Rate Limit Data Structure

```php
[
    'minute_count' => 0,
    'minute_window_start' => timestamp,
    'hour_count' => 0, 
    'hour_window_start' => timestamp,
    'backoff_count' => 0,
    'backoff_until' => timestamp, // only set when in backoff
]
```

## Usage

### Checking Rate Limit Status

```bash
# Check rate limit for current user
php artisan telegram:rate-limit-status

# Check rate limit for specific user
php artisan telegram:rate-limit-status --user=1
```

### Resetting Rate Limits (for testing)

```bash
# Reset rate limit for current user
php artisan telegram:reset-rate-limit

# Reset rate limit for specific user
php artisan telegram:reset-rate-limit --user=1
```

### Testing Notifications

```bash
# Test Telegram notification
php artisan test:telegram-notification

# Test with custom parameters
php artisan test:telegram-notification-advanced --url=https://example.com --status=DOWN
```

## Error Handling

### 429 Error Detection

The system automatically detects 429 errors by checking for:
- Error message containing "429"
- Error message containing "Too Many Requests"

When a 429 error is detected:
1. The system tracks the failure
2. Implements exponential backoff (2^backoff_count minutes)
3. Blocks further notifications until backoff period expires

### Logging

All rate limiting activities are logged with detailed information:
- Rate limit blocks
- Successful notifications
- Failed notifications (429 errors)
- Backoff periods

## Configuration

Rate limits can be adjusted in `app/Services/TelegramRateLimitService.php`:

```php
private const MAX_MESSAGES_PER_MINUTE = 20;
private const MAX_MESSAGES_PER_HOUR = 100;
private const BACKOFF_MULTIPLIER = 2;
private const MAX_BACKOFF_MINUTES = 60;
```

## Monitoring

### Log Entries to Watch

1. **Rate limit blocks**:
   ```
   Telegram notification blocked due to minute rate limit
   Telegram notification blocked due to hour rate limit
   Telegram notification blocked due to backoff period
   ```

2. **Successful notifications**:
   ```
   Telegram notification tracked successfully
   ```

3. **Failed notifications**:
   ```
   Telegram notification failed with 429 error
   Telegram notification failed - backoff period set
   ```

### Cache Monitoring

Monitor cache usage for rate limit keys:
```bash
# Check cache keys (if using Redis)
redis-cli keys "telegram_rate_limit:*"
```

## Best Practices

1. **Monitor logs regularly** for rate limiting activity
2. **Use the status command** to check rate limit status before testing
3. **Reset rate limits** only for testing, not in production
4. **Adjust limits** if needed based on your usage patterns
5. **Monitor cache usage** to ensure proper cleanup

## Troubleshooting

### Common Issues

1. **Notifications not sending**: Check if rate limited
   ```bash
   php artisan telegram:rate-limit-status
   ```

2. **Too many 429 errors**: Increase backoff periods or reduce notification frequency

3. **Cache issues**: Clear cache if rate limits seem stuck
   ```bash
   php artisan cache:clear
   ```

### Debug Commands

```bash
# List all notification channels
php artisan list:notification-channels

# Check rate limit status
php artisan telegram:rate-limit-status

# Reset rate limits for testing
php artisan telegram:reset-rate-limit

# Test notifications
php artisan test:telegram-notification
``` 
