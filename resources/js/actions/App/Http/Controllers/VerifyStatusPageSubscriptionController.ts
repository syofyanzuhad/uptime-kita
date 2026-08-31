import { applyUrlDefaults, queryParams, type RouteDefinition, type RouteQueryOptions } from './../../../../wayfinder';
/**
 * @see \App\Http\Controllers\VerifyStatusPageSubscriptionController::__invoke
 * @see app/Http/Controllers/VerifyStatusPageSubscriptionController.php:9
 * @route '/status-subscription/verify/{token}'
 */
const VerifyStatusPageSubscriptionController = (
    args: { token: string | number } | [token: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: VerifyStatusPageSubscriptionController.url(args, options),
    method: 'get',
});

VerifyStatusPageSubscriptionController.definition = {
    methods: ['get', 'head'],
    url: '/status-subscription/verify/{token}',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\VerifyStatusPageSubscriptionController::__invoke
 * @see app/Http/Controllers/VerifyStatusPageSubscriptionController.php:9
 * @route '/status-subscription/verify/{token}'
 */
VerifyStatusPageSubscriptionController.url = (
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

    return (
        VerifyStatusPageSubscriptionController.definition.url.replace('{token}', parsedArgs.token.toString()).replace(/\/+$/, '') +
        queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\VerifyStatusPageSubscriptionController::__invoke
 * @see app/Http/Controllers/VerifyStatusPageSubscriptionController.php:9
 * @route '/status-subscription/verify/{token}'
 */
VerifyStatusPageSubscriptionController.get = (
    args: { token: string | number } | [token: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: VerifyStatusPageSubscriptionController.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\VerifyStatusPageSubscriptionController::__invoke
 * @see app/Http/Controllers/VerifyStatusPageSubscriptionController.php:9
 * @route '/status-subscription/verify/{token}'
 */
VerifyStatusPageSubscriptionController.head = (
    args: { token: string | number } | [token: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: VerifyStatusPageSubscriptionController.url(args, options),
    method: 'head',
});

export default VerifyStatusPageSubscriptionController;
