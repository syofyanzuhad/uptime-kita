import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\PublicMonitorController::index
* @see app/Http/Controllers/PublicMonitorController.php:18
* @route '/'
*/
const index980bb49ee7ae63891f1d891d2fbcf1c9 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index980bb49ee7ae63891f1d891d2fbcf1c9.url(options),
    method: 'get',
})

index980bb49ee7ae63891f1d891d2fbcf1c9.definition = {
    methods: ["get","head"],
    url: '/',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PublicMonitorController::index
* @see app/Http/Controllers/PublicMonitorController.php:18
* @route '/'
*/
index980bb49ee7ae63891f1d891d2fbcf1c9.url = (options?: RouteQueryOptions) => {
    return index980bb49ee7ae63891f1d891d2fbcf1c9.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PublicMonitorController::index
* @see app/Http/Controllers/PublicMonitorController.php:18
* @route '/'
*/
index980bb49ee7ae63891f1d891d2fbcf1c9.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index980bb49ee7ae63891f1d891d2fbcf1c9.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PublicMonitorController::index
* @see app/Http/Controllers/PublicMonitorController.php:18
* @route '/'
*/
index980bb49ee7ae63891f1d891d2fbcf1c9.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index980bb49ee7ae63891f1d891d2fbcf1c9.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PublicMonitorController::index
* @see app/Http/Controllers/PublicMonitorController.php:18
* @route '/public-monitors'
*/
const indexc50b27167f310089cd51bd16c2529933 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: indexc50b27167f310089cd51bd16c2529933.url(options),
    method: 'get',
})

indexc50b27167f310089cd51bd16c2529933.definition = {
    methods: ["get","head"],
    url: '/public-monitors',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PublicMonitorController::index
* @see app/Http/Controllers/PublicMonitorController.php:18
* @route '/public-monitors'
*/
indexc50b27167f310089cd51bd16c2529933.url = (options?: RouteQueryOptions) => {
    return indexc50b27167f310089cd51bd16c2529933.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PublicMonitorController::index
* @see app/Http/Controllers/PublicMonitorController.php:18
* @route '/public-monitors'
*/
indexc50b27167f310089cd51bd16c2529933.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: indexc50b27167f310089cd51bd16c2529933.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PublicMonitorController::index
* @see app/Http/Controllers/PublicMonitorController.php:18
* @route '/public-monitors'
*/
indexc50b27167f310089cd51bd16c2529933.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: indexc50b27167f310089cd51bd16c2529933.url(options),
    method: 'head',
})

export const index = {
    '/': index980bb49ee7ae63891f1d891d2fbcf1c9,
    '/public-monitors': indexc50b27167f310089cd51bd16c2529933,
}

const PublicMonitorController = { index }

export default PublicMonitorController