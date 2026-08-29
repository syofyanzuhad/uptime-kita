import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\ToggleNotificationChannelController::__invoke
* @see app/Http/Controllers/ToggleNotificationChannelController.php:15
* @route '/settings/notifications/{notification}/toggle'
*/
const ToggleNotificationChannelController = (args: { notification: number | { id: number } } | [notification: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: ToggleNotificationChannelController.url(args, options),
    method: 'patch',
})

ToggleNotificationChannelController.definition = {
    methods: ["patch"],
    url: '/settings/notifications/{notification}/toggle',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ToggleNotificationChannelController::__invoke
* @see app/Http/Controllers/ToggleNotificationChannelController.php:15
* @route '/settings/notifications/{notification}/toggle'
*/
ToggleNotificationChannelController.url = (args: { notification: number | { id: number } } | [notification: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { notification: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { notification: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            notification: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        notification: typeof args.notification === 'object'
        ? args.notification.id
        : args.notification,
    }

    return ToggleNotificationChannelController.definition.url
            .replace('{notification}', parsedArgs.notification.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ToggleNotificationChannelController::__invoke
* @see app/Http/Controllers/ToggleNotificationChannelController.php:15
* @route '/settings/notifications/{notification}/toggle'
*/
ToggleNotificationChannelController.patch = (args: { notification: number | { id: number } } | [notification: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: ToggleNotificationChannelController.url(args, options),
    method: 'patch',
})

export default ToggleNotificationChannelController