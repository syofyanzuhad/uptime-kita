import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \Opcodes\LogViewer\Http\Controllers\FilesController::requestDownload
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/FilesController.php:35
* @route '/log-viewer/api/files/{fileIdentifier}/download/request'
*/
export const requestDownload = (args: { fileIdentifier: string | number } | [fileIdentifier: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: requestDownload.url(args, options),
    method: 'get',
})

requestDownload.definition = {
    methods: ["get","head"],
    url: '/log-viewer/api/files/{fileIdentifier}/download/request',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Opcodes\LogViewer\Http\Controllers\FilesController::requestDownload
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/FilesController.php:35
* @route '/log-viewer/api/files/{fileIdentifier}/download/request'
*/
requestDownload.url = (args: { fileIdentifier: string | number } | [fileIdentifier: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { fileIdentifier: args }
    }

    if (Array.isArray(args)) {
        args = {
            fileIdentifier: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        fileIdentifier: args.fileIdentifier,
    }

    return requestDownload.definition.url
            .replace('{fileIdentifier}', parsedArgs.fileIdentifier.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Opcodes\LogViewer\Http\Controllers\FilesController::requestDownload
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/FilesController.php:35
* @route '/log-viewer/api/files/{fileIdentifier}/download/request'
*/
requestDownload.get = (args: { fileIdentifier: string | number } | [fileIdentifier: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: requestDownload.url(args, options),
    method: 'get',
})

/**
* @see \Opcodes\LogViewer\Http\Controllers\FilesController::requestDownload
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/FilesController.php:35
* @route '/log-viewer/api/files/{fileIdentifier}/download/request'
*/
requestDownload.head = (args: { fileIdentifier: string | number } | [fileIdentifier: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: requestDownload.url(args, options),
    method: 'head',
})

/**
* @see \Opcodes\LogViewer\Http\Controllers\FilesController::clearCache
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/FilesController.php:59
* @route '/log-viewer/api/files/{fileIdentifier}/clear-cache'
*/
export const clearCache = (args: { fileIdentifier: string | number } | [fileIdentifier: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: clearCache.url(args, options),
    method: 'post',
})

clearCache.definition = {
    methods: ["post"],
    url: '/log-viewer/api/files/{fileIdentifier}/clear-cache',
} satisfies RouteDefinition<["post"]>

/**
* @see \Opcodes\LogViewer\Http\Controllers\FilesController::clearCache
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/FilesController.php:59
* @route '/log-viewer/api/files/{fileIdentifier}/clear-cache'
*/
clearCache.url = (args: { fileIdentifier: string | number } | [fileIdentifier: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { fileIdentifier: args }
    }

    if (Array.isArray(args)) {
        args = {
            fileIdentifier: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        fileIdentifier: args.fileIdentifier,
    }

    return clearCache.definition.url
            .replace('{fileIdentifier}', parsedArgs.fileIdentifier.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Opcodes\LogViewer\Http\Controllers\FilesController::clearCache
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/FilesController.php:59
* @route '/log-viewer/api/files/{fileIdentifier}/clear-cache'
*/
clearCache.post = (args: { fileIdentifier: string | number } | [fileIdentifier: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: clearCache.url(args, options),
    method: 'post',
})

/**
* @see \Opcodes\LogViewer\Http\Controllers\FilesController::deleteMethod
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/FilesController.php:81
* @route '/log-viewer/api/files/{fileIdentifier}'
*/
export const deleteMethod = (args: { fileIdentifier: string | number } | [fileIdentifier: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: deleteMethod.url(args, options),
    method: 'delete',
})

deleteMethod.definition = {
    methods: ["delete"],
    url: '/log-viewer/api/files/{fileIdentifier}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \Opcodes\LogViewer\Http\Controllers\FilesController::deleteMethod
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/FilesController.php:81
* @route '/log-viewer/api/files/{fileIdentifier}'
*/
deleteMethod.url = (args: { fileIdentifier: string | number } | [fileIdentifier: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { fileIdentifier: args }
    }

    if (Array.isArray(args)) {
        args = {
            fileIdentifier: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        fileIdentifier: args.fileIdentifier,
    }

    return deleteMethod.definition.url
            .replace('{fileIdentifier}', parsedArgs.fileIdentifier.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Opcodes\LogViewer\Http\Controllers\FilesController::deleteMethod
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/FilesController.php:81
* @route '/log-viewer/api/files/{fileIdentifier}'
*/
deleteMethod.delete = (args: { fileIdentifier: string | number } | [fileIdentifier: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: deleteMethod.url(args, options),
    method: 'delete',
})

/**
* @see \Opcodes\LogViewer\Http\Controllers\FilesController::clearCacheAll
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/FilesController.php:72
* @route '/log-viewer/api/clear-cache-all'
*/
export const clearCacheAll = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: clearCacheAll.url(options),
    method: 'post',
})

clearCacheAll.definition = {
    methods: ["post"],
    url: '/log-viewer/api/clear-cache-all',
} satisfies RouteDefinition<["post"]>

/**
* @see \Opcodes\LogViewer\Http\Controllers\FilesController::clearCacheAll
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/FilesController.php:72
* @route '/log-viewer/api/clear-cache-all'
*/
clearCacheAll.url = (options?: RouteQueryOptions) => {
    return clearCacheAll.definition.url + queryParams(options)
}

/**
* @see \Opcodes\LogViewer\Http\Controllers\FilesController::clearCacheAll
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/FilesController.php:72
* @route '/log-viewer/api/clear-cache-all'
*/
clearCacheAll.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: clearCacheAll.url(options),
    method: 'post',
})

/**
* @see \Opcodes\LogViewer\Http\Controllers\FilesController::deleteMultipleFiles
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/FilesController.php:98
* @route '/log-viewer/api/delete-multiple-files'
*/
export const deleteMultipleFiles = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: deleteMultipleFiles.url(options),
    method: 'post',
})

deleteMultipleFiles.definition = {
    methods: ["post"],
    url: '/log-viewer/api/delete-multiple-files',
} satisfies RouteDefinition<["post"]>

/**
* @see \Opcodes\LogViewer\Http\Controllers\FilesController::deleteMultipleFiles
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/FilesController.php:98
* @route '/log-viewer/api/delete-multiple-files'
*/
deleteMultipleFiles.url = (options?: RouteQueryOptions) => {
    return deleteMultipleFiles.definition.url + queryParams(options)
}

/**
* @see \Opcodes\LogViewer\Http\Controllers\FilesController::deleteMultipleFiles
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/FilesController.php:98
* @route '/log-viewer/api/delete-multiple-files'
*/
deleteMultipleFiles.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: deleteMultipleFiles.url(options),
    method: 'post',
})

/**
* @see \Opcodes\LogViewer\Http\Controllers\FilesController::download
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/FilesController.php:52
* @route '/log-viewer/api/files/{fileIdentifier}/download'
*/
export const download = (args: { fileIdentifier: string | number } | [fileIdentifier: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: download.url(args, options),
    method: 'get',
})

download.definition = {
    methods: ["get","head"],
    url: '/log-viewer/api/files/{fileIdentifier}/download',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Opcodes\LogViewer\Http\Controllers\FilesController::download
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/FilesController.php:52
* @route '/log-viewer/api/files/{fileIdentifier}/download'
*/
download.url = (args: { fileIdentifier: string | number } | [fileIdentifier: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { fileIdentifier: args }
    }

    if (Array.isArray(args)) {
        args = {
            fileIdentifier: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        fileIdentifier: args.fileIdentifier,
    }

    return download.definition.url
            .replace('{fileIdentifier}', parsedArgs.fileIdentifier.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Opcodes\LogViewer\Http\Controllers\FilesController::download
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/FilesController.php:52
* @route '/log-viewer/api/files/{fileIdentifier}/download'
*/
download.get = (args: { fileIdentifier: string | number } | [fileIdentifier: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: download.url(args, options),
    method: 'get',
})

/**
* @see \Opcodes\LogViewer\Http\Controllers\FilesController::download
* @see vendor/opcodesio/log-viewer/src/Http/Controllers/FilesController.php:52
* @route '/log-viewer/api/files/{fileIdentifier}/download'
*/
download.head = (args: { fileIdentifier: string | number } | [fileIdentifier: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: download.url(args, options),
    method: 'head',
})

const files = {
    requestDownload: Object.assign(requestDownload, requestDownload),
    clearCache: Object.assign(clearCache, clearCache),
    delete: Object.assign(deleteMethod, deleteMethod),
    clearCacheAll: Object.assign(clearCacheAll, clearCacheAll),
    deleteMultipleFiles: Object.assign(deleteMultipleFiles, deleteMultipleFiles),
    download: Object.assign(download, download),
}

export default files