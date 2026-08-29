import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\DebugStatsController::__invoke
* @see app/Http/Controllers/DebugStatsController.php:12
* @route '/debug-stats'
*/
export const stats = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: stats.url(options),
    method: 'get',
})

stats.definition = {
    methods: ["get","head"],
    url: '/debug-stats',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DebugStatsController::__invoke
* @see app/Http/Controllers/DebugStatsController.php:12
* @route '/debug-stats'
*/
stats.url = (options?: RouteQueryOptions) => {
    return stats.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DebugStatsController::__invoke
* @see app/Http/Controllers/DebugStatsController.php:12
* @route '/debug-stats'
*/
stats.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: stats.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DebugStatsController::__invoke
* @see app/Http/Controllers/DebugStatsController.php:12
* @route '/debug-stats'
*/
stats.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: stats.url(options),
    method: 'head',
})

const debug = {
    stats: Object.assign(stats, stats),
}

export default debug