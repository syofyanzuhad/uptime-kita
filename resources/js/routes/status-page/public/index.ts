import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\PublicStatusPageController::monitors
* @see app/Http/Controllers/PublicStatusPageController.php:51
* @route '/status/{path}/monitors'
*/
export const monitors = (args: { path: string | number } | [path: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: monitors.url(args, options),
    method: 'get',
})

monitors.definition = {
    methods: ["get","head"],
    url: '/status/{path}/monitors',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PublicStatusPageController::monitors
* @see app/Http/Controllers/PublicStatusPageController.php:51
* @route '/status/{path}/monitors'
*/
monitors.url = (args: { path: string | number } | [path: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { path: args }
    }

    if (Array.isArray(args)) {
        args = {
            path: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        path: args.path,
    }

    return monitors.definition.url
            .replace('{path}', parsedArgs.path.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PublicStatusPageController::monitors
* @see app/Http/Controllers/PublicStatusPageController.php:51
* @route '/status/{path}/monitors'
*/
monitors.get = (args: { path: string | number } | [path: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: monitors.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PublicStatusPageController::monitors
* @see app/Http/Controllers/PublicStatusPageController.php:51
* @route '/status/{path}/monitors'
*/
monitors.head = (args: { path: string | number } | [path: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: monitors.url(args, options),
    method: 'head',
})

const publicMethod = {
    monitors: Object.assign(monitors, monitors),
}

export default publicMethod