import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\PublicIncidentController::publicMethod
* @see app/Http/Controllers/PublicIncidentController.php:16
* @route '/incidents'
*/
export const publicMethod = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: publicMethod.url(options),
    method: 'get',
})

publicMethod.definition = {
    methods: ["get","head"],
    url: '/incidents',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PublicIncidentController::publicMethod
* @see app/Http/Controllers/PublicIncidentController.php:16
* @route '/incidents'
*/
publicMethod.url = (options?: RouteQueryOptions) => {
    return publicMethod.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PublicIncidentController::publicMethod
* @see app/Http/Controllers/PublicIncidentController.php:16
* @route '/incidents'
*/
publicMethod.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: publicMethod.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PublicIncidentController::publicMethod
* @see app/Http/Controllers/PublicIncidentController.php:16
* @route '/incidents'
*/
publicMethod.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: publicMethod.url(options),
    method: 'head',
})

const incidents = {
    public: Object.assign(publicMethod, publicMethod),
}

export default incidents