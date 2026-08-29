import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \Laravel\Horizon\Http\Controllers\MonitoringController::index
* @see vendor/laravel/horizon/src/Http/Controllers/MonitoringController.php:47
* @route '/horizon/api/monitoring'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/horizon/api/monitoring',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Laravel\Horizon\Http\Controllers\MonitoringController::index
* @see vendor/laravel/horizon/src/Http/Controllers/MonitoringController.php:47
* @route '/horizon/api/monitoring'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\MonitoringController::index
* @see vendor/laravel/horizon/src/Http/Controllers/MonitoringController.php:47
* @route '/horizon/api/monitoring'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\MonitoringController::index
* @see vendor/laravel/horizon/src/Http/Controllers/MonitoringController.php:47
* @route '/horizon/api/monitoring'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \Laravel\Horizon\Http\Controllers\MonitoringController::store
* @see vendor/laravel/horizon/src/Http/Controllers/MonitoringController.php:104
* @route '/horizon/api/monitoring'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/horizon/api/monitoring',
} satisfies RouteDefinition<["post"]>

/**
* @see \Laravel\Horizon\Http\Controllers\MonitoringController::store
* @see vendor/laravel/horizon/src/Http/Controllers/MonitoringController.php:104
* @route '/horizon/api/monitoring'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\MonitoringController::store
* @see vendor/laravel/horizon/src/Http/Controllers/MonitoringController.php:104
* @route '/horizon/api/monitoring'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \Laravel\Horizon\Http\Controllers\MonitoringController::paginate
* @see vendor/laravel/horizon/src/Http/Controllers/MonitoringController.php:64
* @route '/horizon/api/monitoring/{tag}'
*/
export const paginate = (args: { tag: string | number } | [tag: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: paginate.url(args, options),
    method: 'get',
})

paginate.definition = {
    methods: ["get","head"],
    url: '/horizon/api/monitoring/{tag}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Laravel\Horizon\Http\Controllers\MonitoringController::paginate
* @see vendor/laravel/horizon/src/Http/Controllers/MonitoringController.php:64
* @route '/horizon/api/monitoring/{tag}'
*/
paginate.url = (args: { tag: string | number } | [tag: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { tag: args }
    }

    if (Array.isArray(args)) {
        args = {
            tag: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        tag: args.tag,
    }

    return paginate.definition.url
            .replace('{tag}', parsedArgs.tag.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\MonitoringController::paginate
* @see vendor/laravel/horizon/src/Http/Controllers/MonitoringController.php:64
* @route '/horizon/api/monitoring/{tag}'
*/
paginate.get = (args: { tag: string | number } | [tag: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: paginate.url(args, options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\MonitoringController::paginate
* @see vendor/laravel/horizon/src/Http/Controllers/MonitoringController.php:64
* @route '/horizon/api/monitoring/{tag}'
*/
paginate.head = (args: { tag: string | number } | [tag: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: paginate.url(args, options),
    method: 'head',
})

/**
* @see \Laravel\Horizon\Http\Controllers\MonitoringController::destroy
* @see vendor/laravel/horizon/src/Http/Controllers/MonitoringController.php:115
* @route '/horizon/api/monitoring/{tag}'
*/
export const destroy = (args: { tag: string | number } | [tag: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/horizon/api/monitoring/{tag}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \Laravel\Horizon\Http\Controllers\MonitoringController::destroy
* @see vendor/laravel/horizon/src/Http/Controllers/MonitoringController.php:115
* @route '/horizon/api/monitoring/{tag}'
*/
destroy.url = (args: { tag: string | number } | [tag: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { tag: args }
    }

    if (Array.isArray(args)) {
        args = {
            tag: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        tag: args.tag,
    }

    return destroy.definition.url
            .replace('{tag}', parsedArgs.tag.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\MonitoringController::destroy
* @see vendor/laravel/horizon/src/Http/Controllers/MonitoringController.php:115
* @route '/horizon/api/monitoring/{tag}'
*/
destroy.delete = (args: { tag: string | number } | [tag: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

const MonitoringController = { index, store, paginate, destroy }

export default MonitoringController