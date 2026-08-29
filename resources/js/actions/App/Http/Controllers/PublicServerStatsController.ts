import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\PublicServerStatsController::__invoke
* @see app/Http/Controllers/PublicServerStatsController.php:17
* @route '/api/server-stats'
*/
const PublicServerStatsController = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: PublicServerStatsController.url(options),
    method: 'get',
})

PublicServerStatsController.definition = {
    methods: ["get","head"],
    url: '/api/server-stats',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PublicServerStatsController::__invoke
* @see app/Http/Controllers/PublicServerStatsController.php:17
* @route '/api/server-stats'
*/
PublicServerStatsController.url = (options?: RouteQueryOptions) => {
    return PublicServerStatsController.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PublicServerStatsController::__invoke
* @see app/Http/Controllers/PublicServerStatsController.php:17
* @route '/api/server-stats'
*/
PublicServerStatsController.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: PublicServerStatsController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PublicServerStatsController::__invoke
* @see app/Http/Controllers/PublicServerStatsController.php:17
* @route '/api/server-stats'
*/
PublicServerStatsController.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: PublicServerStatsController.url(options),
    method: 'head',
})

export default PublicServerStatsController