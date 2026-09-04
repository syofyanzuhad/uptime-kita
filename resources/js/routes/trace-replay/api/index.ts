import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
import mcp from './mcp'
/**
* @see \TraceReplay\Http\Controllers\Api\McpController::list
* @see vendor/iazaran/trace-replay/src/Http/Controllers/Api/McpController.php:38
* @route '/api/trace-replay/traces'
*/
export const list = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: list.url(options),
    method: 'get',
})

list.definition = {
    methods: ["get","head"],
    url: '/api/trace-replay/traces',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \TraceReplay\Http\Controllers\Api\McpController::list
* @see vendor/iazaran/trace-replay/src/Http/Controllers/Api/McpController.php:38
* @route '/api/trace-replay/traces'
*/
list.url = (options?: RouteQueryOptions) => {
    return list.definition.url + queryParams(options)
}

/**
* @see \TraceReplay\Http\Controllers\Api\McpController::list
* @see vendor/iazaran/trace-replay/src/Http/Controllers/Api/McpController.php:38
* @route '/api/trace-replay/traces'
*/
list.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: list.url(options),
    method: 'get',
})

/**
* @see \TraceReplay\Http\Controllers\Api\McpController::list
* @see vendor/iazaran/trace-replay/src/Http/Controllers/Api/McpController.php:38
* @route '/api/trace-replay/traces'
*/
list.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: list.url(options),
    method: 'head',
})

/**
* @see \TraceReplay\Http\Controllers\Api\McpController::context
* @see vendor/iazaran/trace-replay/src/Http/Controllers/Api/McpController.php:63
* @route '/api/trace-replay/traces/{trace}/context'
*/
export const context = (args: { trace: string | number } | [trace: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: context.url(args, options),
    method: 'get',
})

context.definition = {
    methods: ["get","head"],
    url: '/api/trace-replay/traces/{trace}/context',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \TraceReplay\Http\Controllers\Api\McpController::context
* @see vendor/iazaran/trace-replay/src/Http/Controllers/Api/McpController.php:63
* @route '/api/trace-replay/traces/{trace}/context'
*/
context.url = (args: { trace: string | number } | [trace: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { trace: args }
    }

    if (Array.isArray(args)) {
        args = {
            trace: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        trace: args.trace,
    }

    return context.definition.url
            .replace('{trace}', parsedArgs.trace.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \TraceReplay\Http\Controllers\Api\McpController::context
* @see vendor/iazaran/trace-replay/src/Http/Controllers/Api/McpController.php:63
* @route '/api/trace-replay/traces/{trace}/context'
*/
context.get = (args: { trace: string | number } | [trace: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: context.url(args, options),
    method: 'get',
})

/**
* @see \TraceReplay\Http\Controllers\Api\McpController::context
* @see vendor/iazaran/trace-replay/src/Http/Controllers/Api/McpController.php:63
* @route '/api/trace-replay/traces/{trace}/context'
*/
context.head = (args: { trace: string | number } | [trace: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: context.url(args, options),
    method: 'head',
})

/**
* @see \TraceReplay\Http\Controllers\Api\McpController::replay
* @see vendor/iazaran/trace-replay/src/Http/Controllers/Api/McpController.php:82
* @route '/api/trace-replay/traces/{trace}/replay'
*/
export const replay = (args: { trace: string | number } | [trace: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: replay.url(args, options),
    method: 'post',
})

replay.definition = {
    methods: ["post"],
    url: '/api/trace-replay/traces/{trace}/replay',
} satisfies RouteDefinition<["post"]>

/**
* @see \TraceReplay\Http\Controllers\Api\McpController::replay
* @see vendor/iazaran/trace-replay/src/Http/Controllers/Api/McpController.php:82
* @route '/api/trace-replay/traces/{trace}/replay'
*/
replay.url = (args: { trace: string | number } | [trace: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { trace: args }
    }

    if (Array.isArray(args)) {
        args = {
            trace: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        trace: args.trace,
    }

    return replay.definition.url
            .replace('{trace}', parsedArgs.trace.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \TraceReplay\Http\Controllers\Api\McpController::replay
* @see vendor/iazaran/trace-replay/src/Http/Controllers/Api/McpController.php:82
* @route '/api/trace-replay/traces/{trace}/replay'
*/
replay.post = (args: { trace: string | number } | [trace: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: replay.url(args, options),
    method: 'post',
})

/**
* @see \TraceReplay\Http\Controllers\Api\McpController::fixPrompt
* @see vendor/iazaran/trace-replay/src/Http/Controllers/Api/McpController.php:102
* @route '/api/trace-replay/traces/{trace}/fix-prompt'
*/
export const fixPrompt = (args: { trace: string | number } | [trace: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: fixPrompt.url(args, options),
    method: 'get',
})

fixPrompt.definition = {
    methods: ["get","head"],
    url: '/api/trace-replay/traces/{trace}/fix-prompt',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \TraceReplay\Http\Controllers\Api\McpController::fixPrompt
* @see vendor/iazaran/trace-replay/src/Http/Controllers/Api/McpController.php:102
* @route '/api/trace-replay/traces/{trace}/fix-prompt'
*/
fixPrompt.url = (args: { trace: string | number } | [trace: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { trace: args }
    }

    if (Array.isArray(args)) {
        args = {
            trace: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        trace: args.trace,
    }

    return fixPrompt.definition.url
            .replace('{trace}', parsedArgs.trace.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \TraceReplay\Http\Controllers\Api\McpController::fixPrompt
* @see vendor/iazaran/trace-replay/src/Http/Controllers/Api/McpController.php:102
* @route '/api/trace-replay/traces/{trace}/fix-prompt'
*/
fixPrompt.get = (args: { trace: string | number } | [trace: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: fixPrompt.url(args, options),
    method: 'get',
})

/**
* @see \TraceReplay\Http\Controllers\Api\McpController::fixPrompt
* @see vendor/iazaran/trace-replay/src/Http/Controllers/Api/McpController.php:102
* @route '/api/trace-replay/traces/{trace}/fix-prompt'
*/
fixPrompt.head = (args: { trace: string | number } | [trace: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: fixPrompt.url(args, options),
    method: 'head',
})

const api = {
    mcp: Object.assign(mcp, mcp),
    list: Object.assign(list, list),
    context: Object.assign(context, context),
    replay: Object.assign(replay, replay),
    fixPrompt: Object.assign(fixPrompt, fixPrompt),
}

export default api