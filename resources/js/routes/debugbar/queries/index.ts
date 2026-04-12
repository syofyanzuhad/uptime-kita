import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../wayfinder'
/**
* @see \Barryvdh\Debugbar\Controllers\QueriesController::explain
* @see vendor/barryvdh/laravel-debugbar/src/Controllers/QueriesController.php:14
* @route '/_debugbar/queries/explain'
*/
export const explain = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: explain.url(options),
    method: 'post',
})

explain.definition = {
    methods: ["post"],
    url: '/_debugbar/queries/explain',
} satisfies RouteDefinition<["post"]>

/**
* @see \Barryvdh\Debugbar\Controllers\QueriesController::explain
* @see vendor/barryvdh/laravel-debugbar/src/Controllers/QueriesController.php:14
* @route '/_debugbar/queries/explain'
*/
explain.url = (options?: RouteQueryOptions) => {
    return explain.definition.url + queryParams(options)
}

/**
* @see \Barryvdh\Debugbar\Controllers\QueriesController::explain
* @see vendor/barryvdh/laravel-debugbar/src/Controllers/QueriesController.php:14
* @route '/_debugbar/queries/explain'
*/
explain.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: explain.url(options),
    method: 'post',
})

const queries = {
    explain: Object.assign(explain, explain),
}

export default queries