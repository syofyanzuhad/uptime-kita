import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\MonitorCompactController::index
* @see app/Http/Controllers/MonitorCompactController.php:16
* @route '/monitor/compact'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/monitor/compact',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MonitorCompactController::index
* @see app/Http/Controllers/MonitorCompactController.php:16
* @route '/monitor/compact'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MonitorCompactController::index
* @see app/Http/Controllers/MonitorCompactController.php:16
* @route '/monitor/compact'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MonitorCompactController::index
* @see app/Http/Controllers/MonitorCompactController.php:16
* @route '/monitor/compact'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

const MonitorCompactController = { index }

export default MonitorCompactController