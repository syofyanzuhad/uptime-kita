import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\TelegramWebhookController::handle
* @see app/Http/Controllers/TelegramWebhookController.php:10
* @route '/webhook/telegram'
*/
export const handle = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: handle.url(options),
    method: 'post',
})

handle.definition = {
    methods: ["post"],
    url: '/webhook/telegram',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TelegramWebhookController::handle
* @see app/Http/Controllers/TelegramWebhookController.php:10
* @route '/webhook/telegram'
*/
handle.url = (options?: RouteQueryOptions) => {
    return handle.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TelegramWebhookController::handle
* @see app/Http/Controllers/TelegramWebhookController.php:10
* @route '/webhook/telegram'
*/
handle.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: handle.url(options),
    method: 'post',
})

const TelegramWebhookController = { handle }

export default TelegramWebhookController