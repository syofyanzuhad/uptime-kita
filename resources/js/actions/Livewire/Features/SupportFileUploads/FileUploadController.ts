import { queryParams, type RouteDefinition, type RouteQueryOptions } from './../../../../wayfinder';
/**
 * @see \Livewire\Features\SupportFileUploads\FileUploadController::handle
 * @see vendor/livewire/livewire/src/Features/SupportFileUploads/FileUploadController.php:27
 * @route '/livewire-5a099756/upload-file'
 */
export const handle = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: handle.url(options),
    method: 'post',
});

handle.definition = {
    methods: ['post'],
    url: '/livewire-5a099756/upload-file',
} satisfies RouteDefinition<['post']>;

/**
 * @see \Livewire\Features\SupportFileUploads\FileUploadController::handle
 * @see vendor/livewire/livewire/src/Features/SupportFileUploads/FileUploadController.php:27
 * @route '/livewire-5a099756/upload-file'
 */
handle.url = (options?: RouteQueryOptions) => {
    return handle.definition.url + queryParams(options);
};

/**
 * @see \Livewire\Features\SupportFileUploads\FileUploadController::handle
 * @see vendor/livewire/livewire/src/Features/SupportFileUploads/FileUploadController.php:27
 * @route '/livewire-5a099756/upload-file'
 */
handle.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: handle.url(options),
    method: 'post',
});

const FileUploadController = { handle };

export default FileUploadController;
