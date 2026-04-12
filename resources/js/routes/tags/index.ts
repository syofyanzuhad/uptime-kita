import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\TagController::index
* @see app/Http/Controllers/TagController.php:13
* @route '/tags'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/tags',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TagController::index
* @see app/Http/Controllers/TagController.php:13
* @route '/tags'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TagController::index
* @see app/Http/Controllers/TagController.php:13
* @route '/tags'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TagController::index
* @see app/Http/Controllers/TagController.php:13
* @route '/tags'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\TagController::search
* @see app/Http/Controllers/TagController.php:31
* @route '/tags/search'
*/
export const search = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: search.url(options),
    method: 'get',
})

search.definition = {
    methods: ["get","head"],
    url: '/tags/search',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TagController::search
* @see app/Http/Controllers/TagController.php:31
* @route '/tags/search'
*/
search.url = (options?: RouteQueryOptions) => {
    return search.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TagController::search
* @see app/Http/Controllers/TagController.php:31
* @route '/tags/search'
*/
search.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: search.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TagController::search
* @see app/Http/Controllers/TagController.php:31
* @route '/tags/search'
*/
search.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: search.url(options),
    method: 'head',
})

const tags = {
    index: Object.assign(index, index),
    search: Object.assign(search, search),
}

export default tags