import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \LaraBug\Http\Controllers\LaraBugReportController::report
* @see vendor/larabug/larabug/src/Http/Controllers/LaraBugReportController.php:13
* @route '/larabug-api/javascript-report'
*/
export const report = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: report.url(options),
    method: 'post',
})

report.definition = {
    methods: ["post"],
    url: '/larabug-api/javascript-report',
} satisfies RouteDefinition<["post"]>

/**
* @see \LaraBug\Http\Controllers\LaraBugReportController::report
* @see vendor/larabug/larabug/src/Http/Controllers/LaraBugReportController.php:13
* @route '/larabug-api/javascript-report'
*/
report.url = (options?: RouteQueryOptions) => {
    return report.definition.url + queryParams(options)
}

/**
* @see \LaraBug\Http\Controllers\LaraBugReportController::report
* @see vendor/larabug/larabug/src/Http/Controllers/LaraBugReportController.php:13
* @route '/larabug-api/javascript-report'
*/
report.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: report.url(options),
    method: 'post',
})

const LaraBugReportController = { report }

export default LaraBugReportController