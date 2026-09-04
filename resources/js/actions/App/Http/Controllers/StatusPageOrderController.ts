import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StatusPageOrderController::__invoke
* @see app/Http/Controllers/StatusPageOrderController.php:11
* @route '/status-page-monitor/reorder/{statusPage}'
*/
const StatusPageOrderController = (args: { statusPage: number | { id: number } } | [statusPage: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: StatusPageOrderController.url(args, options),
    method: 'post',
})

StatusPageOrderController.definition = {
    methods: ["post"],
    url: '/status-page-monitor/reorder/{statusPage}',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StatusPageOrderController::__invoke
* @see app/Http/Controllers/StatusPageOrderController.php:11
* @route '/status-page-monitor/reorder/{statusPage}'
*/
StatusPageOrderController.url = (args: { statusPage: number | { id: number } } | [statusPage: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return StatusPageOrderController.definition.url
            .replace('{statusPage}', parsedArgs.statusPage.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StatusPageOrderController::__invoke
* @see app/Http/Controllers/StatusPageOrderController.php:11
* @route '/status-page-monitor/reorder/{statusPage}'
*/
StatusPageOrderController.post = (args: { statusPage: number | { id: number } } | [statusPage: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: StatusPageOrderController.url(args, options),
    method: 'post',
})

export default StatusPageOrderController