import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\UptimeMonitorController::index
* @see app/Http/Controllers/UptimeMonitorController.php:24
* @route '/monitors'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/monitors',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\UptimeMonitorController::index
* @see app/Http/Controllers/UptimeMonitorController.php:24
* @route '/monitors'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\UptimeMonitorController::index
* @see app/Http/Controllers/UptimeMonitorController.php:24
* @route '/monitors'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\UptimeMonitorController::index
* @see app/Http/Controllers/UptimeMonitorController.php:24
* @route '/monitors'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\UptimeMonitorController::create
* @see app/Http/Controllers/UptimeMonitorController.php:131
* @route '/monitors/create'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/monitors/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\UptimeMonitorController::create
* @see app/Http/Controllers/UptimeMonitorController.php:131
* @route '/monitors/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\UptimeMonitorController::create
* @see app/Http/Controllers/UptimeMonitorController.php:131
* @route '/monitors/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\UptimeMonitorController::create
* @see app/Http/Controllers/UptimeMonitorController.php:131
* @route '/monitors/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\UptimeMonitorController::store
* @see app/Http/Controllers/UptimeMonitorController.php:141
* @route '/monitors'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/monitors',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\UptimeMonitorController::store
* @see app/Http/Controllers/UptimeMonitorController.php:141
* @route '/monitors'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\UptimeMonitorController::store
* @see app/Http/Controllers/UptimeMonitorController.php:141
* @route '/monitors'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\UptimeMonitorController::show
* @see app/Http/Controllers/UptimeMonitorController.php:90
* @route '/monitors/{monitor}'
*/
export const show = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/monitors/{monitor}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\UptimeMonitorController::show
* @see app/Http/Controllers/UptimeMonitorController.php:90
* @route '/monitors/{monitor}'
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
* @see app/Http/Controllers/UptimeMonitorController.php:90
* @route '/monitors/{monitor}'
*/
show.get = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\UptimeMonitorController::show
* @see app/Http/Controllers/UptimeMonitorController.php:90
* @route '/monitors/{monitor}'
*/
show.head = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\UptimeMonitorController::edit
* @see app/Http/Controllers/UptimeMonitorController.php:179
* @route '/monitors/{monitor}/edit'
*/
export const edit = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/monitors/{monitor}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\UptimeMonitorController::edit
* @see app/Http/Controllers/UptimeMonitorController.php:179
* @route '/monitors/{monitor}/edit'
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
* @see app/Http/Controllers/UptimeMonitorController.php:179
* @route '/monitors/{monitor}/edit'
*/
edit.get = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\UptimeMonitorController::edit
* @see app/Http/Controllers/UptimeMonitorController.php:179
* @route '/monitors/{monitor}/edit'
*/
edit.head = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\UptimeMonitorController::update
* @see app/Http/Controllers/UptimeMonitorController.php:189
* @route '/monitors/{monitor}'
*/
export const update = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put","patch"],
    url: '/monitors/{monitor}',
} satisfies RouteDefinition<["put","patch"]>

/**
* @see \App\Http\Controllers\UptimeMonitorController::update
* @see app/Http/Controllers/UptimeMonitorController.php:189
* @route '/monitors/{monitor}'
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
* @see app/Http/Controllers/UptimeMonitorController.php:189
* @route '/monitors/{monitor}'
*/
update.put = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\UptimeMonitorController::update
* @see app/Http/Controllers/UptimeMonitorController.php:189
* @route '/monitors/{monitor}'
*/
update.patch = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\UptimeMonitorController::destroy
* @see app/Http/Controllers/UptimeMonitorController.php:240
* @route '/monitors/{monitor}'
*/
export const destroy = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/monitors/{monitor}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\UptimeMonitorController::destroy
* @see app/Http/Controllers/UptimeMonitorController.php:240
* @route '/monitors/{monitor}'
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
* @see app/Http/Controllers/UptimeMonitorController.php:240
* @route '/monitors/{monitor}'
*/
destroy.delete = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

const UptimeMonitorController = { index, create, store, show, edit, update, destroy }

export default UptimeMonitorController