import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\TelemetryDashboardController::index
* @see app/Http/Controllers/TelemetryDashboardController.php:16
* @route '/admin/telemetry'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/admin/telemetry',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TelemetryDashboardController::index
* @see app/Http/Controllers/TelemetryDashboardController.php:16
* @route '/admin/telemetry'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TelemetryDashboardController::index
* @see app/Http/Controllers/TelemetryDashboardController.php:16
* @route '/admin/telemetry'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TelemetryDashboardController::index
* @see app/Http/Controllers/TelemetryDashboardController.php:16
* @route '/admin/telemetry'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\TelemetryDashboardController::stats
* @see app/Http/Controllers/TelemetryDashboardController.php:63
* @route '/admin/telemetry/stats'
*/
export const stats = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: stats.url(options),
    method: 'get',
})

stats.definition = {
    methods: ["get","head"],
    url: '/admin/telemetry/stats',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TelemetryDashboardController::stats
* @see app/Http/Controllers/TelemetryDashboardController.php:63
* @route '/admin/telemetry/stats'
*/
stats.url = (options?: RouteQueryOptions) => {
    return stats.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TelemetryDashboardController::stats
* @see app/Http/Controllers/TelemetryDashboardController.php:63
* @route '/admin/telemetry/stats'
*/
stats.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: stats.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TelemetryDashboardController::stats
* @see app/Http/Controllers/TelemetryDashboardController.php:63
* @route '/admin/telemetry/stats'
*/
stats.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: stats.url(options),
    method: 'head',
})

const telemetry = {
    index: Object.assign(index, index),
    stats: Object.assign(stats, stats),
}

export default telemetry