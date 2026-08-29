import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \Fruitcake\LaravelDebugbar\Controllers\CacheController::deleteMethod
* @see vendor/barryvdh/laravel-debugbar/src/Controllers/CacheController.php:16
* @route '/_debugbar/cache/{key}'
*/
export const deleteMethod = (args: { key: string | number } | [key: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: deleteMethod.url(args, options),
    method: 'delete',
})

deleteMethod.definition = {
    methods: ["delete"],
    url: '/_debugbar/cache/{key}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \Fruitcake\LaravelDebugbar\Controllers\CacheController::deleteMethod
* @see vendor/barryvdh/laravel-debugbar/src/Controllers/CacheController.php:16
* @route '/_debugbar/cache/{key}'
*/
deleteMethod.url = (args: { key: string | number } | [key: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { key: args }
    }

    if (Array.isArray(args)) {
        args = {
            key: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        key: args.key,
    }

    return deleteMethod.definition.url
            .replace('{key}', parsedArgs.key.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Fruitcake\LaravelDebugbar\Controllers\CacheController::deleteMethod
* @see vendor/barryvdh/laravel-debugbar/src/Controllers/CacheController.php:16
* @route '/_debugbar/cache/{key}'
*/
deleteMethod.delete = (args: { key: string | number } | [key: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: deleteMethod.url(args, options),
    method: 'delete',
})

const cache = {
    delete: Object.assign(deleteMethod, deleteMethod),
}

export default cache