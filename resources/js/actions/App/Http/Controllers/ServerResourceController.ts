import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\ServerResourceController::index
* @see app/Http/Controllers/ServerResourceController.php:20
* @route '/settings/server-resources'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/settings/server-resources',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ServerResourceController::index
* @see app/Http/Controllers/ServerResourceController.php:20
* @route '/settings/server-resources'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ServerResourceController::index
* @see app/Http/Controllers/ServerResourceController.php:20
* @route '/settings/server-resources'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ServerResourceController::index
* @see app/Http/Controllers/ServerResourceController.php:20
* @route '/settings/server-resources'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ServerResourceController::metrics
* @see app/Http/Controllers/ServerResourceController.php:34
* @route '/api/server-resources'
*/
export const metrics = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: metrics.url(options),
    method: 'get',
})

metrics.definition = {
    methods: ["get","head"],
    url: '/api/server-resources',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ServerResourceController::metrics
* @see app/Http/Controllers/ServerResourceController.php:34
* @route '/api/server-resources'
*/
metrics.url = (options?: RouteQueryOptions) => {
    return metrics.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ServerResourceController::metrics
* @see app/Http/Controllers/ServerResourceController.php:34
* @route '/api/server-resources'
*/
metrics.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: metrics.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ServerResourceController::metrics
* @see app/Http/Controllers/ServerResourceController.php:34
* @route '/api/server-resources'
*/
metrics.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: metrics.url(options),
    method: 'head',
})

const ServerResourceController = { index, metrics }

export default ServerResourceController