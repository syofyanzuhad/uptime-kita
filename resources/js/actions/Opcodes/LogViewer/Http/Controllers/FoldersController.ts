import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \Opcodes\LogViewer\Http\Controllers\FoldersController::index
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/FoldersController.php:16
* @route '/log-viewer/api/folders'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/log-viewer/api/folders',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Opcodes\LogViewer\Http\Controllers\FoldersController::index
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/FoldersController.php:16
* @route '/log-viewer/api/folders'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \Opcodes\LogViewer\Http\Controllers\FoldersController::index
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/FoldersController.php:16
* @route '/log-viewer/api/folders'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \Opcodes\LogViewer\Http\Controllers\FoldersController::index
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/FoldersController.php:16
* @route '/log-viewer/api/folders'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \Opcodes\LogViewer\Http\Controllers\FoldersController::requestDownload
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/FoldersController.php:42
* @route '/log-viewer/api/folders/{folderIdentifier}/download/request'
*/
export const requestDownload = (args: { folderIdentifier: string | number } | [folderIdentifier: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: requestDownload.url(args, options),
    method: 'get',
})

requestDownload.definition = {
    methods: ["get","head"],
    url: '/log-viewer/api/folders/{folderIdentifier}/download/request',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Opcodes\LogViewer\Http\Controllers\FoldersController::requestDownload
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/FoldersController.php:42
* @route '/log-viewer/api/folders/{folderIdentifier}/download/request'
*/
requestDownload.url = (args: { folderIdentifier: string | number } | [folderIdentifier: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { folderIdentifier: args }
    }

    if (Array.isArray(args)) {
        args = {
            folderIdentifier: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        folderIdentifier: args.folderIdentifier,
    }

    return requestDownload.definition.url
            .replace('{folderIdentifier}', parsedArgs.folderIdentifier.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Opcodes\LogViewer\Http\Controllers\FoldersController::requestDownload
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/FoldersController.php:42
* @route '/log-viewer/api/folders/{folderIdentifier}/download/request'
*/
requestDownload.get = (args: { folderIdentifier: string | number } | [folderIdentifier: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: requestDownload.url(args, options),
    method: 'get',
})

/**
* @see \Opcodes\LogViewer\Http\Controllers\FoldersController::requestDownload
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/FoldersController.php:42
* @route '/log-viewer/api/folders/{folderIdentifier}/download/request'
*/
requestDownload.head = (args: { folderIdentifier: string | number } | [folderIdentifier: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: requestDownload.url(args, options),
    method: 'head',
})

/**
* @see \Opcodes\LogViewer\Http\Controllers\FoldersController::clearCache
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/FoldersController.php:66
* @route '/log-viewer/api/folders/{folderIdentifier}/clear-cache'
*/
export const clearCache = (args: { folderIdentifier: string | number } | [folderIdentifier: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: clearCache.url(args, options),
    method: 'post',
})

clearCache.definition = {
    methods: ["post"],
    url: '/log-viewer/api/folders/{folderIdentifier}/clear-cache',
} satisfies RouteDefinition<["post"]>

/**
* @see \Opcodes\LogViewer\Http\Controllers\FoldersController::clearCache
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/FoldersController.php:66
* @route '/log-viewer/api/folders/{folderIdentifier}/clear-cache'
*/
clearCache.url = (args: { folderIdentifier: string | number } | [folderIdentifier: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { folderIdentifier: args }
    }

    if (Array.isArray(args)) {
        args = {
            folderIdentifier: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        folderIdentifier: args.folderIdentifier,
    }

    return clearCache.definition.url
            .replace('{folderIdentifier}', parsedArgs.folderIdentifier.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Opcodes\LogViewer\Http\Controllers\FoldersController::clearCache
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/FoldersController.php:66
* @route '/log-viewer/api/folders/{folderIdentifier}/clear-cache'
*/
clearCache.post = (args: { folderIdentifier: string | number } | [folderIdentifier: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: clearCache.url(args, options),
    method: 'post',
})

/**
* @see \Opcodes\LogViewer\Http\Controllers\FoldersController::deleteMethod
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/FoldersController.php:77
* @route '/log-viewer/api/folders/{folderIdentifier}'
*/
export const deleteMethod = (args: { folderIdentifier: string | number } | [folderIdentifier: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: deleteMethod.url(args, options),
    method: 'delete',
})

deleteMethod.definition = {
    methods: ["delete"],
    url: '/log-viewer/api/folders/{folderIdentifier}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \Opcodes\LogViewer\Http\Controllers\FoldersController::deleteMethod
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/FoldersController.php:77
* @route '/log-viewer/api/folders/{folderIdentifier}'
*/
deleteMethod.url = (args: { folderIdentifier: string | number } | [folderIdentifier: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { folderIdentifier: args }
    }

    if (Array.isArray(args)) {
        args = {
            folderIdentifier: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        folderIdentifier: args.folderIdentifier,
    }

    return deleteMethod.definition.url
            .replace('{folderIdentifier}', parsedArgs.folderIdentifier.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Opcodes\LogViewer\Http\Controllers\FoldersController::deleteMethod
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/FoldersController.php:77
* @route '/log-viewer/api/folders/{folderIdentifier}'
*/
deleteMethod.delete = (args: { folderIdentifier: string | number } | [folderIdentifier: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: deleteMethod.url(args, options),
    method: 'delete',
})

/**
* @see \Opcodes\LogViewer\Http\Controllers\FoldersController::download
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/FoldersController.php:59
* @route '/log-viewer/api/folders/{folderIdentifier}/download'
*/
export const download = (args: { folderIdentifier: string | number } | [folderIdentifier: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: download.url(args, options),
    method: 'get',
})

download.definition = {
    methods: ["get","head"],
    url: '/log-viewer/api/folders/{folderIdentifier}/download',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Opcodes\LogViewer\Http\Controllers\FoldersController::download
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/FoldersController.php:59
* @route '/log-viewer/api/folders/{folderIdentifier}/download'
*/
download.url = (args: { folderIdentifier: string | number } | [folderIdentifier: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { folderIdentifier: args }
    }

    if (Array.isArray(args)) {
        args = {
            folderIdentifier: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        folderIdentifier: args.folderIdentifier,
    }

    return download.definition.url
            .replace('{folderIdentifier}', parsedArgs.folderIdentifier.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Opcodes\LogViewer\Http\Controllers\FoldersController::download
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/FoldersController.php:59
* @route '/log-viewer/api/folders/{folderIdentifier}/download'
*/
download.get = (args: { folderIdentifier: string | number } | [folderIdentifier: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: download.url(args, options),
    method: 'get',
})

/**
* @see \Opcodes\LogViewer\Http\Controllers\FoldersController::download
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/FoldersController.php:59
* @route '/log-viewer/api/folders/{folderIdentifier}/download'
*/
download.head = (args: { folderIdentifier: string | number } | [folderIdentifier: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: download.url(args, options),
    method: 'head',
})

const FoldersController = { index, requestDownload, clearCache, deleteMethod, download, delete: deleteMethod }

export default FoldersController