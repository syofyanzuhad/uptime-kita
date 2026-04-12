import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
/**
* @see \Resend\Laravel\Http\Controllers\WebhookController::webhook
* @see vendor/resend/resend-laravel/src/Http/Controllers/WebhookController.php:39
* @route '/resend/webhook'
*/
export const webhook = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: webhook.url(options),
    method: 'post',
})

webhook.definition = {
    methods: ["post"],
    url: '/resend/webhook',
} satisfies RouteDefinition<["post"]>

/**
* @see \Resend\Laravel\Http\Controllers\WebhookController::webhook
* @see vendor/resend/resend-laravel/src/Http/Controllers/WebhookController.php:39
* @route '/resend/webhook'
*/
webhook.url = (options?: RouteQueryOptions) => {
    return webhook.definition.url + queryParams(options)
}

/**
* @see \Resend\Laravel\Http\Controllers\WebhookController::webhook
* @see vendor/resend/resend-laravel/src/Http/Controllers/WebhookController.php:39
* @route '/resend/webhook'
*/
webhook.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: webhook.url(options),
    method: 'post',
})

const resend = {
    webhook: Object.assign(webhook, webhook),
}

export default resend