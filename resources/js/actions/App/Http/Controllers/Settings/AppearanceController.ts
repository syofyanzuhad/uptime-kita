import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Settings\AppearanceController::__invoke
* @see app/Http/Controllers/Settings/AppearanceController.php:11
* @route '/settings/appearance'
*/
const AppearanceController = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: AppearanceController.url(options),
    method: 'get',
})

AppearanceController.definition = {
    methods: ["get","head"],
    url: '/settings/appearance',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Settings\AppearanceController::__invoke
* @see app/Http/Controllers/Settings/AppearanceController.php:11
* @route '/settings/appearance'
*/
AppearanceController.url = (options?: RouteQueryOptions) => {
    return AppearanceController.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Settings\AppearanceController::__invoke
* @see app/Http/Controllers/Settings/AppearanceController.php:11
* @route '/settings/appearance'
*/
AppearanceController.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: AppearanceController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Settings\AppearanceController::__invoke
* @see app/Http/Controllers/Settings/AppearanceController.php:11
* @route '/settings/appearance'
*/
AppearanceController.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: AppearanceController.url(options),
    method: 'head',
})

export default AppearanceController