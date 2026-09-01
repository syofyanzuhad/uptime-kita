import { queryParams, type RouteDefinition, type RouteQueryOptions } from './../../../../../../wayfinder';
/**
 * @see \App\Http\Controllers\Api\V1\PublicCheckController::__invoke
 * @see app/Http/Controllers/Api/V1/PublicCheckController.php:16
 * @route '/api/v1/check'
 */
const PublicCheckController = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: PublicCheckController.url(options),
    method: 'get',
});

PublicCheckController.definition = {
    methods: ['get', 'post', 'head'],
    url: '/api/v1/check',
} satisfies RouteDefinition<['get', 'post', 'head']>;

/**
 * @see \App\Http\Controllers\Api\V1\PublicCheckController::__invoke
 * @see app/Http/Controllers/Api/V1/PublicCheckController.php:16
 * @route '/api/v1/check'
 */
PublicCheckController.url = (options?: RouteQueryOptions) => {
    return PublicCheckController.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\Api\V1\PublicCheckController::__invoke
 * @see app/Http/Controllers/Api/V1/PublicCheckController.php:16
 * @route '/api/v1/check'
 */
PublicCheckController.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: PublicCheckController.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\Api\V1\PublicCheckController::__invoke
 * @see app/Http/Controllers/Api/V1/PublicCheckController.php:16
 * @route '/api/v1/check'
 */
PublicCheckController.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: PublicCheckController.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\Api\V1\PublicCheckController::__invoke
 * @see app/Http/Controllers/Api/V1/PublicCheckController.php:16
 * @route '/api/v1/check'
 */
PublicCheckController.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: PublicCheckController.url(options),
    method: 'head',
});

export default PublicCheckController;
