import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../wayfinder'
/**
* @see \Laravel\Telescope\Http\Controllers\DumpController::index
* @see vendor/laravel/telescope/src/Http/Controllers/DumpController.php:42
* @route '/telescope/telescope-api/dumps'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: index.url(options),
    method: 'post',
})

index.definition = {
    methods: ["post"],
    url: '/telescope/telescope-api/dumps',
} satisfies RouteDefinition<["post"]>

/**
* @see \Laravel\Telescope\Http\Controllers\DumpController::index
* @see vendor/laravel/telescope/src/Http/Controllers/DumpController.php:42
* @route '/telescope/telescope-api/dumps'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \Laravel\Telescope\Http\Controllers\DumpController::index
* @see vendor/laravel/telescope/src/Http/Controllers/DumpController.php:42
* @route '/telescope/telescope-api/dumps'
*/
index.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: index.url(options),
    method: 'post',
})

const DumpController = { index }

export default DumpController