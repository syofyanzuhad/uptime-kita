import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\PublicIncidentController::index
* @see app/Http/Controllers/PublicIncidentController.php:16
* @route '/incidents'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/incidents',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PublicIncidentController::index
* @see app/Http/Controllers/PublicIncidentController.php:16
* @route '/incidents'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PublicIncidentController::index
* @see app/Http/Controllers/PublicIncidentController.php:16
* @route '/incidents'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PublicIncidentController::index
* @see app/Http/Controllers/PublicIncidentController.php:16
* @route '/incidents'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

const PublicIncidentController = { index }

export default PublicIncidentController