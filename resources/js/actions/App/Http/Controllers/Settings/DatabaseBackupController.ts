import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Settings\DatabaseBackupController::index
* @see app/Http/Controllers/Settings/DatabaseBackupController.php:48
* @route '/settings/database'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/settings/database',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Settings\DatabaseBackupController::index
* @see app/Http/Controllers/Settings/DatabaseBackupController.php:48
* @route '/settings/database'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Settings\DatabaseBackupController::index
* @see app/Http/Controllers/Settings/DatabaseBackupController.php:48
* @route '/settings/database'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Settings\DatabaseBackupController::index
* @see app/Http/Controllers/Settings/DatabaseBackupController.php:48
* @route '/settings/database'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Settings\DatabaseBackupController::download
* @see app/Http/Controllers/Settings/DatabaseBackupController.php:77
* @route '/settings/database/download'
*/
export const download = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: download.url(options),
    method: 'get',
})

download.definition = {
    methods: ["get","head"],
    url: '/settings/database/download',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Settings\DatabaseBackupController::download
* @see app/Http/Controllers/Settings/DatabaseBackupController.php:77
* @route '/settings/database/download'
*/
download.url = (options?: RouteQueryOptions) => {
    return download.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Settings\DatabaseBackupController::download
* @see app/Http/Controllers/Settings/DatabaseBackupController.php:77
* @route '/settings/database/download'
*/
download.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: download.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Settings\DatabaseBackupController::download
* @see app/Http/Controllers/Settings/DatabaseBackupController.php:77
* @route '/settings/database/download'
*/
download.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: download.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Settings\DatabaseBackupController::restore
* @see app/Http/Controllers/Settings/DatabaseBackupController.php:153
* @route '/settings/database/restore'
*/
export const restore = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: restore.url(options),
    method: 'post',
})

restore.definition = {
    methods: ["post"],
    url: '/settings/database/restore',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Settings\DatabaseBackupController::restore
* @see app/Http/Controllers/Settings/DatabaseBackupController.php:153
* @route '/settings/database/restore'
*/
restore.url = (options?: RouteQueryOptions) => {
    return restore.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Settings\DatabaseBackupController::restore
* @see app/Http/Controllers/Settings/DatabaseBackupController.php:153
* @route '/settings/database/restore'
*/
restore.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: restore.url(options),
    method: 'post',
})

const DatabaseBackupController = { index, download, restore }

export default DatabaseBackupController