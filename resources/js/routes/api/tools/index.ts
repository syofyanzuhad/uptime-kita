import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\PublicToolsController::domainExpiration
* @see app/Http/Controllers/PublicToolsController.php:113
* @route '/api/tools/domain-expiration'
*/
export const domainExpiration = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: domainExpiration.url(options),
    method: 'post',
})

domainExpiration.definition = {
    methods: ["post"],
    url: '/api/tools/domain-expiration',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PublicToolsController::domainExpiration
* @see app/Http/Controllers/PublicToolsController.php:113
* @route '/api/tools/domain-expiration'
*/
domainExpiration.url = (options?: RouteQueryOptions) => {
    return domainExpiration.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PublicToolsController::domainExpiration
* @see app/Http/Controllers/PublicToolsController.php:113
* @route '/api/tools/domain-expiration'
*/
domainExpiration.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: domainExpiration.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PublicToolsController::sslCheck
* @see app/Http/Controllers/PublicToolsController.php:155
* @route '/api/tools/ssl-check'
*/
export const sslCheck = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: sslCheck.url(options),
    method: 'post',
})

sslCheck.definition = {
    methods: ["post"],
    url: '/api/tools/ssl-check',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PublicToolsController::sslCheck
* @see app/Http/Controllers/PublicToolsController.php:155
* @route '/api/tools/ssl-check'
*/
sslCheck.url = (options?: RouteQueryOptions) => {
    return sslCheck.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PublicToolsController::sslCheck
* @see app/Http/Controllers/PublicToolsController.php:155
* @route '/api/tools/ssl-check'
*/
sslCheck.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: sslCheck.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PublicToolsController::dnsLookup
* @see app/Http/Controllers/PublicToolsController.php:186
* @route '/api/tools/dns-lookup'
*/
export const dnsLookup = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: dnsLookup.url(options),
    method: 'post',
})

dnsLookup.definition = {
    methods: ["post"],
    url: '/api/tools/dns-lookup',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PublicToolsController::dnsLookup
* @see app/Http/Controllers/PublicToolsController.php:186
* @route '/api/tools/dns-lookup'
*/
dnsLookup.url = (options?: RouteQueryOptions) => {
    return dnsLookup.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PublicToolsController::dnsLookup
* @see app/Http/Controllers/PublicToolsController.php:186
* @route '/api/tools/dns-lookup'
*/
dnsLookup.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: dnsLookup.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PublicToolsController::headersCheck
* @see app/Http/Controllers/PublicToolsController.php:218
* @route '/api/tools/headers-check'
*/
export const headersCheck = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: headersCheck.url(options),
    method: 'post',
})

headersCheck.definition = {
    methods: ["post"],
    url: '/api/tools/headers-check',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PublicToolsController::headersCheck
* @see app/Http/Controllers/PublicToolsController.php:218
* @route '/api/tools/headers-check'
*/
headersCheck.url = (options?: RouteQueryOptions) => {
    return headersCheck.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PublicToolsController::headersCheck
* @see app/Http/Controllers/PublicToolsController.php:218
* @route '/api/tools/headers-check'
*/
headersCheck.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: headersCheck.url(options),
    method: 'post',
})

const tools = {
    domainExpiration: Object.assign(domainExpiration, domainExpiration),
    sslCheck: Object.assign(sslCheck, sslCheck),
    dnsLookup: Object.assign(dnsLookup, dnsLookup),
    headersCheck: Object.assign(headersCheck, headersCheck),
}

export default tools