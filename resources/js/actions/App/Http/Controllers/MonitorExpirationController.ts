import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\MonitorExpirationController::index
* @see app/Http/Controllers/MonitorExpirationController.php:17
* @route '/monitors/expiration'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/monitors/expiration',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MonitorExpirationController::index
* @see app/Http/Controllers/MonitorExpirationController.php:17
* @route '/monitors/expiration'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MonitorExpirationController::index
* @see app/Http/Controllers/MonitorExpirationController.php:17
* @route '/monitors/expiration'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MonitorExpirationController::index
* @see app/Http/Controllers/MonitorExpirationController.php:17
* @route '/monitors/expiration'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

const MonitorExpirationController = { index }

export default MonitorExpirationController