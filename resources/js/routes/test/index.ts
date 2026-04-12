import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\TestFlashController::__invoke
* @see app/Http/Controllers/TestFlashController.php:9
* @route '/test-flash'
*/
export const flash = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: flash.url(options),
    method: 'get',
})

flash.definition = {
    methods: ["get","head"],
    url: '/test-flash',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TestFlashController::__invoke
* @see app/Http/Controllers/TestFlashController.php:9
* @route '/test-flash'
*/
flash.url = (options?: RouteQueryOptions) => {
    return flash.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TestFlashController::__invoke
* @see app/Http/Controllers/TestFlashController.php:9
* @route '/test-flash'
*/
flash.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: flash.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TestFlashController::__invoke
* @see app/Http/Controllers/TestFlashController.php:9
* @route '/test-flash'
*/
flash.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: flash.url(options),
    method: 'head',
})

const test = {
    flash: Object.assign(flash, flash),
}

export default test