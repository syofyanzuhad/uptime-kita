import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
import ai from './ai'
import api from './api'
/**
* @see \TraceReplay\Http\Controllers\DashboardController::index
* @see vendor/iazaran/trace-replay/src/Http/Controllers/DashboardController.php:15
* @route '/trace-replay'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/trace-replay',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \TraceReplay\Http\Controllers\DashboardController::index
* @see vendor/iazaran/trace-replay/src/Http/Controllers/DashboardController.php:15
* @route '/trace-replay'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \TraceReplay\Http\Controllers\DashboardController::index
* @see vendor/iazaran/trace-replay/src/Http/Controllers/DashboardController.php:15
* @route '/trace-replay'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \TraceReplay\Http\Controllers\DashboardController::index
* @see vendor/iazaran/trace-replay/src/Http/Controllers/DashboardController.php:15
* @route '/trace-replay'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \TraceReplay\Http\Controllers\DashboardController::show
* @see vendor/iazaran/trace-replay/src/Http/Controllers/DashboardController.php:144
* @route '/trace-replay/traces/{id}'
*/
export const show = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/trace-replay/traces/{id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \TraceReplay\Http\Controllers\DashboardController::show
* @see vendor/iazaran/trace-replay/src/Http/Controllers/DashboardController.php:144
* @route '/trace-replay/traces/{id}'
*/
show.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    if (Array.isArray(args)) {
        args = {
            id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        id: args.id,
    }

    return show.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \TraceReplay\Http\Controllers\DashboardController::show
* @see vendor/iazaran/trace-replay/src/Http/Controllers/DashboardController.php:144
* @route '/trace-replay/traces/{id}'
*/
show.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \TraceReplay\Http\Controllers\DashboardController::show
* @see vendor/iazaran/trace-replay/src/Http/Controllers/DashboardController.php:144
* @route '/trace-replay/traces/{id}'
*/
show.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \TraceReplay\Http\Controllers\DashboardController::replay
* @see vendor/iazaran/trace-replay/src/Http/Controllers/DashboardController.php:151
* @route '/trace-replay/traces/{id}/replay'
*/
export const replay = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: replay.url(args, options),
    method: 'post',
})

replay.definition = {
    methods: ["post"],
    url: '/trace-replay/traces/{id}/replay',
} satisfies RouteDefinition<["post"]>

/**
* @see \TraceReplay\Http\Controllers\DashboardController::replay
* @see vendor/iazaran/trace-replay/src/Http/Controllers/DashboardController.php:151
* @route '/trace-replay/traces/{id}/replay'
*/
replay.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    if (Array.isArray(args)) {
        args = {
            id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        id: args.id,
    }

    return replay.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \TraceReplay\Http\Controllers\DashboardController::replay
* @see vendor/iazaran/trace-replay/src/Http/Controllers/DashboardController.php:151
* @route '/trace-replay/traces/{id}/replay'
*/
replay.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: replay.url(args, options),
    method: 'post',
})

/**
* @see \TraceReplay\Http\Controllers\DashboardController::stats
* @see vendor/iazaran/trace-replay/src/Http/Controllers/DashboardController.php:181
* @route '/trace-replay/stats'
*/
export const stats = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: stats.url(options),
    method: 'get',
})

stats.definition = {
    methods: ["get","head"],
    url: '/trace-replay/stats',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \TraceReplay\Http\Controllers\DashboardController::stats
* @see vendor/iazaran/trace-replay/src/Http/Controllers/DashboardController.php:181
* @route '/trace-replay/stats'
*/
stats.url = (options?: RouteQueryOptions) => {
    return stats.definition.url + queryParams(options)
}

/**
* @see \TraceReplay\Http\Controllers\DashboardController::stats
* @see vendor/iazaran/trace-replay/src/Http/Controllers/DashboardController.php:181
* @route '/trace-replay/stats'
*/
stats.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: stats.url(options),
    method: 'get',
})

/**
* @see \TraceReplay\Http\Controllers\DashboardController::stats
* @see vendor/iazaran/trace-replay/src/Http/Controllers/DashboardController.php:181
* @route '/trace-replay/stats'
*/
stats.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: stats.url(options),
    method: 'head',
})

/**
* @see \TraceReplay\Http\Controllers\DashboardController::exportMethod
* @see vendor/iazaran/trace-replay/src/Http/Controllers/DashboardController.php:208
* @route '/trace-replay/traces/{id}/export'
*/
export const exportMethod = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: exportMethod.url(args, options),
    method: 'get',
})

exportMethod.definition = {
    methods: ["get","head"],
    url: '/trace-replay/traces/{id}/export',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \TraceReplay\Http\Controllers\DashboardController::exportMethod
* @see vendor/iazaran/trace-replay/src/Http/Controllers/DashboardController.php:208
* @route '/trace-replay/traces/{id}/export'
*/
exportMethod.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    if (Array.isArray(args)) {
        args = {
            id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        id: args.id,
    }

    return exportMethod.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \TraceReplay\Http\Controllers\DashboardController::exportMethod
* @see vendor/iazaran/trace-replay/src/Http/Controllers/DashboardController.php:208
* @route '/trace-replay/traces/{id}/export'
*/
exportMethod.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: exportMethod.url(args, options),
    method: 'get',
})

/**
* @see \TraceReplay\Http\Controllers\DashboardController::exportMethod
* @see vendor/iazaran/trace-replay/src/Http/Controllers/DashboardController.php:208
* @route '/trace-replay/traces/{id}/export'
*/
exportMethod.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: exportMethod.url(args, options),
    method: 'head',
})

const traceReplay = {
    index: Object.assign(index, index),
    show: Object.assign(show, show),
    replay: Object.assign(replay, replay),
    ai: Object.assign(ai, ai),
    stats: Object.assign(stats, stats),
    export: Object.assign(exportMethod, exportMethod),
    api: Object.assign(api, api),
}

export default traceReplay