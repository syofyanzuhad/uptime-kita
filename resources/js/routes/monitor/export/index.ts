import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\MonitorExportController::csv
* @see app/Http/Controllers/MonitorExportController.php:17
* @route '/monitor/export/csv'
*/
export const csv = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: csv.url(options),
    method: 'get',
})

csv.definition = {
    methods: ["get","head"],
    url: '/monitor/export/csv',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MonitorExportController::csv
* @see app/Http/Controllers/MonitorExportController.php:17
* @route '/monitor/export/csv'
*/
csv.url = (options?: RouteQueryOptions) => {
    return csv.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MonitorExportController::csv
* @see app/Http/Controllers/MonitorExportController.php:17
* @route '/monitor/export/csv'
*/
csv.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: csv.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MonitorExportController::csv
* @see app/Http/Controllers/MonitorExportController.php:17
* @route '/monitor/export/csv'
*/
csv.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: csv.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MonitorExportController::json
* @see app/Http/Controllers/MonitorExportController.php:29
* @route '/monitor/export/json'
*/
export const json = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: json.url(options),
    method: 'get',
})

json.definition = {
    methods: ["get","head"],
    url: '/monitor/export/json',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MonitorExportController::json
* @see app/Http/Controllers/MonitorExportController.php:29
* @route '/monitor/export/json'
*/
json.url = (options?: RouteQueryOptions) => {
    return json.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MonitorExportController::json
* @see app/Http/Controllers/MonitorExportController.php:29
* @route '/monitor/export/json'
*/
json.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: json.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MonitorExportController::json
* @see app/Http/Controllers/MonitorExportController.php:29
* @route '/monitor/export/json'
*/
json.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: json.url(options),
    method: 'head',
})

const exportMethod = {
    csv: Object.assign(csv, csv),
    json: Object.assign(json, json),
}

export default exportMethod