import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Auth\ConfirmablePasswordController::show
* @see app/Http/Controllers/Auth/ConfirmablePasswordController.php:18
* @route '/confirm-password'
*/
export const show = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/confirm-password',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Auth\ConfirmablePasswordController::show
* @see app/Http/Controllers/Auth/ConfirmablePasswordController.php:18
* @route '/confirm-password'
*/
show.url = (options?: RouteQueryOptions) => {
    return show.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Auth\ConfirmablePasswordController::show
* @see app/Http/Controllers/Auth/ConfirmablePasswordController.php:18
* @route '/confirm-password'
*/
show.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Auth\ConfirmablePasswordController::show
* @see app/Http/Controllers/Auth/ConfirmablePasswordController.php:18
* @route '/confirm-password'
*/
show.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Auth\ConfirmablePasswordController::store
* @see app/Http/Controllers/Auth/ConfirmablePasswordController.php:26
* @route '/confirm-password'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/confirm-password',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Auth\ConfirmablePasswordController::store
* @see app/Http/Controllers/Auth/ConfirmablePasswordController.php:26
* @route '/confirm-password'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Auth\ConfirmablePasswordController::store
* @see app/Http/Controllers/Auth/ConfirmablePasswordController.php:26
* @route '/confirm-password'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

const ConfirmablePasswordController = { show, store }

export default ConfirmablePasswordController