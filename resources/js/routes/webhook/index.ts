import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\TelegramWebhookController::telegram
* @see app/Http/Controllers/TelegramWebhookController.php:10
* @route '/webhook/telegram'
*/
export const telegram = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: telegram.url(options),
    method: 'post',
})

telegram.definition = {
    methods: ["post"],
    url: '/webhook/telegram',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TelegramWebhookController::telegram
* @see app/Http/Controllers/TelegramWebhookController.php:10
* @route '/webhook/telegram'
*/
telegram.url = (options?: RouteQueryOptions) => {
    return telegram.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TelegramWebhookController::telegram
* @see app/Http/Controllers/TelegramWebhookController.php:10
* @route '/webhook/telegram'
*/
telegram.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: telegram.url(options),
    method: 'post',
})

const webhook = {
    telegram: Object.assign(telegram, telegram),
}

export default webhook