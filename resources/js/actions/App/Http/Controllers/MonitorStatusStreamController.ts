import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\MonitorStatusStreamController::__invoke
* @see app/Http/Controllers/MonitorStatusStreamController.php:19
* @route '/api/monitor-status-stream'
*/
const MonitorStatusStreamController = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: MonitorStatusStreamController.url(options),
    method: 'get',
})

MonitorStatusStreamController.definition = {
    methods: ["get","head"],
    url: '/api/monitor-status-stream',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MonitorStatusStreamController::__invoke
* @see app/Http/Controllers/MonitorStatusStreamController.php:19
* @route '/api/monitor-status-stream'
*/
MonitorStatusStreamController.url = (options?: RouteQueryOptions) => {
    return MonitorStatusStreamController.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MonitorStatusStreamController::__invoke
* @see app/Http/Controllers/MonitorStatusStreamController.php:19
* @route '/api/monitor-status-stream'
*/
MonitorStatusStreamController.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: MonitorStatusStreamController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MonitorStatusStreamController::__invoke
* @see app/Http/Controllers/MonitorStatusStreamController.php:19
* @route '/api/monitor-status-stream'
*/
MonitorStatusStreamController.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: MonitorStatusStreamController.url(options),
    method: 'head',
})

export default MonitorStatusStreamController