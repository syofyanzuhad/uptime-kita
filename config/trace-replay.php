<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enable / Disable Trace-Replay
    |--------------------------------------------------------------------------
    | Set TRACE_REPLAY_ENABLED=false in production .env to completely disable
    | all tracing with zero overhead.
    */
    'enabled' => env('TRACE_REPLAY_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Max Capture Size (Bytes)
    |--------------------------------------------------------------------------
    | Prevent DB bloat by truncating payloads larger than this limit.
    | Default: 64 KB.
    */
    'max_payload_size' => env('TRACE_REPLAY_MAX_PAYLOAD_SIZE', 65536),

    /*
    |--------------------------------------------------------------------------
    | Sampling Rate
    |--------------------------------------------------------------------------
    | A float between 0.0 and 1.0 controlling what fraction of HTTP requests
    | are traced. 1.0 = trace every request, 0.1 = trace 10% at random.
    | Manual Trace-Replay::start() calls are never sampled.
    */
    'sample_rate' => env('TRACE_REPLAY_SAMPLE_RATE', 1.0),

    /*
    |--------------------------------------------------------------------------
    | Multi-Tenant / Project ID
    |--------------------------------------------------------------------------
    | Optionally set a static project UUID, or override determineProjectId()
    | in a custom TraceReplayManager binding for dynamic multi-tenancy.
    */
    'project_id' => env('TRACE_REPLAY_PROJECT_ID', null),

    /*
    |--------------------------------------------------------------------------
    | Batch Step Persistence
    |--------------------------------------------------------------------------
    | When true (default), all trace steps are buffered in memory and written
    | to the database in a single INSERT at the end of the request/job, which
    | is far more efficient than one INSERT per step.
    | Set to false to persist each step immediately (useful for long-running
    | processes where you want partial traces to survive a crash).
    | Ignored when queue.enabled is true.
    */
    'batch_persistence' => env('TRACE_REPLAY_BATCH_PERSISTENCE', true),

    /*
    |--------------------------------------------------------------------------
    | Storage & Queueing
    |--------------------------------------------------------------------------
    | When queue.enabled is true, step persistence is offloaded to a queue
    | worker to avoid adding latency to the request lifecycle.
    */
    'queue' => [
        'enabled' => env('TRACE_REPLAY_QUEUE_ENABLED', false),
        'connection' => env('TRACE_REPLAY_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'sync')),
        'queue' => env('TRACE_REPLAY_QUEUE_NAME', 'default'),
    ],

    /*
    |--------------------------------------------------------------------------
    | DB Query Tracking
    |--------------------------------------------------------------------------
    | When enabled, each step records the number and total time of DB queries
    | executed within the step closure.
    */
    'track_db_queries' => env('TRACE_REPLAY_TRACK_DB', true),

    /*
    |--------------------------------------------------------------------------
    | Data Masking
    |--------------------------------------------------------------------------
    | Fields whose values will be replaced with '********' in all captured
    | payloads (request bodies, response bodies, state snapshots).
    */
    'mask_fields' => [
        'password',
        'password_confirmation',
        'token',
        'api_key',
        'authorization',
        'secret',
        'credit_card',
        'cvv',
        'ssn',
        'private_key',
    ],

    /*
    |--------------------------------------------------------------------------
    | Replay Engine
    |--------------------------------------------------------------------------
    */
    'replay' => [
        'default_base_url' => env('TRACE_REPLAY_REPLAY_URL', env('APP_URL', 'http://localhost')),
        'timeout' => env('TRACE_REPLAY_REPLAY_TIMEOUT', 30),
        // Recommendation 12: Safety gate for non-GET methods
        'allow_mutating_methods' => env('TRACE_REPLAY_REPLAY_MUTATING', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retention / Auto-Pruning
    |--------------------------------------------------------------------------
    | Traces older than `retention_days` will be deleted by the artisan command:
    |   php artisan trace-replay:prune
    | Set to null to disable pruning.
    */
    'retention_days' => env('TRACE_REPLAY_RETENTION_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Dashboard Route Middleware
    |--------------------------------------------------------------------------
    | Protect the Trace-Replay dashboard. For production use, add 'auth' or a
    | custom gate middleware, e.g. ['web', 'auth', 'can:view-trace-replay'].
    */
    'middleware' => ['web', 'auth'],
    'api' => [
        'token' => env('TRACE_REPLAY_API_TOKEN'),
        'middleware' => ['api'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard IP Allowlist
    |--------------------------------------------------------------------------
    | When non-empty, only requests from these IP addresses can access the
    | dashboard. CIDR notation is not evaluated — exact match only.
    | Leave empty to allow all IPs (rely on middleware for auth instead).
    */
    'allowed_ips' => array_filter(explode(',', env('TRACE_REPLAY_ALLOWED_IPS', ''))),

    /*
    |--------------------------------------------------------------------------
    | Failure Notifications
    |--------------------------------------------------------------------------
    | When on_failure is true and a trace ends with status=error, a
    | notification is dispatched via the configured channels.
    */
    'notifications' => [
        'on_failure' => env('TRACE_REPLAY_NOTIFY_ON_FAILURE', false),
        'channels' => ['mail'],           // 'mail', 'slack'
        'mail' => [
            'to' => env('TRACE_REPLAY_NOTIFY_EMAIL', null),
        ],
        'slack' => [
            'webhook_url' => env('TRACE_REPLAY_SLACK_WEBHOOK', null),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Integration (Optional)
    |--------------------------------------------------------------------------
    | When openai_api_key is set, the "AI Fix" button in the dashboard will
    | call the OpenAI API directly and stream the response. When null, users
    | receive a copyable prompt instead (no external call is made).
    */
    'ai' => [
        'driver' => env('TRACE_REPLAY_AI_DRIVER', 'openai'), // openai, anthropic, ollama
        'api_key' => env('TRACE_REPLAY_AI_KEY', env('TRACE_REPLAY_OPENAI_KEY')),
        'model' => env('TRACE_REPLAY_AI_MODEL', 'gpt-4o'),
        'base_url' => env('TRACE_REPLAY_AI_BASE_URL'), // For Ollama or custom endpoints
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-Tracing: Jobs & Artisan Commands
    |--------------------------------------------------------------------------
    | When enabled, queued jobs and artisan commands are automatically wrapped
    | in traces without any manual instrumentation.
    */
    'auto_trace' => [
        'jobs' => env('TRACE_REPLAY_AUTO_TRACE_JOBS', true),
        'commands' => env('TRACE_REPLAY_AUTO_TRACE_COMMANDS', false),
        'livewire' => env('TRACE_REPLAY_AUTO_TRACE_LIVEWIRE', true),
        // Artisan commands to exclude from auto-tracing (exact names)
        'exclude_commands' => [
            'queue:work', 'queue:listen', 'horizon', 'schedule:run',
            'schedule:work', 'trace-replay:prune', 'trace-replay:export',
        ],
    ],
];
