import { applyUrlDefaults, queryParams, type RouteDefinition, type RouteQueryOptions } from './../../../../wayfinder';
/**
 * @see \App\Http\Controllers\CustomDomainController::update
 * @see app/Http/Controllers/CustomDomainController.php:18
 * @route '/status-pages/{statusPage}/custom-domain'
 */
export const update = (
    args:
        | { statusPage: string | number | { id: string | number } }
        | [statusPage: string | number | { id: string | number }]
        | string
        | number
        | { id: string | number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: update.url(args, options),
    method: 'post',
});

update.definition = {
    methods: ['post'],
    url: '/status-pages/{statusPage}/custom-domain',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\CustomDomainController::update
 * @see app/Http/Controllers/CustomDomainController.php:18
 * @route '/status-pages/{statusPage}/custom-domain'
 */
update.url = (
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

    return update.definition.url.replace('{statusPage}', parsedArgs.statusPage.toString()).replace(/\/+$/, '') + queryParams(options);
};

/**
 * @see \App\Http\Controllers\CustomDomainController::update
 * @see app/Http/Controllers/CustomDomainController.php:18
 * @route '/status-pages/{statusPage}/custom-domain'
 */
update.post = (
    args:
        | { statusPage: string | number | { id: string | number } }
        | [statusPage: string | number | { id: string | number }]
        | string
        | number
        | { id: string | number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: update.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\CustomDomainController::verify
 * @see app/Http/Controllers/CustomDomainController.php:54
 * @route '/status-pages/{statusPage}/verify-domain'
 */
export const verify = (
    args:
        | { statusPage: string | number | { id: string | number } }
        | [statusPage: string | number | { id: string | number }]
        | string
        | number
        | { id: string | number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: verify.url(args, options),
    method: 'post',
});

verify.definition = {
    methods: ['post'],
    url: '/status-pages/{statusPage}/verify-domain',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\CustomDomainController::verify
 * @see app/Http/Controllers/CustomDomainController.php:54
 * @route '/status-pages/{statusPage}/verify-domain'
 */
verify.url = (
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

    return verify.definition.url.replace('{statusPage}', parsedArgs.statusPage.toString()).replace(/\/+$/, '') + queryParams(options);
};

/**
 * @see \App\Http\Controllers\CustomDomainController::verify
 * @see app/Http/Controllers/CustomDomainController.php:54
 * @route '/status-pages/{statusPage}/verify-domain'
 */
verify.post = (
    args:
        | { statusPage: string | number | { id: string | number } }
        | [statusPage: string | number | { id: string | number }]
        | string
        | number
        | { id: string | number },
    options?: RouteQueryOptions,
): RouteDefinition<'post'> => ({
    url: verify.url(args, options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\CustomDomainController::dnsInstructions
 * @see app/Http/Controllers/CustomDomainController.php:86
 * @route '/status-pages/{statusPage}/dns-instructions'
 */
export const dnsInstructions = (
    args:
        | { statusPage: string | number | { id: string | number } }
        | [statusPage: string | number | { id: string | number }]
        | string
        | number
        | { id: string | number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: dnsInstructions.url(args, options),
    method: 'get',
});

dnsInstructions.definition = {
    methods: ['get', 'head'],
    url: '/status-pages/{statusPage}/dns-instructions',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\CustomDomainController::dnsInstructions
 * @see app/Http/Controllers/CustomDomainController.php:86
 * @route '/status-pages/{statusPage}/dns-instructions'
 */
dnsInstructions.url = (
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

    return dnsInstructions.definition.url.replace('{statusPage}', parsedArgs.statusPage.toString()).replace(/\/+$/, '') + queryParams(options);
};

/**
 * @see \App\Http\Controllers\CustomDomainController::dnsInstructions
 * @see app/Http/Controllers/CustomDomainController.php:86
 * @route '/status-pages/{statusPage}/dns-instructions'
 */
dnsInstructions.get = (
    args:
        | { statusPage: string | number | { id: string | number } }
        | [statusPage: string | number | { id: string | number }]
        | string
        | number
        | { id: string | number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: dnsInstructions.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\CustomDomainController::dnsInstructions
 * @see app/Http/Controllers/CustomDomainController.php:86
 * @route '/status-pages/{statusPage}/dns-instructions'
 */
dnsInstructions.head = (
    args:
        | { statusPage: string | number | { id: string | number } }
        | [statusPage: string | number | { id: string | number }]
        | string
        | number
        | { id: string | number },
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: dnsInstructions.url(args, options),
    method: 'head',
});

const CustomDomainController = { update, verify, dnsInstructions };

export default CustomDomainController;
