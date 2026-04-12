import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
/**
* @see \Spatie\Health\Http\Controllers\SimpleHealthCheckController::__invoke
* @see vendor/spatie/laravel-health/src/Http/Controllers/SimpleHealthCheckController.php:16
* @route '/health'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/health',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Spatie\Health\Http\Controllers\SimpleHealthCheckController::__invoke
* @see vendor/spatie/laravel-health/src/Http/Controllers/SimpleHealthCheckController.php:16
* @route '/health'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \Spatie\Health\Http\Controllers\SimpleHealthCheckController::__invoke
* @see vendor/spatie/laravel-health/src/Http/Controllers/SimpleHealthCheckController.php:16
* @route '/health'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \Spatie\Health\Http\Controllers\SimpleHealthCheckController::__invoke
* @see vendor/spatie/laravel-health/src/Http/Controllers/SimpleHealthCheckController.php:16
* @route '/health'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \Spatie\Health\Http\Controllers\HealthCheckJsonResultsController::__invoke
* @see vendor/spatie/laravel-health/src/Http/Controllers/HealthCheckJsonResultsController.php:13
* @route '/health/json'
*/
export const json = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: json.url(options),
    method: 'get',
})

json.definition = {
    methods: ["get","head"],
    url: '/health/json',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Spatie\Health\Http\Controllers\HealthCheckJsonResultsController::__invoke
* @see vendor/spatie/laravel-health/src/Http/Controllers/HealthCheckJsonResultsController.php:13
* @route '/health/json'
*/
json.url = (options?: RouteQueryOptions) => {
    return json.definition.url + queryParams(options)
}

/**
* @see \Spatie\Health\Http\Controllers\HealthCheckJsonResultsController::__invoke
* @see vendor/spatie/laravel-health/src/Http/Controllers/HealthCheckJsonResultsController.php:13
* @route '/health/json'
*/
json.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: json.url(options),
    method: 'get',
})

/**
* @see \Spatie\Health\Http\Controllers\HealthCheckJsonResultsController::__invoke
* @see vendor/spatie/laravel-health/src/Http/Controllers/HealthCheckJsonResultsController.php:13
* @route '/health/json'
*/
json.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: json.url(options),
    method: 'head',
})

/**
* @see \Spatie\Health\Http\Controllers\HealthCheckResultsController::__invoke
* @see vendor/spatie/laravel-health/src/Http/Controllers/HealthCheckResultsController.php:16
* @route '/health/results'
*/
export const results = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: results.url(options),
    method: 'get',
})

results.definition = {
    methods: ["get","head"],
    url: '/health/results',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Spatie\Health\Http\Controllers\HealthCheckResultsController::__invoke
* @see vendor/spatie/laravel-health/src/Http/Controllers/HealthCheckResultsController.php:16
* @route '/health/results'
*/
results.url = (options?: RouteQueryOptions) => {
    return results.definition.url + queryParams(options)
}

/**
* @see \Spatie\Health\Http\Controllers\HealthCheckResultsController::__invoke
* @see vendor/spatie/laravel-health/src/Http/Controllers/HealthCheckResultsController.php:16
* @route '/health/results'
*/
results.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: results.url(options),
    method: 'get',
})

/**
* @see \Spatie\Health\Http\Controllers\HealthCheckResultsController::__invoke
* @see vendor/spatie/laravel-health/src/Http/Controllers/HealthCheckResultsController.php:16
* @route '/health/results'
*/
results.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: results.url(options),
    method: 'head',
})

const health = {
    index: Object.assign(index, index),
    json: Object.assign(json, json),
    results: Object.assign(results, results),
}

export default health