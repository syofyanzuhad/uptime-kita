import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\UptimeMonitorController::index
* @see app/Http/Controllers/UptimeMonitorController.php:22
* @route '/monitor'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/monitor',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\UptimeMonitorController::index
* @see app/Http/Controllers/UptimeMonitorController.php:22
* @route '/monitor'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\UptimeMonitorController::index
* @see app/Http/Controllers/UptimeMonitorController.php:22
* @route '/monitor'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\UptimeMonitorController::index
* @see app/Http/Controllers/UptimeMonitorController.php:22
* @route '/monitor'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\UptimeMonitorController::create
* @see app/Http/Controllers/UptimeMonitorController.php:159
* @route '/monitor/create'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/monitor/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\UptimeMonitorController::create
* @see app/Http/Controllers/UptimeMonitorController.php:159
* @route '/monitor/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\UptimeMonitorController::create
* @see app/Http/Controllers/UptimeMonitorController.php:159
* @route '/monitor/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\UptimeMonitorController::create
* @see app/Http/Controllers/UptimeMonitorController.php:159
* @route '/monitor/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\UptimeMonitorController::store
* @see app/Http/Controllers/UptimeMonitorController.php:167
* @route '/monitor'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/monitor',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\UptimeMonitorController::store
* @see app/Http/Controllers/UptimeMonitorController.php:167
* @route '/monitor'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\UptimeMonitorController::store
* @see app/Http/Controllers/UptimeMonitorController.php:167
* @route '/monitor'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\UptimeMonitorController::show
* @see app/Http/Controllers/UptimeMonitorController.php:86
* @route '/monitor/{monitor}'
*/
export const show = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/monitor/{monitor}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\UptimeMonitorController::show
* @see app/Http/Controllers/UptimeMonitorController.php:86
* @route '/monitor/{monitor}'
*/
show.url = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { monitor: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { monitor: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            monitor: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        monitor: typeof args.monitor === 'object'
        ? args.monitor.id
        : args.monitor,
    }

    return show.definition.url
            .replace('{monitor}', parsedArgs.monitor.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\UptimeMonitorController::show
* @see app/Http/Controllers/UptimeMonitorController.php:86
* @route '/monitor/{monitor}'
*/
show.get = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\UptimeMonitorController::show
* @see app/Http/Controllers/UptimeMonitorController.php:86
* @route '/monitor/{monitor}'
*/
show.head = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\UptimeMonitorController::edit
* @see app/Http/Controllers/UptimeMonitorController.php:233
* @route '/monitor/{monitor}/edit'
*/
export const edit = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/monitor/{monitor}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\UptimeMonitorController::edit
* @see app/Http/Controllers/UptimeMonitorController.php:233
* @route '/monitor/{monitor}/edit'
*/
edit.url = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { monitor: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { monitor: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            monitor: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        monitor: typeof args.monitor === 'object'
        ? args.monitor.id
        : args.monitor,
    }

    return edit.definition.url
            .replace('{monitor}', parsedArgs.monitor.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\UptimeMonitorController::edit
* @see app/Http/Controllers/UptimeMonitorController.php:233
* @route '/monitor/{monitor}/edit'
*/
edit.get = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\UptimeMonitorController::edit
* @see app/Http/Controllers/UptimeMonitorController.php:233
* @route '/monitor/{monitor}/edit'
*/
edit.head = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\UptimeMonitorController::update
* @see app/Http/Controllers/UptimeMonitorController.php:243
* @route '/monitor/{monitor}'
*/
export const update = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put","patch"],
    url: '/monitor/{monitor}',
} satisfies RouteDefinition<["put","patch"]>

/**
* @see \App\Http\Controllers\UptimeMonitorController::update
* @see app/Http/Controllers/UptimeMonitorController.php:243
* @route '/monitor/{monitor}'
*/
update.url = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { monitor: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { monitor: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            monitor: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        monitor: typeof args.monitor === 'object'
        ? args.monitor.id
        : args.monitor,
    }

    return update.definition.url
            .replace('{monitor}', parsedArgs.monitor.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\UptimeMonitorController::update
* @see app/Http/Controllers/UptimeMonitorController.php:243
* @route '/monitor/{monitor}'
*/
update.put = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\UptimeMonitorController::update
* @see app/Http/Controllers/UptimeMonitorController.php:243
* @route '/monitor/{monitor}'
*/
update.patch = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\UptimeMonitorController::destroy
* @see app/Http/Controllers/UptimeMonitorController.php:311
* @route '/monitor/{monitor}'
*/
export const destroy = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/monitor/{monitor}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\UptimeMonitorController::destroy
* @see app/Http/Controllers/UptimeMonitorController.php:311
* @route '/monitor/{monitor}'
*/
destroy.url = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { monitor: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { monitor: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            monitor: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        monitor: typeof args.monitor === 'object'
        ? args.monitor.id
        : args.monitor,
    }

    return destroy.definition.url
            .replace('{monitor}', parsedArgs.monitor.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\UptimeMonitorController::destroy
* @see app/Http/Controllers/UptimeMonitorController.php:311
* @route '/monitor/{monitor}'
*/
destroy.delete = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\UptimeMonitorController::getHistory
* @see app/Http/Controllers/UptimeMonitorController.php:123
* @route '/monitor/{monitor}/history'
*/
export const getHistory = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getHistory.url(args, options),
    method: 'get',
})

getHistory.definition = {
    methods: ["get","head"],
    url: '/monitor/{monitor}/history',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\UptimeMonitorController::getHistory
* @see app/Http/Controllers/UptimeMonitorController.php:123
* @route '/monitor/{monitor}/history'
*/
getHistory.url = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { monitor: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { monitor: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            monitor: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        monitor: typeof args.monitor === 'object'
        ? args.monitor.id
        : args.monitor,
    }

    return getHistory.definition.url
            .replace('{monitor}', parsedArgs.monitor.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\UptimeMonitorController::getHistory
* @see app/Http/Controllers/UptimeMonitorController.php:123
* @route '/monitor/{monitor}/history'
*/
getHistory.get = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getHistory.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\UptimeMonitorController::getHistory
* @see app/Http/Controllers/UptimeMonitorController.php:123
* @route '/monitor/{monitor}/history'
*/
getHistory.head = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: getHistory.url(args, options),
    method: 'head',
})

const UptimeMonitorController = { index, create, store, show, edit, update, destroy, getHistory }

export default UptimeMonitorController