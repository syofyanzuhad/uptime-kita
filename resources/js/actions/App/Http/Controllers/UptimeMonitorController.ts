import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\UptimeMonitorController::index
* @see app/Http/Controllers/UptimeMonitorController.php:24
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
* @see app/Http/Controllers/UptimeMonitorController.php:24
* @route '/monitor'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\UptimeMonitorController::index
* @see app/Http/Controllers/UptimeMonitorController.php:24
* @route '/monitor'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\UptimeMonitorController::index
* @see app/Http/Controllers/UptimeMonitorController.php:24
* @route '/monitor'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\UptimeMonitorController::create
* @see app/Http/Controllers/UptimeMonitorController.php:131
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
* @see app/Http/Controllers/UptimeMonitorController.php:131
* @route '/monitor/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\UptimeMonitorController::create
* @see app/Http/Controllers/UptimeMonitorController.php:131
* @route '/monitor/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\UptimeMonitorController::create
* @see app/Http/Controllers/UptimeMonitorController.php:131
* @route '/monitor/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\UptimeMonitorController::store
* @see app/Http/Controllers/UptimeMonitorController.php:141
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
* @see app/Http/Controllers/UptimeMonitorController.php:141
* @route '/monitor'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\UptimeMonitorController::store
* @see app/Http/Controllers/UptimeMonitorController.php:141
* @route '/monitor'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\UptimeMonitorController::show
* @see app/Http/Controllers/UptimeMonitorController.php:90
* @route '/monitor/{monitor}'
*/
export const show = (args: { monitor: string | number | { id: string | number } } | [monitor: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/monitor/{monitor}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\UptimeMonitorController::show
* @see app/Http/Controllers/UptimeMonitorController.php:90
* @route '/monitor/{monitor}'
*/
show.url = (args: { monitor: string | number | { id: string | number } } | [monitor: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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
* @see app/Http/Controllers/UptimeMonitorController.php:90
* @route '/monitor/{monitor}'
*/
show.get = (args: { monitor: string | number | { id: string | number } } | [monitor: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\UptimeMonitorController::show
* @see app/Http/Controllers/UptimeMonitorController.php:90
* @route '/monitor/{monitor}'
*/
show.head = (args: { monitor: string | number | { id: string | number } } | [monitor: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\UptimeMonitorController::edit
* @see app/Http/Controllers/UptimeMonitorController.php:179
* @route '/monitor/{monitor}/edit'
*/
export const edit = (args: { monitor: string | number | { id: string | number } } | [monitor: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/monitor/{monitor}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\UptimeMonitorController::edit
* @see app/Http/Controllers/UptimeMonitorController.php:179
* @route '/monitor/{monitor}/edit'
*/
edit.url = (args: { monitor: string | number | { id: string | number } } | [monitor: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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
* @see app/Http/Controllers/UptimeMonitorController.php:179
* @route '/monitor/{monitor}/edit'
*/
edit.get = (args: { monitor: string | number | { id: string | number } } | [monitor: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\UptimeMonitorController::edit
* @see app/Http/Controllers/UptimeMonitorController.php:179
* @route '/monitor/{monitor}/edit'
*/
edit.head = (args: { monitor: string | number | { id: string | number } } | [monitor: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\UptimeMonitorController::update
* @see app/Http/Controllers/UptimeMonitorController.php:189
* @route '/monitor/{monitor}'
*/
export const update = (args: { monitor: string | number | { id: string | number } } | [monitor: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put","patch"],
    url: '/monitor/{monitor}',
} satisfies RouteDefinition<["put","patch"]>

/**
* @see \App\Http\Controllers\UptimeMonitorController::update
* @see app/Http/Controllers/UptimeMonitorController.php:189
* @route '/monitor/{monitor}'
*/
update.url = (args: { monitor: string | number | { id: string | number } } | [monitor: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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
* @see app/Http/Controllers/UptimeMonitorController.php:189
* @route '/monitor/{monitor}'
*/
update.put = (args: { monitor: string | number | { id: string | number } } | [monitor: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\UptimeMonitorController::update
* @see app/Http/Controllers/UptimeMonitorController.php:189
* @route '/monitor/{monitor}'
*/
update.patch = (args: { monitor: string | number | { id: string | number } } | [monitor: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\UptimeMonitorController::destroy
* @see app/Http/Controllers/UptimeMonitorController.php:240
* @route '/monitor/{monitor}'
*/
export const destroy = (args: { monitor: string | number | { id: string | number } } | [monitor: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/monitor/{monitor}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\UptimeMonitorController::destroy
* @see app/Http/Controllers/UptimeMonitorController.php:240
* @route '/monitor/{monitor}'
*/
destroy.url = (args: { monitor: string | number | { id: string | number } } | [monitor: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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
* @see app/Http/Controllers/UptimeMonitorController.php:240
* @route '/monitor/{monitor}'
*/
destroy.delete = (args: { monitor: string | number | { id: string | number } } | [monitor: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

const UptimeMonitorController = { index, create, store, show, edit, update, destroy }

export default UptimeMonitorController