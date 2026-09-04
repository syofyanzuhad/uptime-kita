import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../wayfinder'
/**
* @see \Laravel\Horizon\Http\Controllers\CompletedJobsController::index
* @see vendor/laravel/horizon/src/Http/Controllers/CompletedJobsController.php:36
* @route '/horizon/api/jobs/completed'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/horizon/api/jobs/completed',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Laravel\Horizon\Http\Controllers\CompletedJobsController::index
* @see vendor/laravel/horizon/src/Http/Controllers/CompletedJobsController.php:36
* @route '/horizon/api/jobs/completed'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \Laravel\Horizon\Http\Controllers\CompletedJobsController::index
* @see vendor/laravel/horizon/src/Http/Controllers/CompletedJobsController.php:36
* @route '/horizon/api/jobs/completed'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \Laravel\Horizon\Http\Controllers\CompletedJobsController::index
* @see vendor/laravel/horizon/src/Http/Controllers/CompletedJobsController.php:36
* @route '/horizon/api/jobs/completed'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

const completedJobs = {
    index: Object.assign(index, index),
}

export default completedJobs