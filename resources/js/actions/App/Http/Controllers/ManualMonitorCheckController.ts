import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\ManualMonitorCheckController::__invoke
* @see app/Http/Controllers/ManualMonitorCheckController.php:20
* @route '/api/monitor/{domain}/check'
*/
const ManualMonitorCheckController = (args: { domain: string | number } | [domain: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: ManualMonitorCheckController.url(args, options),
    method: 'post',
})

ManualMonitorCheckController.definition = {
    methods: ["post"],
    url: '/api/monitor/{domain}/check',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ManualMonitorCheckController::__invoke
* @see app/Http/Controllers/ManualMonitorCheckController.php:20
* @route '/api/monitor/{domain}/check'
*/
ManualMonitorCheckController.url = (args: { domain: string | number } | [domain: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { domain: args }
    }

    if (Array.isArray(args)) {
        args = {
            domain: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        domain: args.domain,
    }

    return ManualMonitorCheckController.definition.url
            .replace('{domain}', parsedArgs.domain.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ManualMonitorCheckController::__invoke
* @see app/Http/Controllers/ManualMonitorCheckController.php:20
* @route '/api/monitor/{domain}/check'
*/
ManualMonitorCheckController.post = (args: { domain: string | number } | [domain: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: ManualMonitorCheckController.url(args, options),
    method: 'post',
})

export default ManualMonitorCheckController