import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\ToggleMonitorActiveController::__invoke
* @see app/Http/Controllers/ToggleMonitorActiveController.php:17
* @route '/monitor/{monitorId}/toggle-active'
*/
const ToggleMonitorActiveController = (args: { monitorId: string | number } | [monitorId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: ToggleMonitorActiveController.url(args, options),
    method: 'post',
})

ToggleMonitorActiveController.definition = {
    methods: ["post"],
    url: '/monitor/{monitorId}/toggle-active',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ToggleMonitorActiveController::__invoke
* @see app/Http/Controllers/ToggleMonitorActiveController.php:17
* @route '/monitor/{monitorId}/toggle-active'
*/
ToggleMonitorActiveController.url = (args: { monitorId: string | number } | [monitorId: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return ToggleMonitorActiveController.definition.url
            .replace('{monitorId}', parsedArgs.monitorId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ToggleMonitorActiveController::__invoke
* @see app/Http/Controllers/ToggleMonitorActiveController.php:17
* @route '/monitor/{monitorId}/toggle-active'
*/
ToggleMonitorActiveController.post = (args: { monitorId: string | number } | [monitorId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: ToggleMonitorActiveController.url(args, options),
    method: 'post',
})

export default ToggleMonitorActiveController