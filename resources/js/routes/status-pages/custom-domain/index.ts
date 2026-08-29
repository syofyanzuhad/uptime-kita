import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\CustomDomainController::update
* @see app/Http/Controllers/CustomDomainController.php:14
* @route '/status-pages/{statusPage}/custom-domain'
*/
export const update = (args: { statusPage: number | { id: number } } | [statusPage: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: update.url(args, options),
    method: 'post',
})

update.definition = {
    methods: ["post"],
    url: '/status-pages/{statusPage}/custom-domain',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CustomDomainController::update
* @see app/Http/Controllers/CustomDomainController.php:14
* @route '/status-pages/{statusPage}/custom-domain'
*/
update.url = (args: { statusPage: number | { id: number } } | [statusPage: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return update.definition.url
            .replace('{statusPage}', parsedArgs.statusPage.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CustomDomainController::update
* @see app/Http/Controllers/CustomDomainController.php:14
* @route '/status-pages/{statusPage}/custom-domain'
*/
update.post = (args: { statusPage: number | { id: number } } | [statusPage: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: update.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CustomDomainController::verify
* @see app/Http/Controllers/CustomDomainController.php:64
* @route '/status-pages/{statusPage}/verify-domain'
*/
export const verify = (args: { statusPage: number | { id: number } } | [statusPage: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: verify.url(args, options),
    method: 'post',
})

verify.definition = {
    methods: ["post"],
    url: '/status-pages/{statusPage}/verify-domain',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CustomDomainController::verify
* @see app/Http/Controllers/CustomDomainController.php:64
* @route '/status-pages/{statusPage}/verify-domain'
*/
verify.url = (args: { statusPage: number | { id: number } } | [statusPage: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return verify.definition.url
            .replace('{statusPage}', parsedArgs.statusPage.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CustomDomainController::verify
* @see app/Http/Controllers/CustomDomainController.php:64
* @route '/status-pages/{statusPage}/verify-domain'
*/
verify.post = (args: { statusPage: number | { id: number } } | [statusPage: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: verify.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CustomDomainController::dns
* @see app/Http/Controllers/CustomDomainController.php:99
* @route '/status-pages/{statusPage}/dns-instructions'
*/
export const dns = (args: { statusPage: number | { id: number } } | [statusPage: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dns.url(args, options),
    method: 'get',
})

dns.definition = {
    methods: ["get","head"],
    url: '/status-pages/{statusPage}/dns-instructions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CustomDomainController::dns
* @see app/Http/Controllers/CustomDomainController.php:99
* @route '/status-pages/{statusPage}/dns-instructions'
*/
dns.url = (args: { statusPage: number | { id: number } } | [statusPage: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return dns.definition.url
            .replace('{statusPage}', parsedArgs.statusPage.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CustomDomainController::dns
* @see app/Http/Controllers/CustomDomainController.php:99
* @route '/status-pages/{statusPage}/dns-instructions'
*/
dns.get = (args: { statusPage: number | { id: number } } | [statusPage: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dns.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CustomDomainController::dns
* @see app/Http/Controllers/CustomDomainController.php:99
* @route '/status-pages/{statusPage}/dns-instructions'
*/
dns.head = (args: { statusPage: number | { id: number } } | [statusPage: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: dns.url(args, options),
    method: 'head',
})

const customDomain = {
    update: Object.assign(update, update),
    verify: Object.assign(verify, verify),
    dns: Object.assign(dns, dns),
}

export default customDomain