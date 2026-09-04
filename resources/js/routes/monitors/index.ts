import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
import importMethod from './import'
import exportMethod from './export'
/**
* @see \App\Http\Controllers\LatestHistoryController::__invoke
* @see app/Http/Controllers/LatestHistoryController.php:14
* @route '/monitors/{monitor}/latest-history'
*/
export const latestHistory = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: latestHistory.url(args, options),
    method: 'get',
})

latestHistory.definition = {
    methods: ["get","head"],
    url: '/monitors/{monitor}/latest-history',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\LatestHistoryController::__invoke
* @see app/Http/Controllers/LatestHistoryController.php:14
* @route '/monitors/{monitor}/latest-history'
*/
latestHistory.url = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return latestHistory.definition.url
            .replace('{monitor}', parsedArgs.monitor.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\LatestHistoryController::__invoke
* @see app/Http/Controllers/LatestHistoryController.php:14
* @route '/monitors/{monitor}/latest-history'
*/
latestHistory.get = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: latestHistory.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LatestHistoryController::__invoke
* @see app/Http/Controllers/LatestHistoryController.php:14
* @route '/monitors/{monitor}/latest-history'
*/
latestHistory.head = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: latestHistory.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MonitorCompactController::compact
* @see app/Http/Controllers/MonitorCompactController.php:17
* @route '/monitors/compact'
*/
export const compact = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: compact.url(options),
    method: 'get',
})

compact.definition = {
    methods: ["get","head"],
    url: '/monitors/compact',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MonitorCompactController::compact
* @see app/Http/Controllers/MonitorCompactController.php:17
* @route '/monitors/compact'
*/
compact.url = (options?: RouteQueryOptions) => {
    return compact.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MonitorCompactController::compact
* @see app/Http/Controllers/MonitorCompactController.php:17
* @route '/monitors/compact'
*/
compact.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: compact.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MonitorCompactController::compact
* @see app/Http/Controllers/MonitorCompactController.php:17
* @route '/monitors/compact'
*/
compact.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: compact.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PinnedMonitorController::pinned
* @see app/Http/Controllers/PinnedMonitorController.php:14
* @route '/pinned-monitors'
*/
export const pinned = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: pinned.url(options),
    method: 'get',
})

pinned.definition = {
    methods: ["get","head"],
    url: '/pinned-monitors',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PinnedMonitorController::pinned
* @see app/Http/Controllers/PinnedMonitorController.php:14
* @route '/pinned-monitors'
*/
pinned.url = (options?: RouteQueryOptions) => {
    return pinned.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PinnedMonitorController::pinned
* @see app/Http/Controllers/PinnedMonitorController.php:14
* @route '/pinned-monitors'
*/
pinned.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: pinned.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PinnedMonitorController::pinned
* @see app/Http/Controllers/PinnedMonitorController.php:14
* @route '/pinned-monitors'
*/
pinned.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: pinned.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MonitorExpirationController::expiration
* @see app/Http/Controllers/MonitorExpirationController.php:17
* @route '/monitors/expiration'
*/
export const expiration = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: expiration.url(options),
    method: 'get',
})

expiration.definition = {
    methods: ["get","head"],
    url: '/monitors/expiration',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MonitorExpirationController::expiration
* @see app/Http/Controllers/MonitorExpirationController.php:17
* @route '/monitors/expiration'
*/
expiration.url = (options?: RouteQueryOptions) => {
    return expiration.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MonitorExpirationController::expiration
* @see app/Http/Controllers/MonitorExpirationController.php:17
* @route '/monitors/expiration'
*/
expiration.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: expiration.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MonitorExpirationController::expiration
* @see app/Http/Controllers/MonitorExpirationController.php:17
* @route '/monitors/expiration'
*/
expiration.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: expiration.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MonitorListController::list
* @see app/Http/Controllers/MonitorListController.php:16
* @route '/monitors/{type}'
*/
export const list = (args: { type: string | number } | [type: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: list.url(args, options),
    method: 'get',
})

list.definition = {
    methods: ["get","head"],
    url: '/monitors/{type}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MonitorListController::list
* @see app/Http/Controllers/MonitorListController.php:16
* @route '/monitors/{type}'
*/
list.url = (args: { type: string | number } | [type: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { type: args }
    }

    if (Array.isArray(args)) {
        args = {
            type: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        type: args.type,
    }

    return list.definition.url
            .replace('{type}', parsedArgs.type.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MonitorListController::list
* @see app/Http/Controllers/MonitorListController.php:16
* @route '/monitors/{type}'
*/
list.get = (args: { type: string | number } | [type: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: list.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MonitorListController::list
* @see app/Http/Controllers/MonitorListController.php:16
* @route '/monitors/{type}'
*/
list.head = (args: { type: string | number } | [type: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: list.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PrivateMonitorController::__invoke
* @see app/Http/Controllers/PrivateMonitorController.php:14
* @route '/private-monitors'
*/
export const privateMethod = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: privateMethod.url(options),
    method: 'get',
})

privateMethod.definition = {
    methods: ["get","head"],
    url: '/private-monitors',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PrivateMonitorController::__invoke
* @see app/Http/Controllers/PrivateMonitorController.php:14
* @route '/private-monitors'
*/
privateMethod.url = (options?: RouteQueryOptions) => {
    return privateMethod.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PrivateMonitorController::__invoke
* @see app/Http/Controllers/PrivateMonitorController.php:14
* @route '/private-monitors'
*/
privateMethod.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: privateMethod.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PrivateMonitorController::__invoke
* @see app/Http/Controllers/PrivateMonitorController.php:14
* @route '/private-monitors'
*/
privateMethod.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: privateMethod.url(options),
    method: 'head',
})

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

/**
* @see \App\Http\Controllers\PinnedMonitorController::togglePin
* @see app/Http/Controllers/PinnedMonitorController.php:76
* @route '/monitors/{monitorId}/toggle-pin'
*/
export const togglePin = (args: { monitorId: string | number } | [monitorId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: togglePin.url(args, options),
    method: 'post',
})

togglePin.definition = {
    methods: ["post"],
    url: '/monitors/{monitorId}/toggle-pin',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PinnedMonitorController::togglePin
* @see app/Http/Controllers/PinnedMonitorController.php:76
* @route '/monitors/{monitorId}/toggle-pin'
*/
togglePin.url = (args: { monitorId: string | number } | [monitorId: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { monitorId: args }
    }

    if (Array.isArray(args)) {
        args = {
            monitorId: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        monitorId: args.monitorId,
    }

    return togglePin.definition.url
            .replace('{monitorId}', parsedArgs.monitorId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PinnedMonitorController::togglePin
* @see app/Http/Controllers/PinnedMonitorController.php:76
* @route '/monitors/{monitorId}/toggle-pin'
*/
togglePin.post = (args: { monitorId: string | number } | [monitorId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: togglePin.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ToggleMonitorActiveController::__invoke
* @see app/Http/Controllers/ToggleMonitorActiveController.php:19
* @route '/monitors/{monitorId}/toggle-active'
*/
export const toggleActive = (args: { monitorId: string | number } | [monitorId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: toggleActive.url(args, options),
    method: 'post',
})

toggleActive.definition = {
    methods: ["post"],
    url: '/monitors/{monitorId}/toggle-active',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ToggleMonitorActiveController::__invoke
* @see app/Http/Controllers/ToggleMonitorActiveController.php:19
* @route '/monitors/{monitorId}/toggle-active'
*/
toggleActive.url = (args: { monitorId: string | number } | [monitorId: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { monitorId: args }
    }

    if (Array.isArray(args)) {
        args = {
            monitorId: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        monitorId: args.monitorId,
    }

    return toggleActive.definition.url
            .replace('{monitorId}', parsedArgs.monitorId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ToggleMonitorActiveController::__invoke
* @see app/Http/Controllers/ToggleMonitorActiveController.php:19
* @route '/monitors/{monitorId}/toggle-active'
*/
toggleActive.post = (args: { monitorId: string | number } | [monitorId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: toggleActive.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SubscribeMonitorController::__invoke
* @see app/Http/Controllers/SubscribeMonitorController.php:11
* @route '/monitors/{monitorId}/subscribe'
*/
export const subscribe = (args: { monitorId: string | number } | [monitorId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: subscribe.url(args, options),
    method: 'post',
})

subscribe.definition = {
    methods: ["post"],
    url: '/monitors/{monitorId}/subscribe',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SubscribeMonitorController::__invoke
* @see app/Http/Controllers/SubscribeMonitorController.php:11
* @route '/monitors/{monitorId}/subscribe'
*/
subscribe.url = (args: { monitorId: string | number } | [monitorId: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { monitorId: args }
    }

    if (Array.isArray(args)) {
        args = {
            monitorId: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        monitorId: args.monitorId,
    }

    return subscribe.definition.url
            .replace('{monitorId}', parsedArgs.monitorId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SubscribeMonitorController::__invoke
* @see app/Http/Controllers/SubscribeMonitorController.php:11
* @route '/monitors/{monitorId}/subscribe'
*/
subscribe.post = (args: { monitorId: string | number } | [monitorId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: subscribe.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\UnsubscribeMonitorController::__invoke
* @see app/Http/Controllers/UnsubscribeMonitorController.php:9
* @route '/monitors/{monitorId}/unsubscribe'
*/
export const unsubscribe = (args: { monitorId: string | number } | [monitorId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: unsubscribe.url(args, options),
    method: 'delete',
})

unsubscribe.definition = {
    methods: ["delete"],
    url: '/monitors/{monitorId}/unsubscribe',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\UnsubscribeMonitorController::__invoke
* @see app/Http/Controllers/UnsubscribeMonitorController.php:9
* @route '/monitors/{monitorId}/unsubscribe'
*/
unsubscribe.url = (args: { monitorId: string | number } | [monitorId: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { monitorId: args }
    }

    if (Array.isArray(args)) {
        args = {
            monitorId: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        monitorId: args.monitorId,
    }

    return unsubscribe.definition.url
            .replace('{monitorId}', parsedArgs.monitorId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\UnsubscribeMonitorController::__invoke
* @see app/Http/Controllers/UnsubscribeMonitorController.php:9
* @route '/monitors/{monitorId}/unsubscribe'
*/
unsubscribe.delete = (args: { monitorId: string | number } | [monitorId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: unsubscribe.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\MonitorHistoryController::__invoke
* @see app/Http/Controllers/MonitorHistoryController.php:17
* @route '/monitors/{monitor}/history'
*/
export const history = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: history.url(args, options),
    method: 'get',
})

history.definition = {
    methods: ["get","head"],
    url: '/monitors/{monitor}/history',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MonitorHistoryController::__invoke
* @see app/Http/Controllers/MonitorHistoryController.php:17
* @route '/monitors/{monitor}/history'
*/
history.url = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return history.definition.url
            .replace('{monitor}', parsedArgs.monitor.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MonitorHistoryController::__invoke
* @see app/Http/Controllers/MonitorHistoryController.php:17
* @route '/monitors/{monitor}/history'
*/
history.get = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: history.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MonitorHistoryController::__invoke
* @see app/Http/Controllers/MonitorHistoryController.php:17
* @route '/monitors/{monitor}/history'
*/
history.head = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: history.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\UptimesDailyController::__invoke
* @see app/Http/Controllers/UptimesDailyController.php:13
* @route '/monitors/{monitor}/uptimes-daily'
*/
export const uptimesDaily = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: uptimesDaily.url(args, options),
    method: 'get',
})

uptimesDaily.definition = {
    methods: ["get","head"],
    url: '/monitors/{monitor}/uptimes-daily',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\UptimesDailyController::__invoke
* @see app/Http/Controllers/UptimesDailyController.php:13
* @route '/monitors/{monitor}/uptimes-daily'
*/
uptimesDaily.url = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return uptimesDaily.definition.url
            .replace('{monitor}', parsedArgs.monitor.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\UptimesDailyController::__invoke
* @see app/Http/Controllers/UptimesDailyController.php:13
* @route '/monitors/{monitor}/uptimes-daily'
*/
uptimesDaily.get = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: uptimesDaily.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\UptimesDailyController::__invoke
* @see app/Http/Controllers/UptimesDailyController.php:13
* @route '/monitors/{monitor}/uptimes-daily'
*/
uptimesDaily.head = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: uptimesDaily.url(args, options),
    method: 'head',
})

const monitors = {
    latestHistory: Object.assign(latestHistory, latestHistory),
    compact: Object.assign(compact, compact),
    pinned: Object.assign(pinned, pinned),
    expiration: Object.assign(expiration, expiration),
    list: Object.assign(list, list),
    private: Object.assign(privateMethod, privateMethod),
    import: Object.assign(importMethod, importMethod),
    export: Object.assign(exportMethod, exportMethod),
    index: Object.assign(index, index),
    create: Object.assign(create, create),
    store: Object.assign(store, store),
    show: Object.assign(show, show),
    edit: Object.assign(edit, edit),
    update: Object.assign(update, update),
    destroy: Object.assign(destroy, destroy),
    togglePin: Object.assign(togglePin, togglePin),
    toggleActive: Object.assign(toggleActive, toggleActive),
    subscribe: Object.assign(subscribe, subscribe),
    unsubscribe: Object.assign(unsubscribe, unsubscribe),
    history: Object.assign(history, history),
    uptimesDaily: Object.assign(uptimesDaily, uptimesDaily),
}

export default monitors