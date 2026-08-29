import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \Fruitcake\LaravelDebugbar\Controllers\AssetController::getAssets
* @see vendor/barryvdh/laravel-debugbar/src/Controllers/AssetController.php:16
* @route '/_debugbar/assets'
*/
export const getAssets = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getAssets.url(options),
    method: 'get',
})

getAssets.definition = {
    methods: ["get","head"],
    url: '/_debugbar/assets',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Fruitcake\LaravelDebugbar\Controllers\AssetController::getAssets
* @see vendor/barryvdh/laravel-debugbar/src/Controllers/AssetController.php:16
* @route '/_debugbar/assets'
*/
getAssets.url = (options?: RouteQueryOptions) => {
    return getAssets.definition.url + queryParams(options)
}

/**
* @see \Fruitcake\LaravelDebugbar\Controllers\AssetController::getAssets
* @see vendor/barryvdh/laravel-debugbar/src/Controllers/AssetController.php:16
* @route '/_debugbar/assets'
*/
getAssets.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getAssets.url(options),
    method: 'get',
})

/**
* @see \Fruitcake\LaravelDebugbar\Controllers\AssetController::getAssets
* @see vendor/barryvdh/laravel-debugbar/src/Controllers/AssetController.php:16
* @route '/_debugbar/assets'
*/
getAssets.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: getAssets.url(options),
    method: 'head',
})

const AssetController = { getAssets }

export default AssetController