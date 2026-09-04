import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\DebugStatsController::__invoke
* @see app/Http/Controllers/DebugStatsController.php:12
* @route '/debug-stats'
*/
const DebugStatsController = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: DebugStatsController.url(options),
    method: 'get',
})

DebugStatsController.definition = {
    methods: ["get","head"],
    url: '/debug-stats',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DebugStatsController::__invoke
* @see app/Http/Controllers/DebugStatsController.php:12
* @route '/debug-stats'
*/
DebugStatsController.url = (options?: RouteQueryOptions) => {
    return DebugStatsController.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DebugStatsController::__invoke
* @see app/Http/Controllers/DebugStatsController.php:12
* @route '/debug-stats'
*/
DebugStatsController.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: DebugStatsController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DebugStatsController::__invoke
* @see app/Http/Controllers/DebugStatsController.php:12
* @route '/debug-stats'
*/
DebugStatsController.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: DebugStatsController.url(options),
    method: 'head',
})

export default DebugStatsController