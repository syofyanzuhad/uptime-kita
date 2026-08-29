import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
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

const serverResources = {
    index: Object.assign(index, index),
}

export default serverResources