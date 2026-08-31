import { applyUrlDefaults, queryParams, type RouteDefinition, type RouteQueryOptions } from './../../../../wayfinder';
/**
 * @see \App\Http\Controllers\MonitorHistoryController::__invoke
 * @see app/Http/Controllers/MonitorHistoryController.php:17
 * @route '/monitor/{monitor}/history'
 */
const MonitorHistoryController = (
    args:
        | { monitor: string | number | { id: string | number } }
        | [monitor: string | number | { id: string | number }]
        | string
        | number
        | { id: string | number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: MonitorHistoryController.url(args, options),
    method: 'get',
});

MonitorHistoryController.definition = {
    methods: ['get', 'head'],
    url: '/monitor/{monitor}/history',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\MonitorHistoryController::__invoke
 * @see app/Http/Controllers/MonitorHistoryController.php:17
 * @route '/monitor/{monitor}/history'
 */
MonitorHistoryController.url = (
    args:
        | { monitor: string | number | { id: string | number } }
        | [monitor: string | number | { id: string | number }]
        | string
        | number
        | { id: string | number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { monitor: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { monitor: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            monitor: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        monitor: typeof args.monitor === 'object' ? args.monitor.id : args.monitor,
    };

    return MonitorHistoryController.definition.url.replace('{monitor}', parsedArgs.monitor.toString()).replace(/\/+$/, '') + queryParams(options);
};

/**
 * @see \App\Http\Controllers\MonitorHistoryController::__invoke
 * @see app/Http/Controllers/MonitorHistoryController.php:17
 * @route '/monitor/{monitor}/history'
 */
MonitorHistoryController.get = (
    args:
        | { monitor: string | number | { id: string | number } }
        | [monitor: string | number | { id: string | number }]
        | string
        | number
        | { id: string | number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: MonitorHistoryController.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\MonitorHistoryController::__invoke
 * @see app/Http/Controllers/MonitorHistoryController.php:17
 * @route '/monitor/{monitor}/history'
 */
MonitorHistoryController.head = (
    args:
        | { monitor: string | number | { id: string | number } }
        | [monitor: string | number | { id: string | number }]
        | string
        | number
        | { id: string | number },
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: MonitorHistoryController.url(args, options),
    method: 'head',
});

export default MonitorHistoryController;
