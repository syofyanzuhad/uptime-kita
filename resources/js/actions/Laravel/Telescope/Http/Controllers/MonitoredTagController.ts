import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../wayfinder'
/**
* @see \Laravel\Telescope\Http\Controllers\MonitoredTagController::index
* @see vendor/laravel/telescope/src/Http/Controllers/MonitoredTagController.php:34
* @route '/telescope/telescope-api/monitored-tags'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/telescope/telescope-api/monitored-tags',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Laravel\Telescope\Http\Controllers\MonitoredTagController::index
* @see vendor/laravel/telescope/src/Http/Controllers/MonitoredTagController.php:34
* @route '/telescope/telescope-api/monitored-tags'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \Laravel\Telescope\Http\Controllers\MonitoredTagController::index
* @see vendor/laravel/telescope/src/Http/Controllers/MonitoredTagController.php:34
* @route '/telescope/telescope-api/monitored-tags'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \Laravel\Telescope\Http\Controllers\MonitoredTagController::index
* @see vendor/laravel/telescope/src/Http/Controllers/MonitoredTagController.php:34
* @route '/telescope/telescope-api/monitored-tags'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \Laravel\Telescope\Http\Controllers\MonitoredTagController::store
* @see vendor/laravel/telescope/src/Http/Controllers/MonitoredTagController.php:47
* @route '/telescope/telescope-api/monitored-tags'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/telescope/telescope-api/monitored-tags',
} satisfies RouteDefinition<["post"]>

/**
* @see \Laravel\Telescope\Http\Controllers\MonitoredTagController::store
* @see vendor/laravel/telescope/src/Http/Controllers/MonitoredTagController.php:47
* @route '/telescope/telescope-api/monitored-tags'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \Laravel\Telescope\Http\Controllers\MonitoredTagController::store
* @see vendor/laravel/telescope/src/Http/Controllers/MonitoredTagController.php:47
* @route '/telescope/telescope-api/monitored-tags'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \Laravel\Telescope\Http\Controllers\MonitoredTagController::destroy
* @see vendor/laravel/telescope/src/Http/Controllers/MonitoredTagController.php:58
* @route '/telescope/telescope-api/monitored-tags/delete'
*/
export const destroy = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: destroy.url(options),
    method: 'post',
})

destroy.definition = {
    methods: ["post"],
    url: '/telescope/telescope-api/monitored-tags/delete',
} satisfies RouteDefinition<["post"]>

/**
* @see \Laravel\Telescope\Http\Controllers\MonitoredTagController::destroy
* @see vendor/laravel/telescope/src/Http/Controllers/MonitoredTagController.php:58
* @route '/telescope/telescope-api/monitored-tags/delete'
*/
destroy.url = (options?: RouteQueryOptions) => {
    return destroy.definition.url + queryParams(options)
}

/**
* @see \Laravel\Telescope\Http\Controllers\MonitoredTagController::destroy
* @see vendor/laravel/telescope/src/Http/Controllers/MonitoredTagController.php:58
* @route '/telescope/telescope-api/monitored-tags/delete'
*/
destroy.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: destroy.url(options),
    method: 'post',
})

const MonitoredTagController = { index, store, destroy }

export default MonitoredTagController