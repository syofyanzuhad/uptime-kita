import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \Barryvdh\Debugbar\Controllers\TelescopeController::show
* @see vendor/barryvdh/laravel-debugbar/src/Controllers/TelescopeController.php:15
* @route '/_debugbar/telescope/{id}'
*/
export const show = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/_debugbar/telescope/{id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Barryvdh\Debugbar\Controllers\TelescopeController::show
* @see vendor/barryvdh/laravel-debugbar/src/Controllers/TelescopeController.php:15
* @route '/_debugbar/telescope/{id}'
*/
show.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return show.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Barryvdh\Debugbar\Controllers\TelescopeController::show
* @see vendor/barryvdh/laravel-debugbar/src/Controllers/TelescopeController.php:15
* @route '/_debugbar/telescope/{id}'
*/
show.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \Barryvdh\Debugbar\Controllers\TelescopeController::show
* @see vendor/barryvdh/laravel-debugbar/src/Controllers/TelescopeController.php:15
* @route '/_debugbar/telescope/{id}'
*/
show.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

const TelescopeController = { show }

export default TelescopeController