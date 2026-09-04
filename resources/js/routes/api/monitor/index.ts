import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\ManualMonitorCheckController::__invoke
* @see app/Http/Controllers/ManualMonitorCheckController.php:20
* @route '/api/monitor/{domain}/check'
*/
export const check = (args: { domain: string | number } | [domain: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: check.url(args, options),
    method: 'post',
})

check.definition = {
    methods: ["post"],
    url: '/api/monitor/{domain}/check',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ManualMonitorCheckController::__invoke
* @see app/Http/Controllers/ManualMonitorCheckController.php:20
* @route '/api/monitor/{domain}/check'
*/
check.url = (args: { domain: string | number } | [domain: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return check.definition.url
            .replace('{domain}', parsedArgs.domain.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ManualMonitorCheckController::__invoke
* @see app/Http/Controllers/ManualMonitorCheckController.php:20
* @route '/api/monitor/{domain}/check'
*/
check.post = (args: { domain: string | number } | [domain: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: check.url(args, options),
    method: 'post',
})

const monitor = {
    check: Object.assign(check, check),
}

export default monitor