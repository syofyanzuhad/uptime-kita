import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\DomainCheckController::__invoke
* @see app/Http/Controllers/DomainCheckController.php:11
* @route '/api/check-domain'
*/
const DomainCheckController = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: DomainCheckController.url(options),
    method: 'get',
})

DomainCheckController.definition = {
    methods: ["get","head"],
    url: '/api/check-domain',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DomainCheckController::__invoke
* @see app/Http/Controllers/DomainCheckController.php:11
* @route '/api/check-domain'
*/
DomainCheckController.url = (options?: RouteQueryOptions) => {
    return DomainCheckController.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DomainCheckController::__invoke
* @see app/Http/Controllers/DomainCheckController.php:11
* @route '/api/check-domain'
*/
DomainCheckController.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: DomainCheckController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DomainCheckController::__invoke
* @see app/Http/Controllers/DomainCheckController.php:11
* @route '/api/check-domain'
*/
DomainCheckController.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: DomainCheckController.url(options),
    method: 'head',
})

export default DomainCheckController