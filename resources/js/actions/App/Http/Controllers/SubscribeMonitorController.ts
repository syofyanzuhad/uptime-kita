import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\SubscribeMonitorController::__invoke
* @see app/Http/Controllers/SubscribeMonitorController.php:10
* @route '/monitor/{monitorId}/subscribe'
*/
const SubscribeMonitorController = (args: { monitorId: string | number } | [monitorId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: SubscribeMonitorController.url(args, options),
    method: 'post',
})

SubscribeMonitorController.definition = {
    methods: ["post"],
    url: '/monitor/{monitorId}/subscribe',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SubscribeMonitorController::__invoke
* @see app/Http/Controllers/SubscribeMonitorController.php:10
* @route '/monitor/{monitorId}/subscribe'
*/
SubscribeMonitorController.url = (args: { monitorId: string | number } | [monitorId: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { monitorId: args }
    }

    if (Array.isArray(args)) {
        args = {
            monitorId: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        monitorId: args.monitorId,
    }

    return SubscribeMonitorController.definition.url
            .replace('{monitorId}', parsedArgs.monitorId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SubscribeMonitorController::__invoke
* @see app/Http/Controllers/SubscribeMonitorController.php:10
* @route '/monitor/{monitorId}/subscribe'
*/
SubscribeMonitorController.post = (args: { monitorId: string | number } | [monitorId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: SubscribeMonitorController.url(args, options),
    method: 'post',
})

export default SubscribeMonitorController