import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\MonitorImportController::csv
* @see app/Http/Controllers/MonitorImportController.php:74
* @route '/monitor/import/sample/csv'
*/
export const csv = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: csv.url(options),
    method: 'get',
})

csv.definition = {
    methods: ["get","head"],
    url: '/monitor/import/sample/csv',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MonitorImportController::csv
* @see app/Http/Controllers/MonitorImportController.php:74
* @route '/monitor/import/sample/csv'
*/
csv.url = (options?: RouteQueryOptions) => {
    return csv.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MonitorImportController::csv
* @see app/Http/Controllers/MonitorImportController.php:74
* @route '/monitor/import/sample/csv'
*/
csv.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: csv.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MonitorImportController::csv
* @see app/Http/Controllers/MonitorImportController.php:74
* @route '/monitor/import/sample/csv'
*/
csv.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: csv.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MonitorImportController::json
* @see app/Http/Controllers/MonitorImportController.php:86
* @route '/monitor/import/sample/json'
*/
export const json = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: json.url(options),
    method: 'get',
})

json.definition = {
    methods: ["get","head"],
    url: '/monitor/import/sample/json',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MonitorImportController::json
* @see app/Http/Controllers/MonitorImportController.php:86
* @route '/monitor/import/sample/json'
*/
json.url = (options?: RouteQueryOptions) => {
    return json.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MonitorImportController::json
* @see app/Http/Controllers/MonitorImportController.php:86
* @route '/monitor/import/sample/json'
*/
json.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: json.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MonitorImportController::json
* @see app/Http/Controllers/MonitorImportController.php:86
* @route '/monitor/import/sample/json'
*/
json.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: json.url(options),
    method: 'head',
})

const sample = {
    csv: Object.assign(csv, csv),
    json: Object.assign(json, json),
}

export default sample