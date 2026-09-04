import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\PublicMonitorShowController::show
* @see app/Http/Controllers/PublicMonitorShowController.php:20
* @route '/m/{domain}'
*/
export const show = (args: { domain: string | number } | [domain: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/m/{domain}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PublicMonitorShowController::show
* @see app/Http/Controllers/PublicMonitorShowController.php:20
* @route '/m/{domain}'
*/
show.url = (args: { domain: string | number } | [domain: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { domain: args }
    }

    if (Array.isArray(args)) {
        args = {
            domain: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        domain: args.domain,
    }

    return show.definition.url
            .replace('{domain}', parsedArgs.domain.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PublicMonitorShowController::show
* @see app/Http/Controllers/PublicMonitorShowController.php:20
* @route '/m/{domain}'
*/
show.get = (args: { domain: string | number } | [domain: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PublicMonitorShowController::show
* @see app/Http/Controllers/PublicMonitorShowController.php:20
* @route '/m/{domain}'
*/
show.head = (args: { domain: string | number } | [domain: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

const publicMethod = {
    show: Object.assign(show, show),
}

export default publicMethod