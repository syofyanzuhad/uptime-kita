import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\TelemetryReceiverController::receive
* @see app/Http/Controllers/Api/TelemetryReceiverController.php:17
* @route '/api/telemetry/ping'
*/
export const receive = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: receive.url(options),
    method: 'post',
})

receive.definition = {
    methods: ["post"],
    url: '/api/telemetry/ping',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\TelemetryReceiverController::receive
* @see app/Http/Controllers/Api/TelemetryReceiverController.php:17
* @route '/api/telemetry/ping'
*/
receive.url = (options?: RouteQueryOptions) => {
    return receive.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\TelemetryReceiverController::receive
* @see app/Http/Controllers/Api/TelemetryReceiverController.php:17
* @route '/api/telemetry/ping'
*/
receive.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: receive.url(options),
    method: 'post',
})

const TelemetryReceiverController = { receive }

export default TelemetryReceiverController