import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Settings\TelemetryController::index
* @see app/Http/Controllers/Settings/TelemetryController.php:24
* @route '/settings/telemetry'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/settings/telemetry',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Settings\TelemetryController::index
* @see app/Http/Controllers/Settings/TelemetryController.php:24
* @route '/settings/telemetry'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Settings\TelemetryController::index
* @see app/Http/Controllers/Settings/TelemetryController.php:24
* @route '/settings/telemetry'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Settings\TelemetryController::index
* @see app/Http/Controllers/Settings/TelemetryController.php:24
* @route '/settings/telemetry'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Settings\TelemetryController::preview
* @see app/Http/Controllers/Settings/TelemetryController.php:39
* @route '/settings/telemetry/preview'
*/
export const preview = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: preview.url(options),
    method: 'get',
})

preview.definition = {
    methods: ["get","head"],
    url: '/settings/telemetry/preview',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Settings\TelemetryController::preview
* @see app/Http/Controllers/Settings/TelemetryController.php:39
* @route '/settings/telemetry/preview'
*/
preview.url = (options?: RouteQueryOptions) => {
    return preview.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Settings\TelemetryController::preview
* @see app/Http/Controllers/Settings/TelemetryController.php:39
* @route '/settings/telemetry/preview'
*/
preview.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: preview.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Settings\TelemetryController::preview
* @see app/Http/Controllers/Settings/TelemetryController.php:39
* @route '/settings/telemetry/preview'
*/
preview.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: preview.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Settings\TelemetryController::testPing
* @see app/Http/Controllers/Settings/TelemetryController.php:51
* @route '/settings/telemetry/test-ping'
*/
export const testPing = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: testPing.url(options),
    method: 'post',
})

testPing.definition = {
    methods: ["post"],
    url: '/settings/telemetry/test-ping',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Settings\TelemetryController::testPing
* @see app/Http/Controllers/Settings/TelemetryController.php:51
* @route '/settings/telemetry/test-ping'
*/
testPing.url = (options?: RouteQueryOptions) => {
    return testPing.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Settings\TelemetryController::testPing
* @see app/Http/Controllers/Settings/TelemetryController.php:51
* @route '/settings/telemetry/test-ping'
*/
testPing.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: testPing.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Settings\TelemetryController::regenerateInstanceId
* @see app/Http/Controllers/Settings/TelemetryController.php:75
* @route '/settings/telemetry/regenerate-id'
*/
export const regenerateInstanceId = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: regenerateInstanceId.url(options),
    method: 'post',
})

regenerateInstanceId.definition = {
    methods: ["post"],
    url: '/settings/telemetry/regenerate-id',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Settings\TelemetryController::regenerateInstanceId
* @see app/Http/Controllers/Settings/TelemetryController.php:75
* @route '/settings/telemetry/regenerate-id'
*/
regenerateInstanceId.url = (options?: RouteQueryOptions) => {
    return regenerateInstanceId.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Settings\TelemetryController::regenerateInstanceId
* @see app/Http/Controllers/Settings/TelemetryController.php:75
* @route '/settings/telemetry/regenerate-id'
*/
regenerateInstanceId.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: regenerateInstanceId.url(options),
    method: 'post',
})

const TelemetryController = { index, preview, testPing, regenerateInstanceId }

export default TelemetryController