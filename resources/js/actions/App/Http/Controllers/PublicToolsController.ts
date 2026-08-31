import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\PublicToolsController::index
* @see app/Http/Controllers/PublicToolsController.php:24
* @route '/tools'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/tools',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PublicToolsController::index
* @see app/Http/Controllers/PublicToolsController.php:24
* @route '/tools'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PublicToolsController::index
* @see app/Http/Controllers/PublicToolsController.php:24
* @route '/tools'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PublicToolsController::index
* @see app/Http/Controllers/PublicToolsController.php:24
* @route '/tools'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PublicToolsController::websiteChecker
* @see app/Http/Controllers/PublicToolsController.php:123
* @route '/tools/website-checker'
*/
export const websiteChecker = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: websiteChecker.url(options),
    method: 'get',
})

websiteChecker.definition = {
    methods: ["get","head"],
    url: '/tools/website-checker',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PublicToolsController::websiteChecker
* @see app/Http/Controllers/PublicToolsController.php:123
* @route '/tools/website-checker'
*/
websiteChecker.url = (options?: RouteQueryOptions) => {
    return websiteChecker.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PublicToolsController::websiteChecker
* @see app/Http/Controllers/PublicToolsController.php:123
* @route '/tools/website-checker'
*/
websiteChecker.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: websiteChecker.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PublicToolsController::websiteChecker
* @see app/Http/Controllers/PublicToolsController.php:123
* @route '/tools/website-checker'
*/
websiteChecker.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: websiteChecker.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PublicToolsController::domainExpiration
* @see app/Http/Controllers/PublicToolsController.php:94
* @route '/tools/domain-expiration'
*/
export const domainExpiration = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: domainExpiration.url(options),
    method: 'get',
})

domainExpiration.definition = {
    methods: ["get","head"],
    url: '/tools/domain-expiration',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PublicToolsController::domainExpiration
* @see app/Http/Controllers/PublicToolsController.php:94
* @route '/tools/domain-expiration'
*/
domainExpiration.url = (options?: RouteQueryOptions) => {
    return domainExpiration.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PublicToolsController::domainExpiration
* @see app/Http/Controllers/PublicToolsController.php:94
* @route '/tools/domain-expiration'
*/
domainExpiration.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: domainExpiration.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PublicToolsController::domainExpiration
* @see app/Http/Controllers/PublicToolsController.php:94
* @route '/tools/domain-expiration'
*/
domainExpiration.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: domainExpiration.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PublicToolsController::sslChecker
* @see app/Http/Controllers/PublicToolsController.php:136
* @route '/tools/ssl-checker'
*/
export const sslChecker = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: sslChecker.url(options),
    method: 'get',
})

sslChecker.definition = {
    methods: ["get","head"],
    url: '/tools/ssl-checker',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PublicToolsController::sslChecker
* @see app/Http/Controllers/PublicToolsController.php:136
* @route '/tools/ssl-checker'
*/
sslChecker.url = (options?: RouteQueryOptions) => {
    return sslChecker.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PublicToolsController::sslChecker
* @see app/Http/Controllers/PublicToolsController.php:136
* @route '/tools/ssl-checker'
*/
sslChecker.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: sslChecker.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PublicToolsController::sslChecker
* @see app/Http/Controllers/PublicToolsController.php:136
* @route '/tools/ssl-checker'
*/
sslChecker.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: sslChecker.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PublicToolsController::dnsLookup
* @see app/Http/Controllers/PublicToolsController.php:165
* @route '/tools/dns-lookup'
*/
export const dnsLookup = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dnsLookup.url(options),
    method: 'get',
})

dnsLookup.definition = {
    methods: ["get","head"],
    url: '/tools/dns-lookup',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PublicToolsController::dnsLookup
* @see app/Http/Controllers/PublicToolsController.php:165
* @route '/tools/dns-lookup'
*/
dnsLookup.url = (options?: RouteQueryOptions) => {
    return dnsLookup.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PublicToolsController::dnsLookup
* @see app/Http/Controllers/PublicToolsController.php:165
* @route '/tools/dns-lookup'
*/
dnsLookup.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dnsLookup.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PublicToolsController::dnsLookup
* @see app/Http/Controllers/PublicToolsController.php:165
* @route '/tools/dns-lookup'
*/
dnsLookup.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: dnsLookup.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PublicToolsController::headersChecker
* @see app/Http/Controllers/PublicToolsController.php:199
* @route '/tools/headers-checker'
*/
export const headersChecker = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: headersChecker.url(options),
    method: 'get',
})

headersChecker.definition = {
    methods: ["get","head"],
    url: '/tools/headers-checker',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PublicToolsController::headersChecker
* @see app/Http/Controllers/PublicToolsController.php:199
* @route '/tools/headers-checker'
*/
headersChecker.url = (options?: RouteQueryOptions) => {
    return headersChecker.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PublicToolsController::headersChecker
* @see app/Http/Controllers/PublicToolsController.php:199
* @route '/tools/headers-checker'
*/
headersChecker.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: headersChecker.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PublicToolsController::headersChecker
* @see app/Http/Controllers/PublicToolsController.php:199
* @route '/tools/headers-checker'
*/
headersChecker.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: headersChecker.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PublicToolsController::badgeGenerator
* @see app/Http/Controllers/PublicToolsController.php:228
* @route '/tools/badge-generator'
*/
export const badgeGenerator = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: badgeGenerator.url(options),
    method: 'get',
})

badgeGenerator.definition = {
    methods: ["get","head"],
    url: '/tools/badge-generator',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PublicToolsController::badgeGenerator
* @see app/Http/Controllers/PublicToolsController.php:228
* @route '/tools/badge-generator'
*/
badgeGenerator.url = (options?: RouteQueryOptions) => {
    return badgeGenerator.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PublicToolsController::badgeGenerator
* @see app/Http/Controllers/PublicToolsController.php:228
* @route '/tools/badge-generator'
*/
badgeGenerator.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: badgeGenerator.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\PublicToolsController::badgeGenerator
* @see app/Http/Controllers/PublicToolsController.php:228
* @route '/tools/badge-generator'
*/
badgeGenerator.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: badgeGenerator.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PublicToolsController::apiCheckDomainExpiration
* @see app/Http/Controllers/PublicToolsController.php:113
* @route '/api/tools/domain-expiration'
*/
export const apiCheckDomainExpiration = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: apiCheckDomainExpiration.url(options),
    method: 'post',
})

apiCheckDomainExpiration.definition = {
    methods: ["post"],
    url: '/api/tools/domain-expiration',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PublicToolsController::apiCheckDomainExpiration
* @see app/Http/Controllers/PublicToolsController.php:113
* @route '/api/tools/domain-expiration'
*/
apiCheckDomainExpiration.url = (options?: RouteQueryOptions) => {
    return apiCheckDomainExpiration.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PublicToolsController::apiCheckDomainExpiration
* @see app/Http/Controllers/PublicToolsController.php:113
* @route '/api/tools/domain-expiration'
*/
apiCheckDomainExpiration.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: apiCheckDomainExpiration.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PublicToolsController::apiCheckSsl
* @see app/Http/Controllers/PublicToolsController.php:155
* @route '/api/tools/ssl-check'
*/
export const apiCheckSsl = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: apiCheckSsl.url(options),
    method: 'post',
})

apiCheckSsl.definition = {
    methods: ["post"],
    url: '/api/tools/ssl-check',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PublicToolsController::apiCheckSsl
* @see app/Http/Controllers/PublicToolsController.php:155
* @route '/api/tools/ssl-check'
*/
apiCheckSsl.url = (options?: RouteQueryOptions) => {
    return apiCheckSsl.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PublicToolsController::apiCheckSsl
* @see app/Http/Controllers/PublicToolsController.php:155
* @route '/api/tools/ssl-check'
*/
apiCheckSsl.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: apiCheckSsl.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PublicToolsController::apiLookupDns
* @see app/Http/Controllers/PublicToolsController.php:186
* @route '/api/tools/dns-lookup'
*/
export const apiLookupDns = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: apiLookupDns.url(options),
    method: 'post',
})

apiLookupDns.definition = {
    methods: ["post"],
    url: '/api/tools/dns-lookup',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PublicToolsController::apiLookupDns
* @see app/Http/Controllers/PublicToolsController.php:186
* @route '/api/tools/dns-lookup'
*/
apiLookupDns.url = (options?: RouteQueryOptions) => {
    return apiLookupDns.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PublicToolsController::apiLookupDns
* @see app/Http/Controllers/PublicToolsController.php:186
* @route '/api/tools/dns-lookup'
*/
apiLookupDns.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: apiLookupDns.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PublicToolsController::apiCheckHeaders
* @see app/Http/Controllers/PublicToolsController.php:218
* @route '/api/tools/headers-check'
*/
export const apiCheckHeaders = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: apiCheckHeaders.url(options),
    method: 'post',
})

apiCheckHeaders.definition = {
    methods: ["post"],
    url: '/api/tools/headers-check',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PublicToolsController::apiCheckHeaders
* @see app/Http/Controllers/PublicToolsController.php:218
* @route '/api/tools/headers-check'
*/
apiCheckHeaders.url = (options?: RouteQueryOptions) => {
    return apiCheckHeaders.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PublicToolsController::apiCheckHeaders
* @see app/Http/Controllers/PublicToolsController.php:218
* @route '/api/tools/headers-check'
*/
apiCheckHeaders.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: apiCheckHeaders.url(options),
    method: 'post',
})

const PublicToolsController = { index, websiteChecker, domainExpiration, sslChecker, dnsLookup, headersChecker, badgeGenerator, apiCheckDomainExpiration, apiCheckSsl, apiLookupDns, apiCheckHeaders }

export default PublicToolsController