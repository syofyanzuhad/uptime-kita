import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\MonitorCompactController::index
* @see app/Http/Controllers/MonitorCompactController.php:17
* @route '/monitors/compact'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/monitors/compact',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MonitorCompactController::index
* @see app/Http/Controllers/MonitorCompactController.php:17
* @route '/monitors/compact'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MonitorCompactController::index
* @see app/Http/Controllers/MonitorCompactController.php:17
* @route '/monitors/compact'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MonitorCompactController::index
* @see app/Http/Controllers/MonitorCompactController.php:17
* @route '/monitors/compact'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

const MonitorCompactController = { index }

export default MonitorCompactController