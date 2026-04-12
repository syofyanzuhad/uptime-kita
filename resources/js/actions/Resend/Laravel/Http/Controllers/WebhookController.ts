import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../wayfinder'
/**
* @see \Resend\Laravel\Http\Controllers\WebhookController::handleWebhook
* @see vendor/resend/resend-laravel/src/Http/Controllers/WebhookController.php:39
* @route '/resend/webhook'
*/
export const handleWebhook = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: handleWebhook.url(options),
    method: 'post',
})

handleWebhook.definition = {
    methods: ["post"],
    url: '/resend/webhook',
} satisfies RouteDefinition<["post"]>

/**
* @see \Resend\Laravel\Http\Controllers\WebhookController::handleWebhook
* @see vendor/resend/resend-laravel/src/Http/Controllers/WebhookController.php:39
* @route '/resend/webhook'
*/
handleWebhook.url = (options?: RouteQueryOptions) => {
    return handleWebhook.definition.url + queryParams(options)
}

/**
* @see \Resend\Laravel\Http\Controllers\WebhookController::handleWebhook
* @see vendor/resend/resend-laravel/src/Http/Controllers/WebhookController.php:39
* @route '/resend/webhook'
*/
handleWebhook.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: handleWebhook.url(options),
    method: 'post',
})

const WebhookController = { handleWebhook }

export default WebhookController