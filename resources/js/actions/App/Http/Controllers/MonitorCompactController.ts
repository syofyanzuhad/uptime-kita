import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\MonitorCompactController::index
* @see app/Http/Controllers/MonitorCompactController.php:17
* @route '/monitors'
*/
const index4d9ff2a9ee3fbf5d0b1b4e5f9b3dd351 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index4d9ff2a9ee3fbf5d0b1b4e5f9b3dd351.url(options),
    method: 'get',
})

index4d9ff2a9ee3fbf5d0b1b4e5f9b3dd351.definition = {
    methods: ["get","head"],
    url: '/monitors',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MonitorCompactController::index
* @see app/Http/Controllers/MonitorCompactController.php:17
* @route '/monitors'
*/
index4d9ff2a9ee3fbf5d0b1b4e5f9b3dd351.url = (options?: RouteQueryOptions) => {
    return index4d9ff2a9ee3fbf5d0b1b4e5f9b3dd351.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MonitorCompactController::index
* @see app/Http/Controllers/MonitorCompactController.php:17
* @route '/monitors'
*/
index4d9ff2a9ee3fbf5d0b1b4e5f9b3dd351.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index4d9ff2a9ee3fbf5d0b1b4e5f9b3dd351.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MonitorCompactController::index
* @see app/Http/Controllers/MonitorCompactController.php:17
* @route '/monitors'
*/
index4d9ff2a9ee3fbf5d0b1b4e5f9b3dd351.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index4d9ff2a9ee3fbf5d0b1b4e5f9b3dd351.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MonitorCompactController::index
* @see app/Http/Controllers/MonitorCompactController.php:17
* @route '/monitors/compact'
*/
const indexb3dd224135898e01b6529dd9369583d9 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: indexb3dd224135898e01b6529dd9369583d9.url(options),
    method: 'get',
})

indexb3dd224135898e01b6529dd9369583d9.definition = {
    methods: ["get","head"],
    url: '/monitors/compact',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MonitorCompactController::index
* @see app/Http/Controllers/MonitorCompactController.php:17
* @route '/monitors/compact'
*/
indexb3dd224135898e01b6529dd9369583d9.url = (options?: RouteQueryOptions) => {
    return indexb3dd224135898e01b6529dd9369583d9.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MonitorCompactController::index
* @see app/Http/Controllers/MonitorCompactController.php:17
* @route '/monitors/compact'
*/
indexb3dd224135898e01b6529dd9369583d9.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: indexb3dd224135898e01b6529dd9369583d9.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MonitorCompactController::index
* @see app/Http/Controllers/MonitorCompactController.php:17
* @route '/monitors/compact'
*/
indexb3dd224135898e01b6529dd9369583d9.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: indexb3dd224135898e01b6529dd9369583d9.url(options),
    method: 'head',
})

/**
* Multiple routes resolve to \App\Http\Controllers\MonitorCompactController::index, so this export is a
* dictionary keyed by URI rather than a callable. Call a specific route with `index['<uri>'](...)`,
* or import the route by name from your generated `routes/` directory.
*/
export const index = {
    '/monitors': index4d9ff2a9ee3fbf5d0b1b4e5f9b3dd351,
    '/monitors/compact': indexb3dd224135898e01b6529dd9369583d9,
}

const MonitorCompactController = { index }

export default MonitorCompactController