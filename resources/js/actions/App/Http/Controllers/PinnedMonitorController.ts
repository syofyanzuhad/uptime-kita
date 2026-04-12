import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\PinnedMonitorController::index
* @see app/Http/Controllers/PinnedMonitorController.php:14
* @route '/pinned-monitors'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/pinned-monitors',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PinnedMonitorController::index
* @see app/Http/Controllers/PinnedMonitorController.php:14
* @route '/pinned-monitors'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PinnedMonitorController::index
* @see app/Http/Controllers/PinnedMonitorController.php:14
* @route '/pinned-monitors'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PinnedMonitorController::index
* @see app/Http/Controllers/PinnedMonitorController.php:14
* @route '/pinned-monitors'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PinnedMonitorController::toggle
* @see app/Http/Controllers/PinnedMonitorController.php:76
* @route '/monitor/{monitorId}/toggle-pin'
*/
export const toggle = (args: { monitorId: string | number } | [monitorId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: toggle.url(args, options),
    method: 'post',
})

toggle.definition = {
    methods: ["post"],
    url: '/monitor/{monitorId}/toggle-pin',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PinnedMonitorController::toggle
* @see app/Http/Controllers/PinnedMonitorController.php:76
* @route '/monitor/{monitorId}/toggle-pin'
*/
toggle.url = (args: { monitorId: string | number } | [monitorId: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return toggle.definition.url
            .replace('{monitorId}', parsedArgs.monitorId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PinnedMonitorController::toggle
* @see app/Http/Controllers/PinnedMonitorController.php:76
* @route '/monitor/{monitorId}/toggle-pin'
*/
toggle.post = (args: { monitorId: string | number } | [monitorId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: toggle.url(args, options),
    method: 'post',
})

const PinnedMonitorController = { index, toggle }

export default PinnedMonitorController