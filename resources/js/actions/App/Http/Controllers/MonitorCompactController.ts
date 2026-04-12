import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\MonitorCompactController::index
* @see app/Http/Controllers/MonitorCompactController.php:16
* @route '/monitors'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/monitors',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MonitorCompactController::index
* @see app/Http/Controllers/MonitorCompactController.php:16
* @route '/monitors'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MonitorCompactController::index
* @see app/Http/Controllers/MonitorCompactController.php:16
* @route '/monitors'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MonitorCompactController::index
* @see app/Http/Controllers/MonitorCompactController.php:16
* @route '/monitors'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

const MonitorCompactController = { index }

export default MonitorCompactController