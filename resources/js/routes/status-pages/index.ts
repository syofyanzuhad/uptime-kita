import { applyUrlDefaults, queryParams, type RouteDefinition, type RouteQueryOptions } from './../../wayfinder';
import customDomain from './custom-domain';
import monitors from './monitors';
/**
 * @see \App\Http\Controllers\StatusPageController::index
 * @see app/Http/Controllers/StatusPageController.php:23
 * @route '/status-pages'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});

index.definition = {
    methods: ['get', 'head'],
    url: '/status-pages',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\StatusPageController::index
 * @see app/Http/Controllers/StatusPageController.php:23
 * @route '/status-pages'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\StatusPageController::index
 * @see app/Http/Controllers/StatusPageController.php:23
 * @route '/status-pages'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\StatusPageController::index
 * @see app/Http/Controllers/StatusPageController.php:23
 * @route '/status-pages'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\StatusPageController::create
 * @see app/Http/Controllers/StatusPageController.php:38
 * @route '/status-pages/create'
 */
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
});

create.definition = {
    methods: ['get', 'head'],
    url: '/status-pages/create',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\StatusPageController::create
 * @see app/Http/Controllers/StatusPageController.php:38
 * @route '/status-pages/create'
 */
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\StatusPageController::create
 * @see app/Http/Controllers/StatusPageController.php:38
 * @route '/status-pages/create'
 */
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\StatusPageController::create
 * @see app/Http/Controllers/StatusPageController.php:38
 * @route '/status-pages/create'
 */
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\StatusPageController::store
 * @see app/Http/Controllers/StatusPageController.php:46
 * @route '/status-pages'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

store.definition = {
    methods: ['post'],
    url: '/status-pages',
} satisfies RouteDefinition<['post']>;

/**
 * @see \App\Http\Controllers\StatusPageController::store
 * @see app/Http/Controllers/StatusPageController.php:46
 * @route '/status-pages'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options);
};

/**
 * @see \App\Http\Controllers\StatusPageController::store
 * @see app/Http/Controllers/StatusPageController.php:46
 * @route '/status-pages'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
});

/**
 * @see \App\Http\Controllers\StatusPageController::show
 * @see app/Http/Controllers/StatusPageController.php:71
 * @route '/status-pages/{status_page}'
 */
export const show = (
    args: { status_page: number | { id: number } } | [status_page: number | { id: number }] | number | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
});

show.definition = {
    methods: ['get', 'head'],
    url: '/status-pages/{status_page}',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\StatusPageController::show
 * @see app/Http/Controllers/StatusPageController.php:71
 * @route '/status-pages/{status_page}'
 */
show.url = (
    args: { status_page: number | { id: number } } | [status_page: number | { id: number }] | number | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { status_page: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { status_page: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            status_page: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        status_page: typeof args.status_page === 'object' ? args.status_page.id : args.status_page,
    };

    return show.definition.url.replace('{status_page}', parsedArgs.status_page.toString()).replace(/\/+$/, '') + queryParams(options);
};

/**
 * @see \App\Http\Controllers\StatusPageController::show
 * @see app/Http/Controllers/StatusPageController.php:71
 * @route '/status-pages/{status_page}'
 */
show.get = (
    args: { status_page: number | { id: number } } | [status_page: number | { id: number }] | number | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\StatusPageController::show
 * @see app/Http/Controllers/StatusPageController.php:71
 * @route '/status-pages/{status_page}'
 */
show.head = (
    args: { status_page: number | { id: number } } | [status_page: number | { id: number }] | number | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\StatusPageController::edit
 * @see app/Http/Controllers/StatusPageController.php:88
 * @route '/status-pages/{status_page}/edit'
 */
export const edit = (
    args: { status_page: number | { id: number } } | [status_page: number | { id: number }] | number | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
});

edit.definition = {
    methods: ['get', 'head'],
    url: '/status-pages/{status_page}/edit',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \App\Http\Controllers\StatusPageController::edit
 * @see app/Http/Controllers/StatusPageController.php:88
 * @route '/status-pages/{status_page}/edit'
 */
edit.url = (
    args: { status_page: number | { id: number } } | [status_page: number | { id: number }] | number | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { status_page: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { status_page: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            status_page: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        status_page: typeof args.status_page === 'object' ? args.status_page.id : args.status_page,
    };

    return edit.definition.url.replace('{status_page}', parsedArgs.status_page.toString()).replace(/\/+$/, '') + queryParams(options);
};

/**
 * @see \App\Http\Controllers\StatusPageController::edit
 * @see app/Http/Controllers/StatusPageController.php:88
 * @route '/status-pages/{status_page}/edit'
 */
edit.get = (
    args: { status_page: number | { id: number } } | [status_page: number | { id: number }] | number | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
});

/**
 * @see \App\Http\Controllers\StatusPageController::edit
 * @see app/Http/Controllers/StatusPageController.php:88
 * @route '/status-pages/{status_page}/edit'
 */
edit.head = (
    args: { status_page: number | { id: number } } | [status_page: number | { id: number }] | number | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
});

/**
 * @see \App\Http\Controllers\StatusPageController::update
 * @see app/Http/Controllers/StatusPageController.php:109
 * @route '/status-pages/{status_page}'
 */
export const update = (
    args: { status_page: number | { id: number } } | [status_page: number | { id: number }] | number | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
});

update.definition = {
    methods: ['put', 'patch'],
    url: '/status-pages/{status_page}',
} satisfies RouteDefinition<['put', 'patch']>;

/**
 * @see \App\Http\Controllers\StatusPageController::update
 * @see app/Http/Controllers/StatusPageController.php:109
 * @route '/status-pages/{status_page}'
 */
update.url = (
    args: { status_page: number | { id: number } } | [status_page: number | { id: number }] | number | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { status_page: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { status_page: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            status_page: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        status_page: typeof args.status_page === 'object' ? args.status_page.id : args.status_page,
    };

    return update.definition.url.replace('{status_page}', parsedArgs.status_page.toString()).replace(/\/+$/, '') + queryParams(options);
};

/**
 * @see \App\Http\Controllers\StatusPageController::update
 * @see app/Http/Controllers/StatusPageController.php:109
 * @route '/status-pages/{status_page}'
 */
update.put = (
    args: { status_page: number | { id: number } } | [status_page: number | { id: number }] | number | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
});

/**
 * @see \App\Http\Controllers\StatusPageController::update
 * @see app/Http/Controllers/StatusPageController.php:109
 * @route '/status-pages/{status_page}'
 */
update.patch = (
    args: { status_page: number | { id: number } } | [status_page: number | { id: number }] | number | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
});

/**
 * @see \App\Http\Controllers\StatusPageController::destroy
 * @see app/Http/Controllers/StatusPageController.php:129
 * @route '/status-pages/{status_page}'
 */
export const destroy = (
    args: { status_page: number | { id: number } } | [status_page: number | { id: number }] | number | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
});

destroy.definition = {
    methods: ['delete'],
    url: '/status-pages/{status_page}',
} satisfies RouteDefinition<['delete']>;

/**
 * @see \App\Http\Controllers\StatusPageController::destroy
 * @see app/Http/Controllers/StatusPageController.php:129
 * @route '/status-pages/{status_page}'
 */
destroy.url = (
    args: { status_page: number | { id: number } } | [status_page: number | { id: number }] | number | { id: number },
    options?: RouteQueryOptions,
) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { status_page: args };
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { status_page: args.id };
    }

    if (Array.isArray(args)) {
        args = {
            status_page: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        status_page: typeof args.status_page === 'object' ? args.status_page.id : args.status_page,
    };

    return destroy.definition.url.replace('{status_page}', parsedArgs.status_page.toString()).replace(/\/+$/, '') + queryParams(options);
};

/**
 * @see \App\Http\Controllers\StatusPageController::destroy
 * @see app/Http/Controllers/StatusPageController.php:129
 * @route '/status-pages/{status_page}'
 */
destroy.delete = (
    args: { status_page: number | { id: number } } | [status_page: number | { id: number }] | number | { id: number },
    options?: RouteQueryOptions,
): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
});

const statusPages = {
    index: Object.assign(index, index),
    create: Object.assign(create, create),
    store: Object.assign(store, store),
    show: Object.assign(show, show),
    edit: Object.assign(edit, edit),
    update: Object.assign(update, update),
    destroy: Object.assign(destroy, destroy),
    monitors: Object.assign(monitors, monitors),
    customDomain: Object.assign(customDomain, customDomain),
};

export default statusPages;
