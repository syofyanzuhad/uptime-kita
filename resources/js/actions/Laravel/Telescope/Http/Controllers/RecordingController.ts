import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../wayfinder'
/**
* @see \Laravel\Telescope\Http\Controllers\RecordingController::toggle
* @see vendor/laravel/telescope/src/Http/Controllers/RecordingController.php:33
* @route '/telescope/telescope-api/toggle-recording'
*/
export const toggle = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: toggle.url(options),
    method: 'post',
})

toggle.definition = {
    methods: ["post"],
    url: '/telescope/telescope-api/toggle-recording',
} satisfies RouteDefinition<["post"]>

/**
* @see \Laravel\Telescope\Http\Controllers\RecordingController::toggle
* @see vendor/laravel/telescope/src/Http/Controllers/RecordingController.php:33
* @route '/telescope/telescope-api/toggle-recording'
*/
toggle.url = (options?: RouteQueryOptions) => {
    return toggle.definition.url + queryParams(options)
}

/**
* @see \Laravel\Telescope\Http\Controllers\RecordingController::toggle
* @see vendor/laravel/telescope/src/Http/Controllers/RecordingController.php:33
* @route '/telescope/telescope-api/toggle-recording'
*/
toggle.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: toggle.url(options),
    method: 'post',
})

const RecordingController = { toggle }

export default RecordingController