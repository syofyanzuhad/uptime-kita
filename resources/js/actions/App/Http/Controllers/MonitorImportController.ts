import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\MonitorImportController::index
* @see app/Http/Controllers/MonitorImportController.php:23
* @route '/monitors/import'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/monitors/import',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MonitorImportController::index
* @see app/Http/Controllers/MonitorImportController.php:23
* @route '/monitors/import'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MonitorImportController::index
* @see app/Http/Controllers/MonitorImportController.php:23
* @route '/monitors/import'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MonitorImportController::index
* @see app/Http/Controllers/MonitorImportController.php:23
* @route '/monitors/import'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MonitorImportController::preview
* @see app/Http/Controllers/MonitorImportController.php:31
* @route '/monitors/import/preview'
*/
export const preview = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: preview.url(options),
    method: 'post',
})

preview.definition = {
    methods: ["post"],
    url: '/monitors/import/preview',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MonitorImportController::preview
* @see app/Http/Controllers/MonitorImportController.php:31
* @route '/monitors/import/preview'
*/
preview.url = (options?: RouteQueryOptions) => {
    return preview.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MonitorImportController::preview
* @see app/Http/Controllers/MonitorImportController.php:31
* @route '/monitors/import/preview'
*/
preview.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: preview.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MonitorImportController::process
* @see app/Http/Controllers/MonitorImportController.php:44
* @route '/monitors/import/process'
*/
export const process = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: process.url(options),
    method: 'post',
})

process.definition = {
    methods: ["post"],
    url: '/monitors/import/process',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MonitorImportController::process
* @see app/Http/Controllers/MonitorImportController.php:44
* @route '/monitors/import/process'
*/
process.url = (options?: RouteQueryOptions) => {
    return process.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MonitorImportController::process
* @see app/Http/Controllers/MonitorImportController.php:44
* @route '/monitors/import/process'
*/
process.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: process.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MonitorImportController::sampleCsv
* @see app/Http/Controllers/MonitorImportController.php:78
* @route '/monitors/import/sample/csv'
*/
export const sampleCsv = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: sampleCsv.url(options),
    method: 'get',
})

sampleCsv.definition = {
    methods: ["get","head"],
    url: '/monitors/import/sample/csv',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MonitorImportController::sampleCsv
* @see app/Http/Controllers/MonitorImportController.php:78
* @route '/monitors/import/sample/csv'
*/
sampleCsv.url = (options?: RouteQueryOptions) => {
    return sampleCsv.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MonitorImportController::sampleCsv
* @see app/Http/Controllers/MonitorImportController.php:78
* @route '/monitors/import/sample/csv'
*/
sampleCsv.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: sampleCsv.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MonitorImportController::sampleCsv
* @see app/Http/Controllers/MonitorImportController.php:78
* @route '/monitors/import/sample/csv'
*/
sampleCsv.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: sampleCsv.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MonitorImportController::sampleJson
* @see app/Http/Controllers/MonitorImportController.php:90
* @route '/monitors/import/sample/json'
*/
export const sampleJson = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: sampleJson.url(options),
    method: 'get',
})

sampleJson.definition = {
    methods: ["get","head"],
    url: '/monitors/import/sample/json',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MonitorImportController::sampleJson
* @see app/Http/Controllers/MonitorImportController.php:90
* @route '/monitors/import/sample/json'
*/
sampleJson.url = (options?: RouteQueryOptions) => {
    return sampleJson.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MonitorImportController::sampleJson
* @see app/Http/Controllers/MonitorImportController.php:90
* @route '/monitors/import/sample/json'
*/
sampleJson.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: sampleJson.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MonitorImportController::sampleJson
* @see app/Http/Controllers/MonitorImportController.php:90
* @route '/monitors/import/sample/json'
*/
sampleJson.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: sampleJson.url(options),
    method: 'head',
})

const MonitorImportController = { index, preview, process, sampleCsv, sampleJson }

export default MonitorImportController