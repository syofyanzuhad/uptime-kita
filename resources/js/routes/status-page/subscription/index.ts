import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\VerifyStatusPageSubscriptionController::__invoke
* @see app/Http/Controllers/VerifyStatusPageSubscriptionController.php:9
* @route '/status-subscription/verify/{token}'
*/
export const verify = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: verify.url(args, options),
    method: 'get',
})

verify.definition = {
    methods: ["get","head"],
    url: '/status-subscription/verify/{token}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\VerifyStatusPageSubscriptionController::__invoke
* @see app/Http/Controllers/VerifyStatusPageSubscriptionController.php:9
* @route '/status-subscription/verify/{token}'
*/
verify.url = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { token: args }
    }

    if (Array.isArray(args)) {
        args = {
            token: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        token: args.token,
    }

    return verify.definition.url
            .replace('{token}', parsedArgs.token.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\VerifyStatusPageSubscriptionController::__invoke
* @see app/Http/Controllers/VerifyStatusPageSubscriptionController.php:9
* @route '/status-subscription/verify/{token}'
*/
verify.get = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: verify.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\VerifyStatusPageSubscriptionController::__invoke
* @see app/Http/Controllers/VerifyStatusPageSubscriptionController.php:9
* @route '/status-subscription/verify/{token}'
*/
verify.head = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: verify.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\UnsubscribeStatusPageController::__invoke
* @see app/Http/Controllers/UnsubscribeStatusPageController.php:9
* @route '/status-subscription/unsubscribe/{token}'
*/
export const unsubscribe = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: unsubscribe.url(args, options),
    method: 'get',
})

unsubscribe.definition = {
    methods: ["get","head"],
    url: '/status-subscription/unsubscribe/{token}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\UnsubscribeStatusPageController::__invoke
* @see app/Http/Controllers/UnsubscribeStatusPageController.php:9
* @route '/status-subscription/unsubscribe/{token}'
*/
unsubscribe.url = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { token: args }
    }

    if (Array.isArray(args)) {
        args = {
            token: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        token: args.token,
    }

    return unsubscribe.definition.url
            .replace('{token}', parsedArgs.token.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\UnsubscribeStatusPageController::__invoke
* @see app/Http/Controllers/UnsubscribeStatusPageController.php:9
* @route '/status-subscription/unsubscribe/{token}'
*/
unsubscribe.get = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: unsubscribe.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\UnsubscribeStatusPageController::__invoke
* @see app/Http/Controllers/UnsubscribeStatusPageController.php:9
* @route '/status-subscription/unsubscribe/{token}'
*/
unsubscribe.head = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: unsubscribe.url(args, options),
    method: 'head',
})

const subscription = {
    verify: Object.assign(verify, verify),
    unsubscribe: Object.assign(unsubscribe, unsubscribe),
}

export default subscription