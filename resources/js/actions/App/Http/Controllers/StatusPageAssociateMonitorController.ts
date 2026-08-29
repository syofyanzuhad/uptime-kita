import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StatusPageAssociateMonitorController::__invoke
* @see app/Http/Controllers/StatusPageAssociateMonitorController.php:13
* @route '/status-pages/{statusPage}/monitors'
*/
const StatusPageAssociateMonitorController = (args: { statusPage: number | { id: number } } | [statusPage: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: StatusPageAssociateMonitorController.url(args, options),
    method: 'post',
})

StatusPageAssociateMonitorController.definition = {
    methods: ["post"],
    url: '/status-pages/{statusPage}/monitors',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StatusPageAssociateMonitorController::__invoke
* @see app/Http/Controllers/StatusPageAssociateMonitorController.php:13
* @route '/status-pages/{statusPage}/monitors'
*/
StatusPageAssociateMonitorController.url = (args: { statusPage: number | { id: number } } | [statusPage: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return StatusPageAssociateMonitorController.definition.url
            .replace('{statusPage}', parsedArgs.statusPage.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StatusPageAssociateMonitorController::__invoke
* @see app/Http/Controllers/StatusPageAssociateMonitorController.php:13
* @route '/status-pages/{statusPage}/monitors'
*/
StatusPageAssociateMonitorController.post = (args: { statusPage: number | { id: number } } | [statusPage: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: StatusPageAssociateMonitorController.url(args, options),
    method: 'post',
})

export default StatusPageAssociateMonitorController