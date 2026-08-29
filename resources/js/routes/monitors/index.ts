import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\MonitorExpirationController::expiration
* @see app/Http/Controllers/MonitorExpirationController.php:17
* @route '/monitors/expiration'
*/
export const expiration = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: expiration.url(options),
    method: 'get',
})

expiration.definition = {
    methods: ["get","head"],
    url: '/monitors/expiration',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MonitorExpirationController::expiration
* @see app/Http/Controllers/MonitorExpirationController.php:17
* @route '/monitors/expiration'
*/
expiration.url = (options?: RouteQueryOptions) => {
    return expiration.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MonitorExpirationController::expiration
* @see app/Http/Controllers/MonitorExpirationController.php:17
* @route '/monitors/expiration'
*/
expiration.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: expiration.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MonitorExpirationController::expiration
* @see app/Http/Controllers/MonitorExpirationController.php:17
* @route '/monitors/expiration'
*/
expiration.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: expiration.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MonitorListController::list
* @see app/Http/Controllers/MonitorListController.php:15
* @route '/monitors/{type}'
*/
export const list = (args: { type: string | number } | [type: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: list.url(args, options),
    method: 'get',
})

list.definition = {
    methods: ["get","head"],
    url: '/monitors/{type}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MonitorListController::list
* @see app/Http/Controllers/MonitorListController.php:15
* @route '/monitors/{type}'
*/
list.url = (args: { type: string | number } | [type: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { type: args }
    }

    if (Array.isArray(args)) {
        args = {
            type: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        type: args.type,
    }

    return list.definition.url
            .replace('{type}', parsedArgs.type.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MonitorListController::list
* @see app/Http/Controllers/MonitorListController.php:15
* @route '/monitors/{type}'
*/
list.get = (args: { type: string | number } | [type: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: list.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MonitorListController::list
* @see app/Http/Controllers/MonitorListController.php:15
* @route '/monitors/{type}'
*/
list.head = (args: { type: string | number } | [type: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: list.url(args, options),
    method: 'head',
})

const monitors = {
    expiration: Object.assign(expiration, expiration),
    list: Object.assign(list, list),
}

export default monitors