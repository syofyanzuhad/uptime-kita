import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\SubscribeStatusPageController::__invoke
* @see app/Http/Controllers/SubscribeStatusPageController.php:15
* @route '/status/{path}/subscribe'
*/
const SubscribeStatusPageController = (args: { path: string | number } | [path: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: SubscribeStatusPageController.url(args, options),
    method: 'post',
})

SubscribeStatusPageController.definition = {
    methods: ["post"],
    url: '/status/{path}/subscribe',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SubscribeStatusPageController::__invoke
* @see app/Http/Controllers/SubscribeStatusPageController.php:15
* @route '/status/{path}/subscribe'
*/
SubscribeStatusPageController.url = (args: { path: string | number } | [path: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { path: args }
    }

    if (Array.isArray(args)) {
        args = {
            path: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        path: args.path,
    }

    return SubscribeStatusPageController.definition.url
            .replace('{path}', parsedArgs.path.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SubscribeStatusPageController::__invoke
* @see app/Http/Controllers/SubscribeStatusPageController.php:15
* @route '/status/{path}/subscribe'
*/
SubscribeStatusPageController.post = (args: { path: string | number } | [path: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: SubscribeStatusPageController.url(args, options),
    method: 'post',
})

export default SubscribeStatusPageController