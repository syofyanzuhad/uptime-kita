import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \Laravel\Telescope\Http\Controllers\MailHtmlController::show
* @see vendor/laravel/telescope/src/Http/Controllers/MailHtmlController.php:17
* @route '/telescope/telescope-api/mail/{telescopeEntryId}/preview'
*/
export const show = (args: { telescopeEntryId: string | number } | [telescopeEntryId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/telescope/telescope-api/mail/{telescopeEntryId}/preview',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Laravel\Telescope\Http\Controllers\MailHtmlController::show
* @see vendor/laravel/telescope/src/Http/Controllers/MailHtmlController.php:17
* @route '/telescope/telescope-api/mail/{telescopeEntryId}/preview'
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
* @see \Laravel\Telescope\Http\Controllers\MailHtmlController::show
* @see vendor/laravel/telescope/src/Http/Controllers/MailHtmlController.php:17
* @route '/telescope/telescope-api/mail/{telescopeEntryId}/preview'
*/
show.get = (args: { telescopeEntryId: string | number } | [telescopeEntryId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \Laravel\Telescope\Http\Controllers\MailHtmlController::show
* @see vendor/laravel/telescope/src/Http/Controllers/MailHtmlController.php:17
* @route '/telescope/telescope-api/mail/{telescopeEntryId}/preview'
*/
show.head = (args: { telescopeEntryId: string | number } | [telescopeEntryId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

const MailHtmlController = { show }

export default MailHtmlController