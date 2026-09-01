import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
/**
* @see [serialized-closure]:2
* @route '/_boost/browser-logs'
*/
export const browserLogs = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: browserLogs.url(options),
    method: 'post',
})

browserLogs.definition = {
    methods: ["post"],
    url: '/_boost/browser-logs',
} satisfies RouteDefinition<["post"]>

/**
* @see [serialized-closure]:2
* @route '/_boost/browser-logs'
*/
browserLogs.url = (options?: RouteQueryOptions) => {
    return browserLogs.definition.url + queryParams(options)
}

/**
* @see [serialized-closure]:2
* @route '/_boost/browser-logs'
*/
browserLogs.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: browserLogs.url(options),
    method: 'post',
})

const boost = {
    browserLogs: Object.assign(browserLogs, browserLogs),
}

export default boost