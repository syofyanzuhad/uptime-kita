import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
import publicMethodC5d39d from './public'
import importMethod from './import'
/**
* @see \App\Http\Controllers\PublicMonitorController::publicMethod
* @see app/Http/Controllers/PublicMonitorController.php:18
* @route '/public-monitors'
*/
export const publicMethod = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: publicMethod.url(options),
    method: 'get',
})

publicMethod.definition = {
    methods: ["get","head"],
    url: '/public-monitors',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PublicMonitorController::publicMethod
* @see app/Http/Controllers/PublicMonitorController.php:18
* @route '/public-monitors'
*/
publicMethod.url = (options?: RouteQueryOptions) => {
    return publicMethod.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PublicMonitorController::publicMethod
* @see app/Http/Controllers/PublicMonitorController.php:18
* @route '/public-monitors'
*/
publicMethod.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: publicMethod.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PublicMonitorController::publicMethod
* @see app/Http/Controllers/PublicMonitorController.php:18
* @route '/public-monitors'
*/
publicMethod.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: publicMethod.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StatisticMonitorController::__invoke
* @see app/Http/Controllers/StatisticMonitorController.php:14
* @route '/statistic-monitor'
*/
export const statistic = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: statistic.url(options),
    method: 'get',
})

statistic.definition = {
    methods: ["get","head"],
    url: '/statistic-monitor',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StatisticMonitorController::__invoke
* @see app/Http/Controllers/StatisticMonitorController.php:14
* @route '/statistic-monitor'
*/
statistic.url = (options?: RouteQueryOptions) => {
    return statistic.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StatisticMonitorController::__invoke
* @see app/Http/Controllers/StatisticMonitorController.php:14
* @route '/statistic-monitor'
*/
statistic.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: statistic.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StatisticMonitorController::__invoke
* @see app/Http/Controllers/StatisticMonitorController.php:14
* @route '/statistic-monitor'
*/
statistic.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: statistic.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\LatestHistoryController::__invoke
* @see app/Http/Controllers/LatestHistoryController.php:14
* @route '/monitor/{monitor}/latest-history'
*/
export const latestHistory = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: latestHistory.url(args, options),
    method: 'get',
})

latestHistory.definition = {
    methods: ["get","head"],
    url: '/monitor/{monitor}/latest-history',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\LatestHistoryController::__invoke
* @see app/Http/Controllers/LatestHistoryController.php:14
* @route '/monitor/{monitor}/latest-history'
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
* @route '/monitor/{monitor}/latest-history'
*/
latestHistory.get = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: latestHistory.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\LatestHistoryController::__invoke
* @see app/Http/Controllers/LatestHistoryController.php:14
* @route '/monitor/{monitor}/latest-history'
*/
latestHistory.head = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: latestHistory.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MonitorCompactController::compact
* @see app/Http/Controllers/MonitorCompactController.php:16
* @route '/monitors'
*/
export const compact = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: compact.url(options),
    method: 'get',
})

compact.definition = {
    methods: ["get","head"],
    url: '/monitors',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MonitorCompactController::compact
* @see app/Http/Controllers/MonitorCompactController.php:16
* @route '/monitors'
*/
compact.url = (options?: RouteQueryOptions) => {
    return compact.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MonitorCompactController::compact
* @see app/Http/Controllers/MonitorCompactController.php:16
* @route '/monitors'
*/
compact.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: compact.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MonitorCompactController::compact
* @see app/Http/Controllers/MonitorCompactController.php:16
* @route '/monitors'
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
* @see \App\Http\Controllers\PinnedMonitorController::togglePin
* @see app/Http/Controllers/PinnedMonitorController.php:76
* @route '/monitor/{monitorId}/toggle-pin'
*/
export const togglePin = (args: { monitorId: string | number } | [monitorId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: togglePin.url(args, options),
    method: 'post',
})

togglePin.definition = {
    methods: ["post"],
    url: '/monitor/{monitorId}/toggle-pin',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PinnedMonitorController::togglePin
* @see app/Http/Controllers/PinnedMonitorController.php:76
* @route '/monitor/{monitorId}/toggle-pin'
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
* @route '/monitor/{monitorId}/toggle-pin'
*/
togglePin.post = (args: { monitorId: string | number } | [monitorId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: togglePin.url(args, options),
    method: 'post',
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
* @see \App\Http\Controllers\SubscribeMonitorController::__invoke
* @see app/Http/Controllers/SubscribeMonitorController.php:10
* @route '/monitor/{monitorId}/subscribe'
*/
export const subscribe = (args: { monitorId: string | number } | [monitorId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: subscribe.url(args, options),
    method: 'post',
})

subscribe.definition = {
    methods: ["post"],
    url: '/monitor/{monitorId}/subscribe',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SubscribeMonitorController::__invoke
* @see app/Http/Controllers/SubscribeMonitorController.php:10
* @route '/monitor/{monitorId}/subscribe'
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
* @see app/Http/Controllers/SubscribeMonitorController.php:10
* @route '/monitor/{monitorId}/subscribe'
*/
subscribe.post = (args: { monitorId: string | number } | [monitorId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: subscribe.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\UnsubscribeMonitorController::__invoke
* @see app/Http/Controllers/UnsubscribeMonitorController.php:9
* @route '/monitor/{monitorId}/unsubscribe'
*/
export const unsubscribe = (args: { monitorId: string | number } | [monitorId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: unsubscribe.url(args, options),
    method: 'delete',
})

unsubscribe.definition = {
    methods: ["delete"],
    url: '/monitor/{monitorId}/unsubscribe',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\UnsubscribeMonitorController::__invoke
* @see app/Http/Controllers/UnsubscribeMonitorController.php:9
* @route '/monitor/{monitorId}/unsubscribe'
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
* @route '/monitor/{monitorId}/unsubscribe'
*/
unsubscribe.delete = (args: { monitorId: string | number } | [monitorId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: unsubscribe.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\ToggleMonitorActiveController::__invoke
* @see app/Http/Controllers/ToggleMonitorActiveController.php:17
* @route '/monitor/{monitorId}/toggle-active'
*/
export const toggleActive = (args: { monitorId: string | number } | [monitorId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: toggleActive.url(args, options),
    method: 'post',
})

toggleActive.definition = {
    methods: ["post"],
    url: '/monitor/{monitorId}/toggle-active',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ToggleMonitorActiveController::__invoke
* @see app/Http/Controllers/ToggleMonitorActiveController.php:17
* @route '/monitor/{monitorId}/toggle-active'
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
* @see app/Http/Controllers/ToggleMonitorActiveController.php:17
* @route '/monitor/{monitorId}/toggle-active'
*/
toggleActive.post = (args: { monitorId: string | number } | [monitorId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: toggleActive.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\UptimeMonitorController::history
* @see app/Http/Controllers/UptimeMonitorController.php:123
* @route '/monitor/{monitor}/history'
*/
export const history = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: history.url(args, options),
    method: 'get',
})

history.definition = {
    methods: ["get","head"],
    url: '/monitor/{monitor}/history',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\UptimeMonitorController::history
* @see app/Http/Controllers/UptimeMonitorController.php:123
* @route '/monitor/{monitor}/history'
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
* @see \App\Http\Controllers\UptimeMonitorController::history
* @see app/Http/Controllers/UptimeMonitorController.php:123
* @route '/monitor/{monitor}/history'
*/
history.get = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: history.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\UptimeMonitorController::history
* @see app/Http/Controllers/UptimeMonitorController.php:123
* @route '/monitor/{monitor}/history'
*/
history.head = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: history.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\UptimesDailyController::__invoke
* @see app/Http/Controllers/UptimesDailyController.php:13
* @route '/monitor/{monitor}/uptimes-daily'
*/
export const uptimesDaily = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: uptimesDaily.url(args, options),
    method: 'get',
})

uptimesDaily.definition = {
    methods: ["get","head"],
    url: '/monitor/{monitor}/uptimes-daily',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\UptimesDailyController::__invoke
* @see app/Http/Controllers/UptimesDailyController.php:13
* @route '/monitor/{monitor}/uptimes-daily'
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
* @route '/monitor/{monitor}/uptimes-daily'
*/
uptimesDaily.get = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: uptimesDaily.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\UptimesDailyController::__invoke
* @see app/Http/Controllers/UptimesDailyController.php:13
* @route '/monitor/{monitor}/uptimes-daily'
*/
uptimesDaily.head = (args: { monitor: number | { id: number } } | [monitor: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: uptimesDaily.url(args, options),
    method: 'head',
})

const monitor = {
    public: Object.assign(publicMethod, publicMethodC5d39d),
    statistic: Object.assign(statistic, statistic),
    latestHistory: Object.assign(latestHistory, latestHistory),
    compact: Object.assign(compact, compact),
    pinned: Object.assign(pinned, pinned),
    togglePin: Object.assign(togglePin, togglePin),
    private: Object.assign(privateMethod, privateMethod),
    import: Object.assign(importMethod, importMethod),
    index: Object.assign(index, index),
    create: Object.assign(create, create),
    store: Object.assign(store, store),
    show: Object.assign(show, show),
    edit: Object.assign(edit, edit),
    update: Object.assign(update, update),
    destroy: Object.assign(destroy, destroy),
    subscribe: Object.assign(subscribe, subscribe),
    unsubscribe: Object.assign(unsubscribe, unsubscribe),
    toggleActive: Object.assign(toggleActive, toggleActive),
    history: Object.assign(history, history),
    uptimesDaily: Object.assign(uptimesDaily, uptimesDaily),
}

export default monitor