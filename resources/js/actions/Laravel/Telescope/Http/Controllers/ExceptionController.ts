import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \Laravel\Telescope\Http\Controllers\ExceptionController::index
* @see vendor/laravel/telescope/src/Http/Controllers/ExceptionController.php:33
* @route '/telescope/telescope-api/exceptions'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: index.url(options),
    method: 'post',
})

index.definition = {
    methods: ["post"],
    url: '/telescope/telescope-api/exceptions',
} satisfies RouteDefinition<["post"]>

/**
* @see \Laravel\Telescope\Http\Controllers\ExceptionController::index
* @see vendor/laravel/telescope/src/Http/Controllers/ExceptionController.php:33
* @route '/telescope/telescope-api/exceptions'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \Laravel\Telescope\Http\Controllers\ExceptionController::index
* @see vendor/laravel/telescope/src/Http/Controllers/ExceptionController.php:33
* @route '/telescope/telescope-api/exceptions'
*/
index.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: index.url(options),
    method: 'post',
})

/**
* @see \Laravel\Telescope\Http\Controllers\ExceptionController::show
* @see vendor/laravel/telescope/src/Http/Controllers/ExceptionController.php:51
* @route '/telescope/telescope-api/exceptions/{telescopeEntryId}'
*/
export const show = (args: { telescopeEntryId: string | number } | [telescopeEntryId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/telescope/telescope-api/exceptions/{telescopeEntryId}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Laravel\Telescope\Http\Controllers\ExceptionController::show
* @see vendor/laravel/telescope/src/Http/Controllers/ExceptionController.php:51
* @route '/telescope/telescope-api/exceptions/{telescopeEntryId}'
*/
show.url = (args: { telescopeEntryId: string | number } | [telescopeEntryId: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { telescopeEntryId: args }
    }

    if (Array.isArray(args)) {
        args = {
            telescopeEntryId: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        telescopeEntryId: args.telescopeEntryId,
    }

    return show.definition.url
            .replace('{telescopeEntryId}', parsedArgs.telescopeEntryId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Laravel\Telescope\Http\Controllers\ExceptionController::show
* @see vendor/laravel/telescope/src/Http/Controllers/ExceptionController.php:51
* @route '/telescope/telescope-api/exceptions/{telescopeEntryId}'
*/
show.get = (args: { telescopeEntryId: string | number } | [telescopeEntryId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \Laravel\Telescope\Http\Controllers\ExceptionController::show
* @see vendor/laravel/telescope/src/Http/Controllers/ExceptionController.php:51
* @route '/telescope/telescope-api/exceptions/{telescopeEntryId}'
*/
show.head = (args: { telescopeEntryId: string | number } | [telescopeEntryId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \Laravel\Telescope\Http\Controllers\ExceptionController::update
* @see vendor/laravel/telescope/src/Http/Controllers/ExceptionController.php:42
* @route '/telescope/telescope-api/exceptions/{telescopeEntryId}'
*/
export const update = (args: { telescopeEntryId: string | number } | [telescopeEntryId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/telescope/telescope-api/exceptions/{telescopeEntryId}',
} satisfies RouteDefinition<["put"]>

/**
* @see \Laravel\Telescope\Http\Controllers\ExceptionController::update
* @see vendor/laravel/telescope/src/Http/Controllers/ExceptionController.php:42
* @route '/telescope/telescope-api/exceptions/{telescopeEntryId}'
*/
update.url = (args: { telescopeEntryId: string | number } | [telescopeEntryId: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { telescopeEntryId: args }
    }

    if (Array.isArray(args)) {
        args = {
            telescopeEntryId: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        telescopeEntryId: args.telescopeEntryId,
    }

    return update.definition.url
            .replace('{telescopeEntryId}', parsedArgs.telescopeEntryId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Laravel\Telescope\Http\Controllers\ExceptionController::update
* @see vendor/laravel/telescope/src/Http/Controllers/ExceptionController.php:42
* @route '/telescope/telescope-api/exceptions/{telescopeEntryId}'
*/
update.put = (args: { telescopeEntryId: string | number } | [telescopeEntryId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

const ExceptionController = { index, show, update }

export default ExceptionController