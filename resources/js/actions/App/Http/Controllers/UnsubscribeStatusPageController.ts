import { applyUrlDefaults, queryParams, type RouteDefinition, type RouteQueryOptions } from './../../../../wayfinder';
/**
 * @see \App\Http\Controllers\UnsubscribeStatusPageController::__invoke
 * @see app/Http/Controllers/UnsubscribeStatusPageController.php:9
 * @route '/status-subscription/unsubscribe/{token}'
 */
const UnsubscribeStatusPageController = (
    args: { token: string | number } | [token: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: UnsubscribeStatusPageController.url(args, options),
    method: 'get',
});

UnsubscribeStatusPageController.definition = {
    methods: ['get', 'head'],
    url: '/status-subscription/unsubscribe/{token}',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\UnsubscribeStatusPageController::__invoke
 * @see app/Http/Controllers/UnsubscribeStatusPageController.php:9
 * @route '/status-subscription/unsubscribe/{token}'
 */
UnsubscribeStatusPageController.url = (
    args: { token: string | number } | [token: string | number] | string | number,
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { token: args };
    }

    if (Array.isArray(args)) {
        args = {
            token: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        token: args.token,
    };

    return UnsubscribeStatusPageController.definition.url.replace('{token}', parsedArgs.token.toString()).replace(/\/+$/, '') + queryParams(options);
};

/**
 * @see \App\Http\Controllers\UnsubscribeStatusPageController::__invoke
 * @see app/Http/Controllers/UnsubscribeStatusPageController.php:9
 * @route '/status-subscription/unsubscribe/{token}'
 */
UnsubscribeStatusPageController.get = (
    args: { token: string | number } | [token: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: UnsubscribeStatusPageController.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\UnsubscribeStatusPageController::__invoke
 * @see app/Http/Controllers/UnsubscribeStatusPageController.php:9
 * @route '/status-subscription/unsubscribe/{token}'
 */
UnsubscribeStatusPageController.head = (
    args: { token: string | number } | [token: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: UnsubscribeStatusPageController.url(args, options),
    method: 'head',
});

export default UnsubscribeStatusPageController;
