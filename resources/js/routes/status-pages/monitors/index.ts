import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\StatusPageAssociateMonitorController::__invoke
* @see app/Http/Controllers/StatusPageAssociateMonitorController.php:13
* @route '/status-pages/{statusPage}/monitors'
*/
export const associate = (args: { statusPage: number | { id: number } } | [statusPage: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: associate.url(args, options),
    method: 'post',
})

associate.definition = {
    methods: ["post"],
    url: '/status-pages/{statusPage}/monitors',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StatusPageAssociateMonitorController::__invoke
* @see app/Http/Controllers/StatusPageAssociateMonitorController.php:13
* @route '/status-pages/{statusPage}/monitors'
*/
associate.url = (args: { statusPage: number | { id: number } } | [statusPage: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { statusPage: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { statusPage: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            statusPage: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        statusPage: typeof args.statusPage === 'object'
        ? args.statusPage.id
        : args.statusPage,
    }

    return associate.definition.url
            .replace('{statusPage}', parsedArgs.statusPage.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StatusPageAssociateMonitorController::__invoke
* @see app/Http/Controllers/StatusPageAssociateMonitorController.php:13
* @route '/status-pages/{statusPage}/monitors'
*/
associate.post = (args: { statusPage: number | { id: number } } | [statusPage: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: associate.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StatusPageDisassociateMonitorController::__invoke
* @see app/Http/Controllers/StatusPageDisassociateMonitorController.php:14
* @route '/status-pages/{statusPage}/monitors/{monitor}'
*/
export const disassociate = (args: { statusPage: number | { id: number }, monitor: number | { id: number } } | [statusPage: number | { id: number }, monitor: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: disassociate.url(args, options),
    method: 'delete',
})

disassociate.definition = {
    methods: ["delete"],
    url: '/status-pages/{statusPage}/monitors/{monitor}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\StatusPageDisassociateMonitorController::__invoke
* @see app/Http/Controllers/StatusPageDisassociateMonitorController.php:14
* @route '/status-pages/{statusPage}/monitors/{monitor}'
*/
disassociate.url = (args: { statusPage: number | { id: number }, monitor: number | { id: number } } | [statusPage: number | { id: number }, monitor: number | { id: number } ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            statusPage: args[0],
            monitor: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        statusPage: typeof args.statusPage === 'object'
        ? args.statusPage.id
        : args.statusPage,
        monitor: typeof args.monitor === 'object'
        ? args.monitor.id
        : args.monitor,
    }

    return disassociate.definition.url
            .replace('{statusPage}', parsedArgs.statusPage.toString())
            .replace('{monitor}', parsedArgs.monitor.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StatusPageDisassociateMonitorController::__invoke
* @see app/Http/Controllers/StatusPageDisassociateMonitorController.php:14
* @route '/status-pages/{statusPage}/monitors/{monitor}'
*/
disassociate.delete = (args: { statusPage: number | { id: number }, monitor: number | { id: number } } | [statusPage: number | { id: number }, monitor: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: disassociate.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\StatusPageAvailableMonitorsController::__invoke
* @see app/Http/Controllers/StatusPageAvailableMonitorsController.php:15
* @route '/status-pages/{statusPage}/available-monitors'
*/
export const available = (args: { statusPage: number | { id: number } } | [statusPage: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: available.url(args, options),
    method: 'get',
})

available.definition = {
    methods: ["get","head"],
    url: '/status-pages/{statusPage}/available-monitors',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StatusPageAvailableMonitorsController::__invoke
* @see app/Http/Controllers/StatusPageAvailableMonitorsController.php:15
* @route '/status-pages/{statusPage}/available-monitors'
*/
available.url = (args: { statusPage: number | { id: number } } | [statusPage: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { statusPage: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { statusPage: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            statusPage: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        statusPage: typeof args.statusPage === 'object'
        ? args.statusPage.id
        : args.statusPage,
    }

    return available.definition.url
            .replace('{statusPage}', parsedArgs.statusPage.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StatusPageAvailableMonitorsController::__invoke
* @see app/Http/Controllers/StatusPageAvailableMonitorsController.php:15
* @route '/status-pages/{statusPage}/available-monitors'
*/
available.get = (args: { statusPage: number | { id: number } } | [statusPage: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: available.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StatusPageAvailableMonitorsController::__invoke
* @see app/Http/Controllers/StatusPageAvailableMonitorsController.php:15
* @route '/status-pages/{statusPage}/available-monitors'
*/
available.head = (args: { statusPage: number | { id: number } } | [statusPage: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: available.url(args, options),
    method: 'head',
})

const monitors = {
    associate: Object.assign(associate, associate),
    disassociate: Object.assign(disassociate, disassociate),
    available: Object.assign(available, available),
}

export default monitors