import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Auth\EmailVerificationNotificationController::store
* @see app/Http/Controllers/Auth/EmailVerificationNotificationController.php:14
* @route '/email/verification-notification'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/email/verification-notification',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Auth\EmailVerificationNotificationController::store
* @see app/Http/Controllers/Auth/EmailVerificationNotificationController.php:14
* @route '/email/verification-notification'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Auth\EmailVerificationNotificationController::store
* @see app/Http/Controllers/Auth/EmailVerificationNotificationController.php:14
* @route '/email/verification-notification'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

const EmailVerificationNotificationController = { store }

export default EmailVerificationNotificationController