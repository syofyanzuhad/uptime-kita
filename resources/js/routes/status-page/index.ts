import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
import publicMethodC5d39d from './public'
/**
* @see \App\Http\Controllers\PublicStatusPageController::publicMethod
* @see app/Http/Controllers/PublicStatusPageController.php:16
* @route '/status/{path}'
*/
export const publicMethod = (args: { path: string | number } | [path: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: publicMethod.url(args, options),
    method: 'get',
})

publicMethod.definition = {
    methods: ["get","head"],
    url: '/status/{path}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PublicStatusPageController::publicMethod
* @see app/Http/Controllers/PublicStatusPageController.php:16
* @route '/status/{path}'
*/
publicMethod.url = (args: { path: string | number } | [path: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return publicMethod.definition.url
            .replace('{path}', parsedArgs.path.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PublicStatusPageController::publicMethod
* @see app/Http/Controllers/PublicStatusPageController.php:16
* @route '/status/{path}'
*/
publicMethod.get = (args: { path: string | number } | [path: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: publicMethod.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PublicStatusPageController::publicMethod
* @see app/Http/Controllers/PublicStatusPageController.php:16
* @route '/status/{path}'
*/
publicMethod.head = (args: { path: string | number } | [path: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: publicMethod.url(args, options),
    method: 'head',
})

const statusPage = {
    public: Object.assign(publicMethod, publicMethodC5d39d),
}

export default statusPage