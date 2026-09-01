import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \Livewire\Mechanisms\FrontendAssets\FrontendAssets::returnJavaScriptAsFile
* @see vendor/livewire/livewire/src/Mechanisms/FrontendAssets/FrontendAssets.php:81
* @route '/livewire-5a099756/livewire.js'
*/
export const returnJavaScriptAsFile = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: returnJavaScriptAsFile.url(options),
    method: 'get',
})

returnJavaScriptAsFile.definition = {
    methods: ["get","head"],
    url: '/livewire-5a099756/livewire.js',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Livewire\Mechanisms\FrontendAssets\FrontendAssets::returnJavaScriptAsFile
* @see vendor/livewire/livewire/src/Mechanisms/FrontendAssets/FrontendAssets.php:81
* @route '/livewire-5a099756/livewire.js'
*/
returnJavaScriptAsFile.url = (options?: RouteQueryOptions) => {
    return returnJavaScriptAsFile.definition.url + queryParams(options)
}

/**
* @see \Livewire\Mechanisms\FrontendAssets\FrontendAssets::returnJavaScriptAsFile
* @see vendor/livewire/livewire/src/Mechanisms/FrontendAssets/FrontendAssets.php:81
* @route '/livewire-5a099756/livewire.js'
*/
returnJavaScriptAsFile.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: returnJavaScriptAsFile.url(options),
    method: 'get',
})

/**
* @see \Livewire\Mechanisms\FrontendAssets\FrontendAssets::returnJavaScriptAsFile
* @see vendor/livewire/livewire/src/Mechanisms/FrontendAssets/FrontendAssets.php:81
* @route '/livewire-5a099756/livewire.js'
*/
returnJavaScriptAsFile.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: returnJavaScriptAsFile.url(options),
    method: 'head',
})

/**
* @see \Livewire\Mechanisms\FrontendAssets\FrontendAssets::maps
* @see vendor/livewire/livewire/src/Mechanisms/FrontendAssets/FrontendAssets.php:94
* @route '/livewire-5a099756/livewire.min.js.map'
*/
export const maps = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: maps.url(options),
    method: 'get',
})

maps.definition = {
    methods: ["get","head"],
    url: '/livewire-5a099756/livewire.min.js.map',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Livewire\Mechanisms\FrontendAssets\FrontendAssets::maps
* @see vendor/livewire/livewire/src/Mechanisms/FrontendAssets/FrontendAssets.php:94
* @route '/livewire-5a099756/livewire.min.js.map'
*/
maps.url = (options?: RouteQueryOptions) => {
    return maps.definition.url + queryParams(options)
}

/**
* @see \Livewire\Mechanisms\FrontendAssets\FrontendAssets::maps
* @see vendor/livewire/livewire/src/Mechanisms/FrontendAssets/FrontendAssets.php:94
* @route '/livewire-5a099756/livewire.min.js.map'
*/
maps.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: maps.url(options),
    method: 'get',
})

/**
* @see \Livewire\Mechanisms\FrontendAssets\FrontendAssets::maps
* @see vendor/livewire/livewire/src/Mechanisms/FrontendAssets/FrontendAssets.php:94
* @route '/livewire-5a099756/livewire.min.js.map'
*/
maps.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: maps.url(options),
    method: 'head',
})

/**
* @see \Livewire\Mechanisms\FrontendAssets\FrontendAssets::cspMaps
* @see vendor/livewire/livewire/src/Mechanisms/FrontendAssets/FrontendAssets.php:103
* @route '/livewire-5a099756/livewire.csp.min.js.map'
*/
export const cspMaps = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: cspMaps.url(options),
    method: 'get',
})

cspMaps.definition = {
    methods: ["get","head"],
    url: '/livewire-5a099756/livewire.csp.min.js.map',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Livewire\Mechanisms\FrontendAssets\FrontendAssets::cspMaps
* @see vendor/livewire/livewire/src/Mechanisms/FrontendAssets/FrontendAssets.php:103
* @route '/livewire-5a099756/livewire.csp.min.js.map'
*/
cspMaps.url = (options?: RouteQueryOptions) => {
    return cspMaps.definition.url + queryParams(options)
}

/**
* @see \Livewire\Mechanisms\FrontendAssets\FrontendAssets::cspMaps
* @see vendor/livewire/livewire/src/Mechanisms/FrontendAssets/FrontendAssets.php:103
* @route '/livewire-5a099756/livewire.csp.min.js.map'
*/
cspMaps.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: cspMaps.url(options),
    method: 'get',
})

/**
* @see \Livewire\Mechanisms\FrontendAssets\FrontendAssets::cspMaps
* @see vendor/livewire/livewire/src/Mechanisms/FrontendAssets/FrontendAssets.php:103
* @route '/livewire-5a099756/livewire.csp.min.js.map'
*/
cspMaps.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: cspMaps.url(options),
    method: 'head',
})

const FrontendAssets = { returnJavaScriptAsFile, maps, cspMaps }

export default FrontendAssets