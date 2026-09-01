import { applyUrlDefaults, queryParams, type RouteDefinition, type RouteQueryOptions } from './../../../../../wayfinder';
/**
 * @see \TraceReplay\Http\Controllers\Api\McpController::handleRpc
 * @see vendor/iazaran/trace-replay/src/Http/Controllers/Api/McpController.php:117
 * @route '/api/trace-replay/mcp'
 */
export const handleRpc = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: handleRpc.url(options),
    method: 'post',
});

handleRpc.definition = {
    methods: ['post'],
    url: '/api/trace-replay/mcp',
} satisfies RouteDefinition<['post']>;

/**
 * @see \TraceReplay\Http\Controllers\Api\McpController::handleRpc
 * @see vendor/iazaran/trace-replay/src/Http/Controllers/Api/McpController.php:117
 * @route '/api/trace-replay/mcp'
 */
handleRpc.url = (options?: RouteQueryOptions) => {
    return handleRpc.definition.url + queryParams(options);
};

/**
 * @see \TraceReplay\Http\Controllers\Api\McpController::handleRpc
 * @see vendor/iazaran/trace-replay/src/Http/Controllers/Api/McpController.php:117
 * @route '/api/trace-replay/mcp'
 */
handleRpc.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: handleRpc.url(options),
    method: 'post',
});

/**
 * @see \TraceReplay\Http\Controllers\Api\McpController::listTraces
 * @see vendor/iazaran/trace-replay/src/Http/Controllers/Api/McpController.php:38
 * @route '/api/trace-replay/traces'
 */
export const listTraces = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: listTraces.url(options),
    method: 'get',
});

listTraces.definition = {
    methods: ['get', 'head'],
    url: '/api/trace-replay/traces',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \TraceReplay\Http\Controllers\Api\McpController::listTraces
 * @see vendor/iazaran/trace-replay/src/Http/Controllers/Api/McpController.php:38
 * @route '/api/trace-replay/traces'
 */
listTraces.url = (options?: RouteQueryOptions) => {
    return listTraces.definition.url + queryParams(options);
};

/**
 * @see \TraceReplay\Http\Controllers\Api\McpController::listTraces
 * @see vendor/iazaran/trace-replay/src/Http/Controllers/Api/McpController.php:38
 * @route '/api/trace-replay/traces'
 */
listTraces.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: listTraces.url(options),
    method: 'get',
});

/**
 * @see \TraceReplay\Http\Controllers\Api\McpController::listTraces
 * @see vendor/iazaran/trace-replay/src/Http/Controllers/Api/McpController.php:38
 * @route '/api/trace-replay/traces'
 */
listTraces.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: listTraces.url(options),
    method: 'head',
});

/**
 * @see \TraceReplay\Http\Controllers\Api\McpController::getContext
 * @see vendor/iazaran/trace-replay/src/Http/Controllers/Api/McpController.php:63
 * @route '/api/trace-replay/traces/{trace}/context'
 */
export const getContext = (
    args: { trace: string | number } | [trace: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: getContext.url(args, options),
    method: 'get',
});

getContext.definition = {
    methods: ['get', 'head'],
    url: '/api/trace-replay/traces/{trace}/context',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \TraceReplay\Http\Controllers\Api\McpController::getContext
 * @see vendor/iazaran/trace-replay/src/Http/Controllers/Api/McpController.php:63
 * @route '/api/trace-replay/traces/{trace}/context'
 */
getContext.url = (args: { trace: string | number } | [trace: string | number] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { trace: args };
    }

    if (Array.isArray(args)) {
        args = {
            trace: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        trace: args.trace,
    };

    return getContext.definition.url.replace('{trace}', parsedArgs.trace.toString()).replace(/\/+$/, '') + queryParams(options);
};

/**
 * @see \TraceReplay\Http\Controllers\Api\McpController::getContext
 * @see vendor/iazaran/trace-replay/src/Http/Controllers/Api/McpController.php:63
 * @route '/api/trace-replay/traces/{trace}/context'
 */
getContext.get = (
    args: { trace: string | number } | [trace: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: getContext.url(args, options),
    method: 'get',
});

/**
 * @see \TraceReplay\Http\Controllers\Api\McpController::getContext
 * @see vendor/iazaran/trace-replay/src/Http/Controllers/Api/McpController.php:63
 * @route '/api/trace-replay/traces/{trace}/context'
 */
getContext.head = (
    args: { trace: string | number } | [trace: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: getContext.url(args, options),
    method: 'head',
});

/**
 * @see \TraceReplay\Http\Controllers\Api\McpController::triggerReplay
 * @see vendor/iazaran/trace-replay/src/Http/Controllers/Api/McpController.php:82
 * @route '/api/trace-replay/traces/{trace}/replay'
 */
export const triggerReplay = (
    args: { trace: string | number } | [trace: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: triggerReplay.url(args, options),
    method: 'post',
});

triggerReplay.definition = {
    methods: ['post'],
    url: '/api/trace-replay/traces/{trace}/replay',
} satisfies RouteDefinition<['post']>;

/**
 * @see \TraceReplay\Http\Controllers\Api\McpController::triggerReplay
 * @see vendor/iazaran/trace-replay/src/Http/Controllers/Api/McpController.php:82
 * @route '/api/trace-replay/traces/{trace}/replay'
 */
triggerReplay.url = (args: { trace: string | number } | [trace: string | number] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { trace: args };
    }

    if (Array.isArray(args)) {
        args = {
            trace: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        trace: args.trace,
    };

    return triggerReplay.definition.url.replace('{trace}', parsedArgs.trace.toString()).replace(/\/+$/, '') + queryParams(options);
};

/**
 * @see \TraceReplay\Http\Controllers\Api\McpController::triggerReplay
 * @see vendor/iazaran/trace-replay/src/Http/Controllers/Api/McpController.php:82
 * @route '/api/trace-replay/traces/{trace}/replay'
 */
triggerReplay.post = (
    args: { trace: string | number } | [trace: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: triggerReplay.url(args, options),
    method: 'post',
});

/**
 * @see \TraceReplay\Http\Controllers\Api\McpController::generateFixPrompt
 * @see vendor/iazaran/trace-replay/src/Http/Controllers/Api/McpController.php:102
 * @route '/api/trace-replay/traces/{trace}/fix-prompt'
 */
export const generateFixPrompt = (
    args: { trace: string | number } | [trace: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: generateFixPrompt.url(args, options),
    method: 'get',
});

generateFixPrompt.definition = {
    methods: ['get', 'head'],
    url: '/api/trace-replay/traces/{trace}/fix-prompt',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \TraceReplay\Http\Controllers\Api\McpController::generateFixPrompt
 * @see vendor/iazaran/trace-replay/src/Http/Controllers/Api/McpController.php:102
 * @route '/api/trace-replay/traces/{trace}/fix-prompt'
 */
generateFixPrompt.url = (args: { trace: string | number } | [trace: string | number] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { trace: args };
    }

    if (Array.isArray(args)) {
        args = {
            trace: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        trace: args.trace,
    };

    return generateFixPrompt.definition.url.replace('{trace}', parsedArgs.trace.toString()).replace(/\/+$/, '') + queryParams(options);
};

/**
 * @see \TraceReplay\Http\Controllers\Api\McpController::generateFixPrompt
 * @see vendor/iazaran/trace-replay/src/Http/Controllers/Api/McpController.php:102
 * @route '/api/trace-replay/traces/{trace}/fix-prompt'
 */
generateFixPrompt.get = (
    args: { trace: string | number } | [trace: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: generateFixPrompt.url(args, options),
    method: 'get',
});

/**
 * @see \TraceReplay\Http\Controllers\Api\McpController::generateFixPrompt
 * @see vendor/iazaran/trace-replay/src/Http/Controllers/Api/McpController.php:102
 * @route '/api/trace-replay/traces/{trace}/fix-prompt'
 */
generateFixPrompt.head = (
    args: { trace: string | number } | [trace: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: generateFixPrompt.url(args, options),
    method: 'head',
});

const McpController = { handleRpc, listTraces, getContext, triggerReplay, generateFixPrompt };

export default McpController;
