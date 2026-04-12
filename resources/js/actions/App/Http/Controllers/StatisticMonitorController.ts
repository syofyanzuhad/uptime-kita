import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StatisticMonitorController::__invoke
* @see app/Http/Controllers/StatisticMonitorController.php:14
* @route '/statistic-monitor'
*/
const StatisticMonitorController = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StatisticMonitorController.url(options),
    method: 'get',
})

StatisticMonitorController.definition = {
    methods: ["get","head"],
    url: '/statistic-monitor',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StatisticMonitorController::__invoke
* @see app/Http/Controllers/StatisticMonitorController.php:14
* @route '/statistic-monitor'
*/
StatisticMonitorController.url = (options?: RouteQueryOptions) => {
    return StatisticMonitorController.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StatisticMonitorController::__invoke
* @see app/Http/Controllers/StatisticMonitorController.php:14
* @route '/statistic-monitor'
*/
StatisticMonitorController.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StatisticMonitorController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StatisticMonitorController::__invoke
* @see app/Http/Controllers/StatisticMonitorController.php:14
* @route '/statistic-monitor'
*/
StatisticMonitorController.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: StatisticMonitorController.url(options),
    method: 'head',
})

export default StatisticMonitorController