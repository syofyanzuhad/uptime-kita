import { applyUrlDefaults, queryParams, type RouteDefinition, type RouteQueryOptions } from './../../../../wayfinder';
/**
 * @see \Livewire\Features\SupportFileUploads\FilePreviewController::handle
 * @see vendor/livewire/livewire/src/Features/SupportFileUploads/FilePreviewController.php:18
 * @route '/livewire-5a099756/preview-file/{filename}'
 */
export const handle = (
    args: { filename: string | number } | [filename: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: handle.url(args, options),
    method: 'get',
});

handle.definition = {
    methods: ['get', 'head'],
    url: '/livewire-5a099756/preview-file/{filename}',
} satisfies RouteDefinition<['get', 'head']>;

/**
 * @see \Livewire\Features\SupportFileUploads\FilePreviewController::handle
 * @see vendor/livewire/livewire/src/Features/SupportFileUploads/FilePreviewController.php:18
 * @route '/livewire-5a099756/preview-file/{filename}'
 */
handle.url = (args: { filename: string | number } | [filename: string | number] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { filename: args };
    }

    if (Array.isArray(args)) {
        args = {
            filename: args[0],
        };
    }

    args = applyUrlDefaults(args);

    const parsedArgs = {
        filename: args.filename,
    };

    return handle.definition.url.replace('{filename}', parsedArgs.filename.toString()).replace(/\/+$/, '') + queryParams(options);
};

/**
 * @see \Livewire\Features\SupportFileUploads\FilePreviewController::handle
 * @see vendor/livewire/livewire/src/Features/SupportFileUploads/FilePreviewController.php:18
 * @route '/livewire-5a099756/preview-file/{filename}'
 */
handle.get = (
    args: { filename: string | number } | [filename: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'get'> => ({
    url: handle.url(args, options),
    method: 'get',
});

/**
 * @see \Livewire\Features\SupportFileUploads\FilePreviewController::handle
 * @see vendor/livewire/livewire/src/Features/SupportFileUploads/FilePreviewController.php:18
 * @route '/livewire-5a099756/preview-file/{filename}'
 */
handle.head = (
    args: { filename: string | number } | [filename: string | number] | string | number,
    options?: RouteQueryOptions,
): RouteDefinition<'head'> => ({
    url: handle.url(args, options),
    method: 'head',
});

const FilePreviewController = { handle };

export default FilePreviewController;
