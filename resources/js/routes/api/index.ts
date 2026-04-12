import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
import telemetry from './telemetry'
/**
* @see \App\Http\Controllers\PublicServerStatsController::__invoke
* @see app/Http/Controllers/PublicServerStatsController.php:17
* @route '/api/server-stats'
*/
export const serverStats = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: serverStats.url(options),
    method: 'get',
})

serverStats.definition = {
    methods: ["get","head"],
    url: '/api/server-stats',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PublicServerStatsController::__invoke
* @see app/Http/Controllers/PublicServerStatsController.php:17
* @route '/api/server-stats'
*/
serverStats.url = (options?: RouteQueryOptions) => {
    return serverStats.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PublicServerStatsController::__invoke
* @see app/Http/Controllers/PublicServerStatsController.php:17
* @route '/api/server-stats'
*/
serverStats.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: serverStats.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PublicServerStatsController::__invoke
* @see app/Http/Controllers/PublicServerStatsController.php:17
* @route '/api/server-stats'
*/
serverStats.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: serverStats.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MonitorStatusStreamController::__invoke
* @see app/Http/Controllers/MonitorStatusStreamController.php:19
* @route '/api/monitor-status-stream'
*/
export const monitorStatusStream = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: monitorStatusStream.url(options),
    method: 'get',
})

monitorStatusStream.definition = {
    methods: ["get","head"],
    url: '/api/monitor-status-stream',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MonitorStatusStreamController::__invoke
* @see app/Http/Controllers/MonitorStatusStreamController.php:19
* @route '/api/monitor-status-stream'
*/
monitorStatusStream.url = (options?: RouteQueryOptions) => {
    return monitorStatusStream.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MonitorStatusStreamController::__invoke
* @see app/Http/Controllers/MonitorStatusStreamController.php:19
* @route '/api/monitor-status-stream'
*/
monitorStatusStream.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: monitorStatusStream.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MonitorStatusStreamController::__invoke
* @see app/Http/Controllers/MonitorStatusStreamController.php:19
* @route '/api/monitor-status-stream'
*/
monitorStatusStream.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: monitorStatusStream.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ServerResourceController::serverResources
* @see app/Http/Controllers/ServerResourceController.php:34
* @route '/api/server-resources'
*/
export const serverResources = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: serverResources.url(options),
    method: 'get',
})

serverResources.definition = {
    methods: ["get","head"],
    url: '/api/server-resources',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ServerResourceController::serverResources
* @see app/Http/Controllers/ServerResourceController.php:34
* @route '/api/server-resources'
*/
serverResources.url = (options?: RouteQueryOptions) => {
    return serverResources.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ServerResourceController::serverResources
* @see app/Http/Controllers/ServerResourceController.php:34
* @route '/api/server-resources'
*/
serverResources.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: serverResources.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ServerResourceController::serverResources
* @see app/Http/Controllers/ServerResourceController.php:34
* @route '/api/server-resources'
*/
serverResources.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: serverResources.url(options),
    method: 'head',
})

const api = {
    serverStats: Object.assign(serverStats, serverStats),
    monitorStatusStream: Object.assign(monitorStatusStream, monitorStatusStream),
    telemetry: Object.assign(telemetry, telemetry),
    serverResources: Object.assign(serverResources, serverResources),
}

export default api