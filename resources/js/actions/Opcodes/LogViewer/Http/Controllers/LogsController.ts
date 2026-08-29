import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../wayfinder'
/**
* @see \Opcodes\LogViewer\Http\Controllers\LogsController::index
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/LogsController.php:19
* @route '/log-viewer/api/logs'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/log-viewer/api/logs',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Opcodes\LogViewer\Http\Controllers\LogsController::index
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/LogsController.php:19
* @route '/log-viewer/api/logs'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \Opcodes\LogViewer\Http\Controllers\LogsController::index
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/LogsController.php:19
* @route '/log-viewer/api/logs'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \Opcodes\LogViewer\Http\Controllers\LogsController::index
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/LogsController.php:19
* @route '/log-viewer/api/logs'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

const LogsController = { index }

export default LogsController