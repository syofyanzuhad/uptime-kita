import { queryParams, type RouteDefinition, type RouteQueryOptions } from './../../../wayfinder';
/**
 * @see \App\Http\Controllers\Api\V1\PublicCheckController::__invoke
 * @see app/Http/Controllers/Api/V1/PublicCheckController.php:16
 * @route '/api/v1/check'
 */
export const check = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: check.url(options),
    method: 'get',
});

check.definition = {
    methods: ['get', 'post', 'head'],
    url: '/api/v1/check',
} satisfies RouteDefinition<['get', 'post', 'head']>;

/**
 * @see \App\Http\Controllers\Api\V1\PublicCheckController::__invoke
 * @see app/Http/Controllers/Api/V1/PublicCheckController.php:16
 * @route '/api/v1/check'
 */
check.url = (options?: RouteQueryOptions) => {
    return check.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\Api\V1\PublicCheckController::__invoke
 * @see app/Http/Controllers/Api/V1/PublicCheckController.php:16
 * @route '/api/v1/check'
 */
check.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: check.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\Api\V1\PublicCheckController::__invoke
 * @see app/Http/Controllers/Api/V1/PublicCheckController.php:16
 * @route '/api/v1/check'
 */
check.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: check.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Api\V1\PublicCheckController::__invoke
 * @see app/Http/Controllers/Api/V1/PublicCheckController.php:16
 * @route '/api/v1/check'
 */
check.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: check.url(options),
    method: 'head',
});

const v1 = {
    check: Object.assign(check, check),
};

export default v1;
