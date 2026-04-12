import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \Barryvdh\Debugbar\Controllers\OpenHandlerController::handle
* @see vendor/barryvdh/laravel-debugbar/src/Controllers/OpenHandlerController.php:43
* @route '/_debugbar/open'
*/
export const handle = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: handle.url(options),
    method: 'get',
})

handle.definition = {
    methods: ["get","head"],
    url: '/_debugbar/open',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Barryvdh\Debugbar\Controllers\OpenHandlerController::handle
* @see vendor/barryvdh/laravel-debugbar/src/Controllers/OpenHandlerController.php:43
* @route '/_debugbar/open'
*/
handle.url = (options?: RouteQueryOptions) => {
    return handle.definition.url + queryParams(options)
}

/**
* @see \Barryvdh\Debugbar\Controllers\OpenHandlerController::handle
* @see vendor/barryvdh/laravel-debugbar/src/Controllers/OpenHandlerController.php:43
* @route '/_debugbar/open'
*/
handle.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: handle.url(options),
    method: 'get',
})

/**
* @see \Barryvdh\Debugbar\Controllers\OpenHandlerController::handle
* @see vendor/barryvdh/laravel-debugbar/src/Controllers/OpenHandlerController.php:43
* @route '/_debugbar/open'
*/
handle.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: handle.url(options),
    method: 'head',
})

/**
* @see \Barryvdh\Debugbar\Controllers\OpenHandlerController::clockwork
* @see vendor/barryvdh/laravel-debugbar/src/Controllers/OpenHandlerController.php:77
* @route '/_debugbar/clockwork/{id}'
*/
export const clockwork = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: clockwork.url(args, options),
    method: 'get',
})

clockwork.definition = {
    methods: ["get","head"],
    url: '/_debugbar/clockwork/{id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Barryvdh\Debugbar\Controllers\OpenHandlerController::clockwork
* @see vendor/barryvdh/laravel-debugbar/src/Controllers/OpenHandlerController.php:77
* @route '/_debugbar/clockwork/{id}'
*/
clockwork.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    if (Array.isArray(args)) {
        args = {
            id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        id: args.id,
    }

    return clockwork.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Barryvdh\Debugbar\Controllers\OpenHandlerController::clockwork
* @see vendor/barryvdh/laravel-debugbar/src/Controllers/OpenHandlerController.php:77
* @route '/_debugbar/clockwork/{id}'
*/
clockwork.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: clockwork.url(args, options),
    method: 'get',
})

/**
* @see \Barryvdh\Debugbar\Controllers\OpenHandlerController::clockwork
* @see vendor/barryvdh/laravel-debugbar/src/Controllers/OpenHandlerController.php:77
* @route '/_debugbar/clockwork/{id}'
*/
clockwork.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: clockwork.url(args, options),
    method: 'head',
})

const OpenHandlerController = { handle, clockwork }

export default OpenHandlerController