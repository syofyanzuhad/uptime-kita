import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \TraceReplay\Http\Controllers\Api\McpController::rpc
* @see vendor/iazaran/trace-replay/src/Http/Controllers/Api/McpController.php:117
* @route '/api/trace-replay/mcp'
*/
export const rpc = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: rpc.url(options),
    method: 'post',
})

rpc.definition = {
    methods: ["post"],
    url: '/api/trace-replay/mcp',
} satisfies RouteDefinition<["post"]>

/**
* @see \TraceReplay\Http\Controllers\Api\McpController::rpc
* @see vendor/iazaran/trace-replay/src/Http/Controllers/Api/McpController.php:117
* @route '/api/trace-replay/mcp'
*/
rpc.url = (options?: RouteQueryOptions) => {
    return rpc.definition.url + queryParams(options)
}

/**
* @see \TraceReplay\Http\Controllers\Api\McpController::rpc
* @see vendor/iazaran/trace-replay/src/Http/Controllers/Api/McpController.php:117
* @route '/api/trace-replay/mcp'
*/
rpc.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: rpc.url(options),
    method: 'post',
})

const mcp = {
    rpc: Object.assign(rpc, rpc),
}

export default mcp