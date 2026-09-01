import { queryParams, type RouteDefinition, type RouteQueryOptions } from './../../../wayfinder';
/**
 * @see \App\Http\Controllers\MonitorCompactController::alias
 * @see app/Http/Controllers/MonitorCompactController.php:17
 * @route '/monitors/compact'
 */
export const alias = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: alias.url(options),
    method: 'get',
});

alias.definition = {
    methods: ['get', 'head'],
    url: '/monitors/compact',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\MonitorCompactController::alias
 * @see app/Http/Controllers/MonitorCompactController.php:17
 * @route '/monitors/compact'
 */
alias.url = (options?: RouteQueryOptions) => {
    return alias.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\MonitorCompactController::alias
 * @see app/Http/Controllers/MonitorCompactController.php:17
 * @route '/monitors/compact'
 */
alias.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: alias.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\MonitorCompactController::alias
 * @see app/Http/Controllers/MonitorCompactController.php:17
 * @route '/monitors/compact'
 */
alias.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: alias.url(options),
    method: 'head',
});

const compact = {
    alias: Object.assign(alias, alias),
};

export default compact;
