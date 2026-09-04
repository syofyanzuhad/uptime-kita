import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
import publicMethodC5d39d from './public'
/**
* @see \App\Http\Controllers\PublicMonitorController::publicMethod
* @see app/Http/Controllers/PublicMonitorController.php:24
* @route '/public-monitors'
*/
export const publicMethod = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: publicMethod.url(options),
    method: 'get',
})

publicMethod.definition = {
    methods: ["get","head"],
    url: '/public-monitors',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PublicMonitorController::publicMethod
* @see app/Http/Controllers/PublicMonitorController.php:24
* @route '/public-monitors'
*/
publicMethod.url = (options?: RouteQueryOptions) => {
    return publicMethod.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PublicMonitorController::publicMethod
* @see app/Http/Controllers/PublicMonitorController.php:24
* @route '/public-monitors'
*/
publicMethod.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: publicMethod.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PublicMonitorController::publicMethod
* @see app/Http/Controllers/PublicMonitorController.php:24
* @route '/public-monitors'
*/
publicMethod.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: publicMethod.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StatisticMonitorController::__invoke
* @see app/Http/Controllers/StatisticMonitorController.php:16
* @route '/statistic-monitor'
*/
export const statistic = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: statistic.url(options),
    method: 'get',
})

statistic.definition = {
    methods: ["get","head"],
    url: '/statistic-monitor',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StatisticMonitorController::__invoke
* @see app/Http/Controllers/StatisticMonitorController.php:16
* @route '/statistic-monitor'
*/
statistic.url = (options?: RouteQueryOptions) => {
    return statistic.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StatisticMonitorController::__invoke
* @see app/Http/Controllers/StatisticMonitorController.php:16
* @route '/statistic-monitor'
*/
statistic.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: statistic.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StatisticMonitorController::__invoke
* @see app/Http/Controllers/StatisticMonitorController.php:16
* @route '/statistic-monitor'
*/
statistic.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: statistic.url(options),
    method: 'head',
})

const monitor = {
    public: Object.assign(publicMethod, publicMethodC5d39d),
    statistic: Object.assign(statistic, statistic),
}

export default monitor