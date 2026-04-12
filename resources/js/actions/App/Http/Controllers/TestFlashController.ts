import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\TestFlashController::__invoke
* @see app/Http/Controllers/TestFlashController.php:9
* @route '/test-flash'
*/
const TestFlashController = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: TestFlashController.url(options),
    method: 'get',
})

TestFlashController.definition = {
    methods: ["get","head"],
    url: '/test-flash',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TestFlashController::__invoke
* @see app/Http/Controllers/TestFlashController.php:9
* @route '/test-flash'
*/
TestFlashController.url = (options?: RouteQueryOptions) => {
    return TestFlashController.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TestFlashController::__invoke
* @see app/Http/Controllers/TestFlashController.php:9
* @route '/test-flash'
*/
TestFlashController.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: TestFlashController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TestFlashController::__invoke
* @see app/Http/Controllers/TestFlashController.php:9
* @route '/test-flash'
*/
TestFlashController.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: TestFlashController.url(options),
    method: 'head',
})

export default TestFlashController