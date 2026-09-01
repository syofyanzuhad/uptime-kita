import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\LatestHistoryController::__invoke
* @see app/Http/Controllers/LatestHistoryController.php:14
* @route '/monitor/{monitor}/latest-history'
*/
const LatestHistoryController = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: LatestHistoryController.url(args, options),
    method: 'get',
})

LatestHistoryController.definition = {
    methods: ["get","head"],
    url: '/monitor/{monitor}/latest-history',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\LatestHistoryController::__invoke
* @see app/Http/Controllers/LatestHistoryController.php:14
* @route '/monitor/{monitor}/latest-history'
*/
LatestHistoryController.url = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { monitor: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { monitor: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            monitor: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        monitor: typeof args.monitor === 'object'
        ? args.monitor.id
        : args.monitor,
    }

    return LatestHistoryController.definition.url
            .replace('{monitor}', parsedArgs.monitor.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\LatestHistoryController::__invoke
* @see app/Http/Controllers/LatestHistoryController.php:14
* @route '/monitor/{monitor}/latest-history'
*/
LatestHistoryController.get = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: LatestHistoryController.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LatestHistoryController::__invoke
* @see app/Http/Controllers/LatestHistoryController.php:14
* @route '/monitor/{monitor}/latest-history'
*/
LatestHistoryController.head = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: LatestHistoryController.url(args, options),
    method: 'head',
})

export default LatestHistoryController