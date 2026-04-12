import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\StatusPageOrderController::__invoke
* @see app/Http/Controllers/StatusPageOrderController.php:11
* @route '/status-page-monitor/reorder/{statusPage}'
*/
export const reorder = (args: { statusPage: number | { id: number } } | [statusPage: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reorder.url(args, options),
    method: 'post',
})

reorder.definition = {
    methods: ["post"],
    url: '/status-page-monitor/reorder/{statusPage}',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StatusPageOrderController::__invoke
* @see app/Http/Controllers/StatusPageOrderController.php:11
* @route '/status-page-monitor/reorder/{statusPage}'
*/
reorder.url = (args: { statusPage: number | { id: number } } | [statusPage: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return reorder.definition.url
            .replace('{statusPage}', parsedArgs.statusPage.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StatusPageOrderController::__invoke
* @see app/Http/Controllers/StatusPageOrderController.php:11
* @route '/status-page-monitor/reorder/{statusPage}'
*/
reorder.post = (args: { statusPage: number | { id: number } } | [statusPage: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reorder.url(args, options),
    method: 'post',
})

const statusPageMonitor = {
    reorder: Object.assign(reorder, reorder),
}

export default statusPageMonitor