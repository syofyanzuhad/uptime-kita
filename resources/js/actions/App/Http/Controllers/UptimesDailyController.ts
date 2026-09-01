import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\UptimesDailyController::__invoke
* @see app/Http/Controllers/UptimesDailyController.php:13
* @route '/monitor/{monitor}/uptimes-daily'
*/
const UptimesDailyController = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: UptimesDailyController.url(args, options),
    method: 'get',
})

UptimesDailyController.definition = {
    methods: ["get","head"],
    url: '/monitor/{monitor}/uptimes-daily',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\UptimesDailyController::__invoke
* @see app/Http/Controllers/UptimesDailyController.php:13
* @route '/monitor/{monitor}/uptimes-daily'
*/
UptimesDailyController.url = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return UptimesDailyController.definition.url
            .replace('{monitor}', parsedArgs.monitor.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\UptimesDailyController::__invoke
* @see app/Http/Controllers/UptimesDailyController.php:13
* @route '/monitor/{monitor}/uptimes-daily'
*/
UptimesDailyController.get = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: UptimesDailyController.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\UptimesDailyController::__invoke
* @see app/Http/Controllers/UptimesDailyController.php:13
* @route '/monitor/{monitor}/uptimes-daily'
*/
UptimesDailyController.head = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: UptimesDailyController.url(args, options),
    method: 'head',
})

export default UptimesDailyController