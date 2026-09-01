import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\UnsubscribeMonitorController::__invoke
* @see app/Http/Controllers/UnsubscribeMonitorController.php:9
* @route '/monitor/{monitorId}/unsubscribe'
*/
const UnsubscribeMonitorController = (args: { monitorId: string | number } | [monitorId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: UnsubscribeMonitorController.url(args, options),
    method: 'delete',
})

UnsubscribeMonitorController.definition = {
    methods: ["delete"],
    url: '/monitor/{monitorId}/unsubscribe',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\UnsubscribeMonitorController::__invoke
* @see app/Http/Controllers/UnsubscribeMonitorController.php:9
* @route '/monitor/{monitorId}/unsubscribe'
*/
UnsubscribeMonitorController.url = (args: { monitorId: string | number } | [monitorId: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return UnsubscribeMonitorController.definition.url
            .replace('{monitorId}', parsedArgs.monitorId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\UnsubscribeMonitorController::__invoke
* @see app/Http/Controllers/UnsubscribeMonitorController.php:9
* @route '/monitor/{monitorId}/unsubscribe'
*/
UnsubscribeMonitorController.delete = (args: { monitorId: string | number } | [monitorId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: UnsubscribeMonitorController.url(args, options),
    method: 'delete',
})

export default UnsubscribeMonitorController