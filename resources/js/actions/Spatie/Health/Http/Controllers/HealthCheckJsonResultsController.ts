import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../wayfinder'
/**
* @see \Spatie\Health\Http\Controllers\HealthCheckJsonResultsController::__invoke
* @see vendor/spatie/laravel-health/src/Http/Controllers/HealthCheckJsonResultsController.php:13
* @route '/health/json'
*/
const HealthCheckJsonResultsController = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: HealthCheckJsonResultsController.url(options),
    method: 'get',
})

HealthCheckJsonResultsController.definition = {
    methods: ["get","head"],
    url: '/health/json',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Spatie\Health\Http\Controllers\HealthCheckJsonResultsController::__invoke
* @see vendor/spatie/laravel-health/src/Http/Controllers/HealthCheckJsonResultsController.php:13
* @route '/health/json'
*/
HealthCheckJsonResultsController.url = (options?: RouteQueryOptions) => {
    return HealthCheckJsonResultsController.definition.url + queryParams(options)
}

/**
* @see \Spatie\Health\Http\Controllers\HealthCheckJsonResultsController::__invoke
* @see vendor/spatie/laravel-health/src/Http/Controllers/HealthCheckJsonResultsController.php:13
* @route '/health/json'
*/
HealthCheckJsonResultsController.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: HealthCheckJsonResultsController.url(options),
    method: 'get',
})

/**
* @see \Spatie\Health\Http\Controllers\HealthCheckJsonResultsController::__invoke
* @see vendor/spatie/laravel-health/src/Http/Controllers/HealthCheckJsonResultsController.php:13
* @route '/health/json'
*/
HealthCheckJsonResultsController.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: HealthCheckJsonResultsController.url(options),
    method: 'head',
})

export default HealthCheckJsonResultsController