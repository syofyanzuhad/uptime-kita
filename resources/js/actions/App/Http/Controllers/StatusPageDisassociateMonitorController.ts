import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StatusPageDisassociateMonitorController::__invoke
* @see app/Http/Controllers/StatusPageDisassociateMonitorController.php:14
* @route '/status-pages/{statusPage}/monitors/{monitor}'
*/
const StatusPageDisassociateMonitorController = (args: { statusPage: number | { id: number }, monitor: number | { id: number } } | [statusPage: number | { id: number }, monitor: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: StatusPageDisassociateMonitorController.url(args, options),
    method: 'delete',
})

StatusPageDisassociateMonitorController.definition = {
    methods: ["delete"],
    url: '/status-pages/{statusPage}/monitors/{monitor}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\StatusPageDisassociateMonitorController::__invoke
* @see app/Http/Controllers/StatusPageDisassociateMonitorController.php:14
* @route '/status-pages/{statusPage}/monitors/{monitor}'
*/
StatusPageDisassociateMonitorController.url = (args: { statusPage: number | { id: number }, monitor: number | { id: number } } | [statusPage: number | { id: number }, monitor: number | { id: number } ], options?: RouteQueryOptions) => {
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

    return StatusPageDisassociateMonitorController.definition.url
            .replace('{statusPage}', parsedArgs.statusPage.toString())
            .replace('{monitor}', parsedArgs.monitor.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StatusPageDisassociateMonitorController::__invoke
* @see app/Http/Controllers/StatusPageDisassociateMonitorController.php:14
* @route '/status-pages/{statusPage}/monitors/{monitor}'
*/
StatusPageDisassociateMonitorController.delete = (args: { statusPage: number | { id: number }, monitor: number | { id: number } } | [statusPage: number | { id: number }, monitor: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: StatusPageDisassociateMonitorController.url(args, options),
    method: 'delete',
})

export default StatusPageDisassociateMonitorController