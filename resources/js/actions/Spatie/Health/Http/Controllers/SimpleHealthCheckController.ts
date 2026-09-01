import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../wayfinder'
/**
* @see \Spatie\Health\Http\Controllers\SimpleHealthCheckController::__invoke
* @see vendor/spatie/laravel-health/src/Http/Controllers/SimpleHealthCheckController.php:16
* @route '/health'
*/
const SimpleHealthCheckController = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: SimpleHealthCheckController.url(options),
    method: 'get',
})

SimpleHealthCheckController.definition = {
    methods: ["get","head"],
    url: '/health',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Spatie\Health\Http\Controllers\SimpleHealthCheckController::__invoke
* @see vendor/spatie/laravel-health/src/Http/Controllers/SimpleHealthCheckController.php:16
* @route '/health'
*/
SimpleHealthCheckController.url = (options?: RouteQueryOptions) => {
    return SimpleHealthCheckController.definition.url + queryParams(options)
}

/**
* @see \Spatie\Health\Http\Controllers\SimpleHealthCheckController::__invoke
* @see vendor/spatie/laravel-health/src/Http/Controllers/SimpleHealthCheckController.php:16
* @route '/health'
*/
SimpleHealthCheckController.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: SimpleHealthCheckController.url(options),
    method: 'get',
})

/**
* @see \Spatie\Health\Http\Controllers\SimpleHealthCheckController::__invoke
* @see vendor/spatie/laravel-health/src/Http/Controllers/SimpleHealthCheckController.php:16
* @route '/health'
*/
SimpleHealthCheckController.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: SimpleHealthCheckController.url(options),
    method: 'head',
})

export default SimpleHealthCheckController