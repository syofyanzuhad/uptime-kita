import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \Laravel\Telescope\Http\Controllers\MailEmlController::show
* @see vendor/laravel/telescope/src/Http/Controllers/MailEmlController.php:17
* @route '/telescope/telescope-api/mail/{telescopeEntryId}/download'
*/
export const show = (args: { telescopeEntryId: string | number } | [telescopeEntryId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/telescope/telescope-api/mail/{telescopeEntryId}/download',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Laravel\Telescope\Http\Controllers\MailEmlController::show
* @see vendor/laravel/telescope/src/Http/Controllers/MailEmlController.php:17
* @route '/telescope/telescope-api/mail/{telescopeEntryId}/download'
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
* @see \Laravel\Telescope\Http\Controllers\MailEmlController::show
* @see vendor/laravel/telescope/src/Http/Controllers/MailEmlController.php:17
* @route '/telescope/telescope-api/mail/{telescopeEntryId}/download'
*/
show.get = (args: { telescopeEntryId: string | number } | [telescopeEntryId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \Laravel\Telescope\Http\Controllers\MailEmlController::show
* @see vendor/laravel/telescope/src/Http/Controllers/MailEmlController.php:17
* @route '/telescope/telescope-api/mail/{telescopeEntryId}/download'
*/
show.head = (args: { telescopeEntryId: string | number } | [telescopeEntryId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

const MailEmlController = { show }

export default MailEmlController