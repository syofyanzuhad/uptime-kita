import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \TraceReplay\Http\Controllers\DashboardController::prompt
* @see vendor/iazaran/trace-replay/src/Http/Controllers/DashboardController.php:164
* @route '/trace-replay/traces/{id}/ai-prompt'
*/
export const prompt = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: prompt.url(args, options),
    method: 'post',
})

prompt.definition = {
    methods: ["post"],
    url: '/trace-replay/traces/{id}/ai-prompt',
} satisfies RouteDefinition<["post"]>

/**
* @see \TraceReplay\Http\Controllers\DashboardController::prompt
* @see vendor/iazaran/trace-replay/src/Http/Controllers/DashboardController.php:164
* @route '/trace-replay/traces/{id}/ai-prompt'
*/
prompt.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    if (Array.isArray(args)) {
        args = {
            id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        id: args.id,
    }

    return prompt.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \TraceReplay\Http\Controllers\DashboardController::prompt
* @see vendor/iazaran/trace-replay/src/Http/Controllers/DashboardController.php:164
* @route '/trace-replay/traces/{id}/ai-prompt'
*/
prompt.post = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: prompt.url(args, options),
    method: 'post',
})

const ai = {
    prompt: Object.assign(prompt, prompt),
}

export default ai