import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Auth\SocialiteController::redirectToProvider
* @see app/Http/Controllers/Auth/SocialiteController.php:15
* @route '/auth/{provider}'
*/
export const redirectToProvider = (args: { provider: string | number } | [provider: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: redirectToProvider.url(args, options),
    method: 'get',
})

redirectToProvider.definition = {
    methods: ["get","head"],
    url: '/auth/{provider}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Auth\SocialiteController::redirectToProvider
* @see app/Http/Controllers/Auth/SocialiteController.php:15
* @route '/auth/{provider}'
*/
redirectToProvider.url = (args: { provider: string | number } | [provider: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { provider: args }
    }

    if (Array.isArray(args)) {
        args = {
            provider: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        provider: args.provider,
    }

    return redirectToProvider.definition.url
            .replace('{provider}', parsedArgs.provider.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Auth\SocialiteController::redirectToProvider
* @see app/Http/Controllers/Auth/SocialiteController.php:15
* @route '/auth/{provider}'
*/
redirectToProvider.get = (args: { provider: string | number } | [provider: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: redirectToProvider.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Auth\SocialiteController::redirectToProvider
* @see app/Http/Controllers/Auth/SocialiteController.php:15
* @route '/auth/{provider}'
*/
redirectToProvider.head = (args: { provider: string | number } | [provider: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: redirectToProvider.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Auth\SocialiteController::handleProvideCallback
* @see app/Http/Controllers/Auth/SocialiteController.php:23
* @route '/auth/{provider}/callback'
*/
export const handleProvideCallback = (args: { provider: string | number } | [provider: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: handleProvideCallback.url(args, options),
    method: 'get',
})

handleProvideCallback.definition = {
    methods: ["get","head"],
    url: '/auth/{provider}/callback',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Auth\SocialiteController::handleProvideCallback
* @see app/Http/Controllers/Auth/SocialiteController.php:23
* @route '/auth/{provider}/callback'
*/
handleProvideCallback.url = (args: { provider: string | number } | [provider: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { provider: args }
    }

    if (Array.isArray(args)) {
        args = {
            provider: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        provider: args.provider,
    }

    return handleProvideCallback.definition.url
            .replace('{provider}', parsedArgs.provider.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Auth\SocialiteController::handleProvideCallback
* @see app/Http/Controllers/Auth/SocialiteController.php:23
* @route '/auth/{provider}/callback'
*/
handleProvideCallback.get = (args: { provider: string | number } | [provider: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: handleProvideCallback.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Auth\SocialiteController::handleProvideCallback
* @see app/Http/Controllers/Auth/SocialiteController.php:23
* @route '/auth/{provider}/callback'
*/
handleProvideCallback.head = (args: { provider: string | number } | [provider: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: handleProvideCallback.url(args, options),
    method: 'head',
})

const SocialiteController = { redirectToProvider, handleProvideCallback }

export default SocialiteController