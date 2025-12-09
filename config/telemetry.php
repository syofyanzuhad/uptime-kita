<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Telemetry Enabled
    |--------------------------------------------------------------------------
    |
    | Controls whether anonymous telemetry is enabled. This is opt-in by default.
    | Set to true to enable sending anonymous usage statistics to help improve
    | Uptime-Kita. No personal or identifying information is ever collected.
    |
    */
    'enabled' => env('TELEMETRY_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Telemetry Endpoint URL
    |--------------------------------------------------------------------------
    |
    | The URL where telemetry data will be sent. By default, this points to the
    | official Uptime-Kita telemetry server. You can change this to your own
    | server if you prefer to collect telemetry data yourself.
    |
    */
    'endpoint' => env('TELEMETRY_ENDPOINT', 'https://uptime.syofyanzuhad.dev/api/telemetry/ping'),

    /*
    |--------------------------------------------------------------------------
    | Ping Frequency
    |--------------------------------------------------------------------------
    |
    | How often to send telemetry pings. Options: 'hourly', 'daily', 'weekly'
    | Default is 'daily' which is recommended for most installations.
    |
    */
    'frequency' => env('TELEMETRY_FREQUENCY', 'daily'),

    /*
    |--------------------------------------------------------------------------
    | Instance ID Path
    |--------------------------------------------------------------------------
    |
    | Where to store the anonymous instance ID file. This ID is a SHA-256 hash
    | that cannot be reversed to identify the installation or its owner.
    |
    */
    'instance_id_path' => storage_path('app/.instance_id'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | Timeout in seconds for the telemetry HTTP request. Telemetry should never
    | impact your application's performance, so we use a short timeout.
    |
    */
    'timeout' => env('TELEMETRY_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, telemetry data will be logged instead of sent to the
    | endpoint. Useful for testing and debugging telemetry configuration.
    |
    */
    'debug' => env('TELEMETRY_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Receiver Enabled
    |--------------------------------------------------------------------------
    |
    | When enabled, this instance will accept telemetry pings from other
    | Uptime-Kita installations. Enable this only on your central server.
    |
    */
    'receiver_enabled' => env('TELEMETRY_RECEIVER_ENABLED', false),
];
