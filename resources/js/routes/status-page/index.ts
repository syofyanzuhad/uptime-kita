import { applyUrlDefaults, queryParams, type RouteDefinition, type RouteQueryOptions } from './../../wayfinder';
import publicMethodC5d39d from './public';
import subscription from './subscription';
/**
 * @see \App\Http\Controllers\PublicStatusPageController::publicMethod
 * @see app/Http/Controllers/PublicStatusPageController.php:17
 * @route '/status/{path}'
 */
export const publicMethod = (
    args: { path: string | number } | [path: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: publicMethod.url(args, options),
    method: 'get',
});

publicMethod.definition = {
    methods: ['get', 'head'],
    url: '/status/{path}',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\PublicStatusPageController::publicMethod
 * @see app/Http/Controllers/PublicStatusPageController.php:17
 * @route '/status/{path}'
 */
publicMethod.url = (args: { path: string | number } | [path: string | number] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { path: args };
    }

    if (Array.isArray(args)) {
        args = {
            path: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        path: args.path,
    };

    return publicMethod.definition.url.replace('{path}', parsedArgs.path.toString()).replace(/\/+$/, '') + queryParams(options);
};

/**
 * @see \App\Http\Controllers\PublicStatusPageController::publicMethod
 * @see app/Http/Controllers/PublicStatusPageController.php:17
 * @route '/status/{path}'
 */
publicMethod.get = (
    args: { path: string | number } | [path: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: publicMethod.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\PublicStatusPageController::publicMethod
 * @see app/Http/Controllers/PublicStatusPageController.php:17
 * @route '/status/{path}'
 */
publicMethod.head = (
    args: { path: string | number } | [path: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: publicMethod.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\SubscribeStatusPageController::__invoke
 * @see app/Http/Controllers/SubscribeStatusPageController.php:13
 * @route '/status/{path}/subscribe'
 */
export const subscribe = (
    args: { path: string | number } | [path: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: subscribe.url(args, options),
    method: 'post',
});

subscribe.definition = {
    methods: ['post'],
    url: '/status/{path}/subscribe',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\SubscribeStatusPageController::__invoke
 * @see app/Http/Controllers/SubscribeStatusPageController.php:13
 * @route '/status/{path}/subscribe'
 */
subscribe.url = (args: { path: string | number } | [path: string | number] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { path: args };
    }

    if (Array.isArray(args)) {
        args = {
            path: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        path: args.path,
    };

    return subscribe.definition.url.replace('{path}', parsedArgs.path.toString()).replace(/\/+$/, '') + queryParams(options);
};

/**
 * @see \App\Http\Controllers\SubscribeStatusPageController::__invoke
 * @see app/Http/Controllers/SubscribeStatusPageController.php:13
 * @route '/status/{path}/subscribe'
 */
subscribe.post = (
    args: { path: string | number } | [path: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: subscribe.url(args, options),
    method: 'post',
});

const statusPage = {
    public: Object.assign(publicMethod, publicMethodC5d39d),
    subscribe: Object.assign(subscribe, subscribe),
    subscription: Object.assign(subscription, subscription),
};

export default statusPage;
