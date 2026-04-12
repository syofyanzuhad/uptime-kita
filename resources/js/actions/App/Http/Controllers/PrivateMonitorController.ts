import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\PrivateMonitorController::__invoke
* @see app/Http/Controllers/PrivateMonitorController.php:14
* @route '/private-monitors'
*/
const PrivateMonitorController = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: PrivateMonitorController.url(options),
    method: 'get',
})

PrivateMonitorController.definition = {
    methods: ["get","head"],
    url: '/private-monitors',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PrivateMonitorController::__invoke
* @see app/Http/Controllers/PrivateMonitorController.php:14
* @route '/private-monitors'
*/
PrivateMonitorController.url = (options?: RouteQueryOptions) => {
    return PrivateMonitorController.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PrivateMonitorController::__invoke
* @see app/Http/Controllers/PrivateMonitorController.php:14
* @route '/private-monitors'
*/
PrivateMonitorController.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: PrivateMonitorController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PrivateMonitorController::__invoke
* @see app/Http/Controllers/PrivateMonitorController.php:14
* @route '/private-monitors'
*/
PrivateMonitorController.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: PrivateMonitorController.url(options),
    method: 'head',
})

export default PrivateMonitorController