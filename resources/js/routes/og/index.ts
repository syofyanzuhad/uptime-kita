import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\OgImageController::monitors
* @see app/Http/Controllers/OgImageController.php:21
* @route '/og/monitors.png'
*/
export const monitors = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: monitors.url(options),
    method: 'get',
})

monitors.definition = {
    methods: ["get","head"],
    url: '/og/monitors.png',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\OgImageController::monitors
* @see app/Http/Controllers/OgImageController.php:21
* @route '/og/monitors.png'
*/
monitors.url = (options?: RouteQueryOptions) => {
    return monitors.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\OgImageController::monitors
* @see app/Http/Controllers/OgImageController.php:21
* @route '/og/monitors.png'
*/
monitors.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: monitors.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OgImageController::monitors
* @see app/Http/Controllers/OgImageController.php:21
* @route '/og/monitors.png'
*/
monitors.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: monitors.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\OgImageController::monitor
* @see app/Http/Controllers/OgImageController.php:42
* @route '/og/monitor/{domain}.png'
*/
export const monitor = (args: { domain: string | number } | [domain: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: monitor.url(args, options),
    method: 'get',
})

monitor.definition = {
    methods: ["get","head"],
    url: '/og/monitor/{domain}.png',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\OgImageController::monitor
* @see app/Http/Controllers/OgImageController.php:42
* @route '/og/monitor/{domain}.png'
*/
monitor.url = (args: { domain: string | number } | [domain: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return monitor.definition.url
            .replace('{domain}', parsedArgs.domain.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\OgImageController::monitor
* @see app/Http/Controllers/OgImageController.php:42
* @route '/og/monitor/{domain}.png'
*/
monitor.get = (args: { domain: string | number } | [domain: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: monitor.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OgImageController::monitor
* @see app/Http/Controllers/OgImageController.php:42
* @route '/og/monitor/{domain}.png'
*/
monitor.head = (args: { domain: string | number } | [domain: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: monitor.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\OgImageController::statusPage
* @see app/Http/Controllers/OgImageController.php:69
* @route '/og/status/{path}.png'
*/
export const statusPage = (args: { path: string | number } | [path: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: statusPage.url(args, options),
    method: 'get',
})

statusPage.definition = {
    methods: ["get","head"],
    url: '/og/status/{path}.png',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\OgImageController::statusPage
* @see app/Http/Controllers/OgImageController.php:69
* @route '/og/status/{path}.png'
*/
statusPage.url = (args: { path: string | number } | [path: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return statusPage.definition.url
            .replace('{path}', parsedArgs.path.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\OgImageController::statusPage
* @see app/Http/Controllers/OgImageController.php:69
* @route '/og/status/{path}.png'
*/
statusPage.get = (args: { path: string | number } | [path: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: statusPage.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OgImageController::statusPage
* @see app/Http/Controllers/OgImageController.php:69
* @route '/og/status/{path}.png'
*/
statusPage.head = (args: { path: string | number } | [path: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: statusPage.url(args, options),
    method: 'head',
})

const og = {
    monitors: Object.assign(monitors, monitors),
    monitor: Object.assign(monitor, monitor),
    statusPage: Object.assign(statusPage, statusPage),
}

export default og