import { queryParams, type RouteDefinition, type RouteQueryOptions } from './../../wayfinder';
/**
 * @see \Livewire\Mechanisms\HandleRequests\HandleRequests::update
 * @see vendor/livewire/livewire/src/Mechanisms/HandleRequests/HandleRequests.php:138
 * @route '/livewire-5a099756/update'
 */
export const update = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: update.url(options),
    method: 'post',
});

update.definition = {
    methods: ['post'],
    url: '/livewire-5a099756/update',
} satisfies RouteDefinition<['post']>;

/**
 * @see \Livewire\Mechanisms\HandleRequests\HandleRequests::update
 * @see vendor/livewire/livewire/src/Mechanisms/HandleRequests/HandleRequests.php:138
 * @route '/livewire-5a099756/update'
 */
update.url = (options?: RouteQueryOptions) => {
    return update.definition.url + queryParams(options);
};

/**
 * @see \Livewire\Mechanisms\HandleRequests\HandleRequests::update
 * @see vendor/livewire/livewire/src/Mechanisms/HandleRequests/HandleRequests.php:138
 * @route '/livewire-5a099756/update'
 */
update.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: update.url(options),
    method: 'post',
});

const defaultLivewire = {
    update: Object.assign(update, update),
};

export default defaultLivewire;
