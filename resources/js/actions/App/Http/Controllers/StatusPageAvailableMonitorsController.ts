import { applyUrlDefaults, queryParams, type RouteDefinition, type RouteQueryOptions } from './../../../../wayfinder';
/**
 * @see \App\Http\Controllers\StatusPageAvailableMonitorsController::__invoke
 * @see app/Http/Controllers/StatusPageAvailableMonitorsController.php:15
 * @route '/status-pages/{statusPage}/available-monitors'
 */
const StatusPageAvailableMonitorsController = (
    args:
        | { statusPage: string | number | { id: string | number } }
        | [statusPage: string | number | { id: string | number }]
        | string
        | number
        | { id: string | number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: StatusPageAvailableMonitorsController.url(args, options),
    method: 'get',
});

StatusPageAvailableMonitorsController.definition = {
    methods: ['get', 'head'],
    url: '/status-pages/{statusPage}/available-monitors',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\StatusPageAvailableMonitorsController::__invoke
 * @see app/Http/Controllers/StatusPageAvailableMonitorsController.php:15
 * @route '/status-pages/{statusPage}/available-monitors'
 */
StatusPageAvailableMonitorsController.url = (
    args:
        | { statusPage: string | number | { id: string | number } }
        | [statusPage: string | number | { id: string | number }]
        | string
        | number
        | { id: string | number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { statusPage: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { statusPage: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            statusPage: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        statusPage: typeof args.statusPage === 'object' ? args.statusPage.id : args.statusPage,
    };

    return (
        StatusPageAvailableMonitorsController.definition.url.replace('{statusPage}', parsedArgs.statusPage.toString()).replace(/\/+$/, '') +
        queryParams(options)
    );
};

/**
 * @see \App\Http\Controllers\StatusPageAvailableMonitorsController::__invoke
 * @see app/Http/Controllers/StatusPageAvailableMonitorsController.php:15
 * @route '/status-pages/{statusPage}/available-monitors'
 */
StatusPageAvailableMonitorsController.get = (
    args:
        | { statusPage: string | number | { id: string | number } }
        | [statusPage: string | number | { id: string | number }]
        | string
        | number
        | { id: string | number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: StatusPageAvailableMonitorsController.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\StatusPageAvailableMonitorsController::__invoke
 * @see app/Http/Controllers/StatusPageAvailableMonitorsController.php:15
 * @route '/status-pages/{statusPage}/available-monitors'
 */
StatusPageAvailableMonitorsController.head = (
    args:
        | { statusPage: string | number | { id: string | number } }
        | [statusPage: string | number | { id: string | number }]
        | string
        | number
        | { id: string | number },
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: StatusPageAvailableMonitorsController.url(args, options),
    method: 'head',
});

export default StatusPageAvailableMonitorsController;
