import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../wayfinder'
import sample from './sample'
/**
* @see \App\Http\Controllers\MonitorImportController::index
* @see app/Http/Controllers/MonitorImportController.php:19
* @route '/monitor/import'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/monitor/import',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MonitorImportController::index
* @see app/Http/Controllers/MonitorImportController.php:19
* @route '/monitor/import'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MonitorImportController::index
* @see app/Http/Controllers/MonitorImportController.php:19
* @route '/monitor/import'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MonitorImportController::index
* @see app/Http/Controllers/MonitorImportController.php:19
* @route '/monitor/import'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MonitorImportController::preview
* @see app/Http/Controllers/MonitorImportController.php:27
* @route '/monitor/import/preview'
*/
export const preview = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: preview.url(options),
    method: 'post',
})

preview.definition = {
    methods: ["post"],
    url: '/monitor/import/preview',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MonitorImportController::preview
* @see app/Http/Controllers/MonitorImportController.php:27
* @route '/monitor/import/preview'
*/
preview.url = (options?: RouteQueryOptions) => {
    return preview.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MonitorImportController::preview
* @see app/Http/Controllers/MonitorImportController.php:27
* @route '/monitor/import/preview'
*/
preview.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: preview.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MonitorImportController::process
* @see app/Http/Controllers/MonitorImportController.php:40
* @route '/monitor/import/process'
*/
export const process = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: process.url(options),
    method: 'post',
})

process.definition = {
    methods: ["post"],
    url: '/monitor/import/process',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MonitorImportController::process
* @see app/Http/Controllers/MonitorImportController.php:40
* @route '/monitor/import/process'
*/
process.url = (options?: RouteQueryOptions) => {
    return process.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MonitorImportController::process
* @see app/Http/Controllers/MonitorImportController.php:40
* @route '/monitor/import/process'
*/
process.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: process.url(options),
    method: 'post',
})

const importMethod = {
    index: Object.assign(index, index),
    preview: Object.assign(preview, preview),
    process: Object.assign(process, process),
    sample: Object.assign(sample, sample),
}

export default importMethod