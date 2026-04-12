import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\TelemetryReceiverController::ping
* @see app/Http/Controllers/Api/TelemetryReceiverController.php:17
* @route '/api/telemetry/ping'
*/
export const ping = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: ping.url(options),
    method: 'post',
})

ping.definition = {
    methods: ["post"],
    url: '/api/telemetry/ping',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\TelemetryReceiverController::ping
* @see app/Http/Controllers/Api/TelemetryReceiverController.php:17
* @route '/api/telemetry/ping'
*/
ping.url = (options?: RouteQueryOptions) => {
    return ping.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\TelemetryReceiverController::ping
* @see app/Http/Controllers/Api/TelemetryReceiverController.php:17
* @route '/api/telemetry/ping'
*/
ping.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: ping.url(options),
    method: 'post',
})

const telemetry = {
    ping: Object.assign(ping, ping),
}

export default telemetry