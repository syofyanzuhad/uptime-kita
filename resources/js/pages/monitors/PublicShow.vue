<template>
    <Head :title="`${monitor.host} - Monitor Status`" />

    <TooltipProvider>
        <div class="min-h-full bg-gray-50 dark:bg-gray-900">
            <!-- Header -->
            <div class="fixed top-0 w-full bg-white shadow dark:bg-gray-800">
                <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
                    <div class="flex justify-between space-y-4 sm:flex-row sm:items-center sm:space-y-0">
                        <div class="flex items-center space-x-3 sm:space-x-4">
                            <!-- Back Button -->
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <Link
                                        href="/"
                                        class="flex-shrink-0 rounded-full bg-gray-100 p-1.5 transition-colors hover:bg-gray-200 sm:p-2 dark:bg-gray-700 dark:hover:bg-gray-600"
                                    >
                                        <Icon name="arrowLeft" class="h-4 w-4 cursor-pointer text-gray-600 sm:h-5 sm:w-5 dark:text-gray-300" />
                                    </Link>
                                </TooltipTrigger>
                                <TooltipContent> Go to home page </TooltipContent>
                            </Tooltip>

                            <img
                                v-if="monitor.favicon"
                                :src="monitor.favicon"
                                :alt="`${monitor.host} favicon`"
                                class="h-6 w-6 flex-shrink-0 rounded sm:h-8 sm:w-8"
                                @error="(e) => ((e.target as HTMLImageElement).style.display = 'none')"
                            />
                            <div class="min-w-0 flex-1">
                                <h1
                                    class="max-w-[200px] truncate text-lg font-bold text-gray-900 sm:max-w-none sm:text-xl lg:text-2xl dark:text-white"
                                >
                                    {{ monitor.host }}
                                </h1>
                                <a
                                    :href="monitor.url"
                                    target="_blank"
                                    class="block max-w-[200px] truncate text-xs text-blue-600 hover:text-blue-800 sm:max-w-none sm:text-sm dark:text-blue-400 dark:hover:text-blue-300"
                                >
                                    {{ monitor.url }}
                                </a>
                            </div>
                        </div>

                        <!-- Current Status Badge, Share Button and Theme Toggle -->
                        <div class="flex items-center justify-center space-x-2 sm:justify-end">
                            <!-- Mobile: Icon only -->
                            <span
                                :class="[
                                    'rounded-full p-2 sm:hidden',
                                    monitor.uptime_status === 'up'
                                        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                                        : monitor.uptime_status === 'down'
                                          ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
                                          : monitor.uptime_status === 'not yet checked'
                                            ? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300'
                                            : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                ]"
                            >
                                <Icon :name="getStatusIcon(monitor.uptime_status)" class="h-5 w-5" />
                            </span>

                            <!-- Desktop: Icon with text -->
                            <span
                                :class="[
                                    'hidden items-center rounded-full px-3 py-1 text-sm font-medium whitespace-nowrap sm:inline-flex',
                                    monitor.uptime_status === 'up'
                                        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                                        : monitor.uptime_status === 'down'
                                          ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
                                          : monitor.uptime_status === 'not yet checked'
                                            ? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300'
                                            : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                ]"
                            >
                                <Icon :name="getStatusIcon(monitor.uptime_status)" class="mr-1 h-4 w-4" />
                                {{ getStatusText(monitor.uptime_status) }}
                            </span>

                            <!-- Page Views Badge -->
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                        <Icon name="eye" class="h-3 w-3" />
                                        <span>{{ monitor.formatted_page_views }}</span>
                                    </span>
                                </TooltipTrigger>
                                <TooltipContent>
                                    {{ monitor.page_views_count.toLocaleString() }} page views
                                </TooltipContent>
                            </Tooltip>

                            <!-- Share Button -->
                            <ShareButton 
                                :title="monitor.host || ''" 
                                :status="monitor.uptime_status"
                                :uptime="uptimeStats['24h']"
                                :responseTime="avgResponseTime"
                                :sslStatus="monitor.certificate_check_enabled ? monitor.certificate_status : null"
                            />

                            <!-- Theme Toggle -->
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <button
                                        @click="toggleTheme"
                                        class="cursor-pointer rounded-full bg-gray-100 p-2 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600"
                                    >
                                        <Icon :name="isDark ? 'sun' : 'moon'" class="h-4 w-4 text-gray-600 dark:text-gray-300" />
                                    </button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    {{ isDark ? 'Switch to light mode' : 'Switch to dark mode' }}
                                </TooltipContent>
                            </Tooltip>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="mx-auto mt-24 max-w-7xl px-4 py-4 sm:px-6 sm:py-6 lg:px-8 lg:py-8">
                <!-- Latest 100 Minutes History Bar -->
                <div class="mb-6">
                    <div class="mb-3 flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Latest 100 Minutes</h3>
                            <div class="flex items-center space-x-1 text-xs text-gray-500 dark:text-gray-400">
                                <Icon :name="isRefreshing ? 'loader' : 'refreshCw'" class="h-3 w-3" :class="isRefreshing ? 'animate-spin' : ''" />
                                <span>{{ isRefreshing ? 'Refreshing...' : 'Auto-refresh every minute' }}</span>
                            </div>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ latestHistory.length }} checks</div>
                    </div>
                    <div v-if="monitor.uptime_status === 'not yet checked'" class="rounded-lg bg-gray-50 py-8 text-center dark:bg-gray-800">
                        <Icon name="clock" class="mx-auto mb-2 h-8 w-8 text-gray-400" />
                        <p class="text-sm text-gray-500 dark:text-gray-400">No history data available yet</p>
                    </div>
                    <div v-else class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-center space-x-1 overflow-x-auto">
                            <Tooltip v-for="(date, i) in last100Minutes" :key="i">
                                <TooltipTrigger asChild>
                                    <div
                                        class="h-8 w-1.5 flex-shrink-0 cursor-pointer rounded-sm transition-all sm:w-2"
                                        :class="[
                                            getMinuteStatus(date)?.uptime_status === 'up'
                                                ? 'bg-green-500 hover:bg-green-600'
                                                : getMinuteStatus(date)?.uptime_status === 'down'
                                                  ? 'bg-red-500 hover:bg-red-600'
                                                  : 'bg-gray-300 hover:bg-gray-400 dark:bg-gray-600 dark:hover:bg-gray-500',
                                        ]"
                                    />
                                </TooltipTrigger>
                                <TooltipContent>
                                    <div class="space-y-1">
                                        <div>{{ date.toLocaleString() }}</div>
                                        <div v-if="getMinuteStatus(date)">
                                            <div>{{ getStatusText(getMinuteStatus(date)!.uptime_status) }}</div>
                                            <div v-if="getMinuteStatus(date)!.response_time">{{ getMinuteStatus(date)!.response_time }}ms</div>
                                        </div>
                                        <div v-else class="text-gray-400">No data</div>
                                    </div>
                                </TooltipContent>
                            </Tooltip>
                        </div>
                        <div class="mt-2 flex justify-between text-xs text-gray-400">
                            <span>{{ last100Minutes[0].toLocaleString() }}</span>
                            <span>{{ last100Minutes[last100Minutes.length - 1].toLocaleString() }}</span>
                        </div>
                        <div class="mt-3 flex items-center justify-center space-x-4 text-xs text-gray-600 dark:text-gray-400">
                            <div class="flex items-center space-x-1">
                                <div class="h-3 w-3 rounded-sm bg-green-500"></div>
                                <span>Up</span>
                            </div>
                            <div class="flex items-center space-x-1">
                                <div class="h-3 w-3 rounded-sm bg-red-500"></div>
                                <span>Down</span>
                            </div>
                            <div class="flex items-center space-x-1">
                                <div class="h-3 w-3 rounded-sm bg-gray-300 dark:bg-gray-600"></div>
                                <span>No data</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:gap-6 lg:grid-cols-3">
                    <!-- Left Column - Stats -->
                    <div class="space-y-4 sm:space-y-6 lg:col-span-2">
                        <!-- Uptime Statistics -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Uptime Statistics</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div v-if="monitor.uptime_status === 'not yet checked'" class="py-6 text-center sm:py-8">
                                    <Icon name="clock" class="mx-auto mb-3 h-8 w-8 text-gray-400 sm:mb-4 sm:h-12 sm:w-12" />
                                    <p class="text-sm text-gray-500 sm:text-base dark:text-gray-400">No uptime data available yet</p>
                                    <p class="text-xs text-gray-400 sm:text-sm dark:text-gray-500">Monitor has not been checked yet</p>
                                </div>
                                <div v-else class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
                                    <Tooltip v-for="(value, period) in uptimeStats" :key="period">
                                        <TooltipTrigger asChild>
                                            <div
                                                class="cursor-pointer rounded-lg p-2 text-center transition-colors hover:bg-gray-50 dark:hover:bg-gray-800"
                                            >
                                                <div class="text-xl font-bold sm:text-2xl" :class="getUptimeColor(value)">{{ value }}%</div>
                                                <div class="text-xs text-gray-500 sm:text-sm dark:text-gray-400">
                                                    {{ getPeriodLabel(period) }}
                                                </div>
                                            </div>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <div class="space-y-1">
                                                <div class="font-medium">{{ getPeriodLabel(period) }}</div>
                                                <div>{{ value }}% uptime over the {{ getPeriodLabel(period).toLowerCase() }}</div>
                                                <div class="text-xs text-gray-400">
                                                    {{ value >= 99.5 ? 'Excellent' : value >= 95 ? 'Good' : 'Needs improvement' }}
                                                </div>
                                            </div>
                                        </TooltipContent>
                                    </Tooltip>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Response Time Stats -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Response Time (Last 24 Hours)</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div v-if="monitor.uptime_status === 'not yet checked'" class="py-6 text-center sm:py-8">
                                    <Icon name="clock" class="mx-auto mb-3 h-8 w-8 text-gray-400 sm:mb-4 sm:h-12 sm:w-12" />
                                    <p class="text-sm text-gray-500 sm:text-base dark:text-gray-400">No response time data available yet</p>
                                    <p class="text-xs text-gray-400 sm:text-sm dark:text-gray-500">Monitor has not been checked yet</p>
                                </div>
                                <div v-else class="space-y-4">
                                    <div class="grid grid-cols-3 gap-2 text-center sm:gap-4">
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <div class="cursor-pointer rounded-lg p-2 transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                                    <div class="text-lg font-bold text-gray-900 sm:text-xl lg:text-2xl dark:text-white">
                                                        {{ avgResponseTime }}ms
                                                    </div>
                                                    <div class="text-xs text-gray-500 sm:text-sm dark:text-gray-400">Average</div>
                                                </div>
                                            </TooltipTrigger>
                                            <TooltipContent> Average response time over the last 24 hours </TooltipContent>
                                        </Tooltip>
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <div class="cursor-pointer rounded-lg p-2 transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                                    <div class="text-lg font-bold text-gray-900 sm:text-xl lg:text-2xl dark:text-white">
                                                        {{ minResponseTime }}ms
                                                    </div>
                                                    <div class="text-xs text-gray-500 sm:text-sm dark:text-gray-400">Min</div>
                                                </div>
                                            </TooltipTrigger>
                                            <TooltipContent> Fastest response time in the last 24 hours </TooltipContent>
                                        </Tooltip>
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <div class="cursor-pointer rounded-lg p-2 transition-colors hover:bg-gray-50 dark:hover:bg-gray-800">
                                                    <div class="text-lg font-bold text-gray-900 sm:text-xl lg:text-2xl dark:text-white">
                                                        {{ maxResponseTime }}ms
                                                    </div>
                                                    <div class="text-xs text-gray-500 sm:text-sm dark:text-gray-400">Max</div>
                                                </div>
                                            </TooltipTrigger>
                                            <TooltipContent> Slowest response time in the last 24 hours </TooltipContent>
                                        </Tooltip>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Uptime Graph -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Uptime History (Last 90 Days)</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div v-if="monitor.uptime_status === 'not yet checked'" class="py-6 text-center sm:py-8">
                                    <Icon name="clock" class="mx-auto mb-3 h-8 w-8 text-gray-400 sm:mb-4 sm:h-12 sm:w-12" />
                                    <p class="text-sm text-gray-500 sm:text-base dark:text-gray-400">No uptime history available yet</p>
                                    <p class="text-xs text-gray-400 sm:text-sm dark:text-gray-500">Monitor has not been checked yet</p>
                                </div>
                                <div v-else class="space-y-2">
                                    <div class="flex items-center justify-between text-xs text-gray-600 sm:text-sm dark:text-gray-400">
                                        <span>{{ getDateRange() }}</span>
                                        <span>Today</span>
                                    </div>
                                    <div class="grid h-16 grid-cols-90 gap-0.5 overflow-x-auto sm:h-20">
                                        <Tooltip v-for="day in getUptimeDays()" :key="day.date">
                                            <TooltipTrigger asChild>
                                                <div class="cursor-pointer">
                                                    <div
                                                        v-if="day.uptime"
                                                        :class="[
                                                            'h-full rounded-sm transition-all',
                                                            day.uptime === 100
                                                                ? 'bg-green-500'
                                                                : day.uptime >= 99.5
                                                                  ? 'bg-green-300'
                                                                  : day.uptime >= 95
                                                                    ? 'bg-yellow-400'
                                                                    : 'bg-red-500',
                                                        ]"
                                                    />
                                                    <div v-else class="h-full rounded-sm bg-gray-300 dark:bg-gray-700" />
                                                </div>
                                            </TooltipTrigger>
                                            <TooltipContent> {{ day.date }}: {{ day.uptime }}% uptime </TooltipContent>
                                        </Tooltip>
                                    </div>
                                    <div class="flex flex-wrap items-center justify-center gap-2 text-xs text-gray-600 sm:gap-4 dark:text-gray-400">
                                        <div class="flex items-center space-x-1">
                                            <div class="h-2 w-2 rounded-sm bg-green-500 sm:h-3 sm:w-3"></div>
                                            <span class="text-xs">100% Uptime</span>
                                        </div>
                                        <div class="flex items-center space-x-1">
                                            <div class="h-2 w-2 rounded-sm bg-yellow-500 sm:h-3 sm:w-3"></div>
                                            <span class="text-xs">Partial Outage</span>
                                        </div>
                                        <div class="flex items-center space-x-1">
                                            <div class="h-2 w-2 rounded-sm bg-red-500 sm:h-3 sm:w-3"></div>
                                            <span class="text-xs">Major Outage</span>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Latest Incidents -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Latest Incidents</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div v-if="monitor.uptime_status === 'not yet checked'" class="py-6 text-center sm:py-8">
                                    <Icon name="clock" class="mx-auto mb-3 h-8 w-8 text-gray-400 sm:mb-4 sm:h-12 sm:w-12" />
                                    <p class="text-sm text-gray-500 sm:text-base dark:text-gray-400">No incidents data available yet</p>
                                    <p class="text-xs text-gray-400 sm:text-sm dark:text-gray-500">Monitor has not been checked yet</p>
                                </div>
                                <div v-else-if="latestIncidents.length > 0" class="space-y-3">
                                    <div
                                        v-for="incident in latestIncidents"
                                        :key="incident.id"
                                        class="rounded-lg border border-gray-200 p-3 dark:border-gray-700"
                                    >
                                        <div class="flex items-start justify-between">
                                            <div class="flex items-center gap-2">
                                                <span
                                                    :class="[
                                                        'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                                        incident.type === 'down'
                                                            ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'
                                                            : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
                                                    ]"
                                                >
                                                    {{ incident.type === 'down' ? 'Downtime' : 'Degraded' }}
                                                </span>
                                                <span v-if="incident.status_code" class="text-xs text-gray-500 dark:text-gray-400">
                                                    HTTP {{ incident.status_code }}
                                                </span>
                                            </div>
                                            <span v-if="incident.duration_minutes" class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ formatDuration(incident.duration_minutes) }}
                                            </span>
                                            <span v-else class="text-xs text-red-500">Ongoing</span>
                                        </div>
                                        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                            {{ formatDate(incident.started_at) }}
                                            <span v-if="incident.ended_at"> → {{ formatDate(incident.ended_at) }}</span>
                                        </div>
                                        <div v-if="incident.reason" class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                            {{ incident.reason }}
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="py-4 text-center">
                                    <Icon name="checkCircle" class="mx-auto mb-2 h-8 w-8 text-green-500" />
                                    <p class="text-sm text-gray-500 dark:text-gray-400">No incidents recorded</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500">This monitor has been running smoothly</p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Right Column - Info -->
                    <div class="space-y-4 sm:space-y-6">
                        <!-- Monitor Details -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Monitor Details</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-3">
                                <div>
                                    <div class="text-xs text-gray-500 sm:text-sm dark:text-gray-400">Check Interval</div>
                                    <div class="text-sm font-medium sm:text-base">Every {{ monitor.uptime_check_interval }} minutes</div>
                                </div>

                                <div v-if="monitor.last_check_date">
                                    <div class="text-xs text-gray-500 sm:text-sm dark:text-gray-400">Last Checked</div>
                                    <div class="text-sm font-medium sm:text-base">{{ formatDate(monitor.last_check_date) }}</div>
                                </div>

                                <div v-if="monitor.uptime_status_last_change_date">
                                    <div class="text-xs text-gray-500 sm:text-sm dark:text-gray-400">Status Since</div>
                                    <div class="text-sm font-medium sm:text-base">{{ formatDate(monitor.uptime_status_last_change_date) }}</div>
                                </div>

                                <div v-if="monitor.certificate_check_enabled">
                                    <div class="text-xs text-gray-500 sm:text-sm dark:text-gray-400">SSL Certificate</div>
                                    <div v-if="monitor.certificate_status === 'not yet checked'" class="flex items-center space-x-2">
                                        <Icon name="clock" class="h-4 w-4 text-gray-400" />
                                        <span class="text-sm font-medium text-gray-500 sm:text-base"> Not Yet Checked </span>
                                    </div>
                                    <div v-else class="flex items-center space-x-2">
                                        <Icon
                                            :name="getCertificateIcon(monitor.certificate_status)"
                                            class="h-4 w-4"
                                            :class="getCertificateColor(monitor.certificate_status)"
                                        />
                                        <span class="text-sm font-medium sm:text-base">
                                            {{ getCertificateText(monitor.certificate_status) }}
                                        </span>
                                    </div>
                                    <div
                                        v-if="
                                            monitor.certificate_expiration_date &&
                                            monitor.certificate_status &&
                                            monitor.certificate_status !== 'not yet checked'
                                        "
                                        class="mt-1 text-xs text-gray-500"
                                    >
                                        Expires: {{ formatDate(monitor.certificate_expiration_date) }}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Embed Badge -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center space-x-2">
                                    <Icon name="code" class="h-4 w-4" />
                                    <span>Embed Badge</span>
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div class="flex justify-center">
                                    <img :src="badgeUrl" :alt="`${monitor.host} uptime badge`" class="h-5" />
                                </div>
                                <div class="space-y-3">
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Markdown</label>
                                        <div class="relative">
                                            <code class="block overflow-x-auto rounded bg-gray-100 p-2 text-xs break-all dark:bg-gray-700">{{ badgeMarkdown }}</code>
                                            <button
                                                @click="copyToClipboard(badgeMarkdown, 'markdown')"
                                                class="absolute top-1 right-1 rounded bg-gray-200 p-1 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500"
                                            >
                                                <Icon :name="copiedType === 'markdown' ? 'check' : 'copy'" class="h-3 w-3" />
                                            </button>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">HTML</label>
                                        <div class="relative">
                                            <code class="block overflow-x-auto rounded bg-gray-100 p-2 text-xs break-all dark:bg-gray-700">{{ badgeHtml }}</code>
                                            <button
                                                @click="copyToClipboard(badgeHtml, 'html')"
                                                class="absolute top-1 right-1 rounded bg-gray-200 p-1 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500"
                                            >
                                                <Icon :name="copiedType === 'html' ? 'check' : 'copy'" class="h-3 w-3" />
                                            </button>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">URL Only</label>
                                        <div class="relative">
                                            <code class="block overflow-x-auto rounded bg-gray-100 p-2 text-xs break-all dark:bg-gray-700">{{ badgeUrl }}</code>
                                            <button
                                                @click="copyToClipboard(badgeUrl, 'url')"
                                                class="absolute top-1 right-1 rounded bg-gray-200 p-1 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500"
                                            >
                                                <Icon :name="copiedType === 'url' ? 'check' : 'copy'" class="h-3 w-3" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <!-- Usage Guide -->
                                <div class="border-t border-gray-200 pt-4 dark:border-gray-700">
                                    <div class="mb-3 flex items-center space-x-2">
                                        <Icon name="helpCircle" class="h-4 w-4 text-gray-500 dark:text-gray-400" />
                                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Usage Guide</span>
                                    </div>

                                    <!-- Available Parameters -->
                                    <div class="space-y-3 text-xs">
                                        <div>
                                            <span class="font-medium text-gray-700 dark:text-gray-300">Available Parameters:</span>
                                            <ul class="mt-1 ml-3 list-disc space-y-1 text-gray-600 dark:text-gray-400">
                                                <li>
                                                    <code class="rounded bg-gray-100 px-1 dark:bg-gray-700">period</code> - Time period for uptime calculation
                                                    <ul class="mt-0.5 ml-3 list-none space-y-0.5 text-gray-500 dark:text-gray-500">
                                                        <li><code class="bg-gray-100 dark:bg-gray-700">24h</code> (default), <code class="bg-gray-100 dark:bg-gray-700">7d</code>, <code class="bg-gray-100 dark:bg-gray-700">30d</code>, <code class="bg-gray-100 dark:bg-gray-700">90d</code></li>
                                                    </ul>
                                                </li>
                                                <li>
                                                    <code class="rounded bg-gray-100 px-1 dark:bg-gray-700">style</code> - Badge style
                                                    <ul class="mt-0.5 ml-3 list-none space-y-0.5 text-gray-500 dark:text-gray-500">
                                                        <li><code class="bg-gray-100 dark:bg-gray-700">flat</code> (default), <code class="bg-gray-100 dark:bg-gray-700">flat-square</code>, <code class="bg-gray-100 dark:bg-gray-700">plastic</code>, <code class="bg-gray-100 dark:bg-gray-700">for-the-badge</code></li>
                                                    </ul>
                                                </li>
                                            </ul>
                                        </div>

                                        <!-- Examples -->
                                        <div>
                                            <span class="font-medium text-gray-700 dark:text-gray-300">Examples:</span>
                                            <div class="mt-2 space-y-2">
                                                <div class="rounded bg-gray-50 p-2 dark:bg-gray-800">
                                                    <div class="mb-1 text-gray-500 dark:text-gray-400">Weekly uptime:</div>
                                                    <code class="text-xs break-all">{{ badgeUrl }}?period=7d</code>
                                                </div>
                                                <div class="rounded bg-gray-50 p-2 dark:bg-gray-800">
                                                    <div class="mb-1 text-gray-500 dark:text-gray-400">Flat-square style with 30-day period:</div>
                                                    <code class="text-xs break-all">{{ badgeUrl }}?period=30d&amp;style=flat-square</code>
                                                </div>
                                                <div class="rounded bg-gray-50 p-2 dark:bg-gray-800">
                                                    <div class="mb-1 text-gray-500 dark:text-gray-400">For-the-badge style:</div>
                                                    <code class="text-xs break-all">{{ badgeUrl }}?style=for-the-badge</code>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Preview Different Styles -->
                                        <div>
                                            <span class="font-medium text-gray-700 dark:text-gray-300">Style Preview:</span>
                                            <div class="mt-2 grid grid-cols-2 gap-2">
                                                <Tooltip>
                                                    <TooltipTrigger asChild>
                                                        <div class="flex cursor-pointer flex-col items-center rounded bg-gray-50 p-2 hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700">
                                                            <img :src="`${badgeUrl}?style=flat`" alt="flat style" class="h-5" />
                                                            <span class="mt-1 text-gray-500 dark:text-gray-400">flat</span>
                                                        </div>
                                                    </TooltipTrigger>
                                                    <TooltipContent>Default style</TooltipContent>
                                                </Tooltip>
                                                <Tooltip>
                                                    <TooltipTrigger asChild>
                                                        <div class="flex cursor-pointer flex-col items-center rounded bg-gray-50 p-2 hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700">
                                                            <img :src="`${badgeUrl}?style=flat-square`" alt="flat-square style" class="h-5" />
                                                            <span class="mt-1 text-gray-500 dark:text-gray-400">flat-square</span>
                                                        </div>
                                                    </TooltipTrigger>
                                                    <TooltipContent>Flat with square corners</TooltipContent>
                                                </Tooltip>
                                                <Tooltip>
                                                    <TooltipTrigger asChild>
                                                        <div class="flex cursor-pointer flex-col items-center rounded bg-gray-50 p-2 hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700">
                                                            <img :src="`${badgeUrl}?style=plastic`" alt="plastic style" class="h-5" />
                                                            <span class="mt-1 text-gray-500 dark:text-gray-400">plastic</span>
                                                        </div>
                                                    </TooltipTrigger>
                                                    <TooltipContent>Plastic 3D style</TooltipContent>
                                                </Tooltip>
                                                <Tooltip>
                                                    <TooltipTrigger asChild>
                                                        <div class="flex cursor-pointer flex-col items-center rounded bg-gray-50 p-2 hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700">
                                                            <img :src="`${badgeUrl}?style=for-the-badge`" alt="for-the-badge style" class="h-5" />
                                                            <span class="mt-1 text-gray-500 dark:text-gray-400">for-the-badge</span>
                                                        </div>
                                                    </TooltipTrigger>
                                                    <TooltipContent>Large badge style</TooltipContent>
                                                </Tooltip>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <PublicFooter />
        </div>
    </TooltipProvider>
</template>

<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import PublicFooter from '@/components/PublicFooter.vue';
import ShareButton from '@/components/ShareButton.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import type { Monitor, MonitorHistory } from '@/types/monitor';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

interface Props {
    monitor: { data: Monitor };
    histories: MonitorHistory[];
    uptimeStats: {
        '24h': number;
        '7d': number;
        '30d': number;
        '90d': number;
    };
    responseTimeStats: {
        average: number;
        min: number;
        max: number;
    };
    recentIncidents: any[];
}

const props = defineProps<Props>();
const monitor = computed(() => props.monitor.data);

// Latest incidents from props (MonitorIncident model)
const latestIncidents = computed(() => props.recentIncidents || []);

// Format duration in human readable format
const formatDuration = (minutes: number): string => {
    if (minutes < 60) {
        return `${minutes}m`;
    }
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    if (hours < 24) {
        return mins > 0 ? `${hours}h ${mins}m` : `${hours}h`;
    }
    const days = Math.floor(hours / 24);
    const remainingHours = hours % 24;
    return remainingHours > 0 ? `${days}d ${remainingHours}h` : `${days}d`;
};

// Badge embed functionality
const badgeUrl = computed(() => {
    const baseUrl = window.location.origin;
    return `${baseUrl}/badge/${monitor.value.host}`;
});

const badgeMarkdown = computed(() => {
    return `![Uptime](${badgeUrl.value})`;
});

const badgeHtml = computed(() => {
    return `<img src="${badgeUrl.value}" alt="Uptime" />`;
});

const copiedType = ref<string | null>(null);

const copyToClipboard = async (text: string, type: string) => {
    try {
        await navigator.clipboard.writeText(text);
        copiedType.value = type;
        setTimeout(() => {
            copiedType.value = null;
        }, 2000);
    } catch (err) {
        console.error('Failed to copy:', err);
    }
};

// Auto-refetch functionality
const refreshInterval = ref<number | null>(null);
const lastRefreshTime = ref<Date>(new Date());
const isRefreshing = ref(false);

// Theme toggle functionality
const isDark = ref(false);

const toggleTheme = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

// Refetch function
const refetchHistory = () => {
    lastRefreshTime.value = new Date();
    isRefreshing.value = true;

    // Update the 100-minute timeline
    last100Minutes.value = getLast100Minutes();

    // Only fetch history data without full page refresh
    router.visit(window.location.pathname, {
        only: ['histories'],
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => {
            isRefreshing.value = false;
        },
    });
};

onMounted(() => {
    // Check for saved theme preference or default to light mode
    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

    if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
        isDark.value = true;
        document.documentElement.classList.add('dark');
    }

    // Start auto-refresh timer (every 60 seconds)
    refreshInterval.value = window.setInterval(refetchHistory, 60000);
});

onUnmounted(() => {
    // Clean up timer when component is destroyed
    if (refreshInterval.value) {
        clearInterval(refreshInterval.value);
    }
});

// Function to get last 100 minutes timeline
function getLast100Minutes() {
    const now = new Date();
    return Array.from({ length: 100 }, (_, i) => {
        const d = new Date(now);
        d.setMinutes(now.getMinutes() - (99 - i));
        d.setSeconds(0, 0);
        return d;
    });
}

// Create the 100-minute timeline
const last100Minutes = ref(getLast100Minutes());

// Map history by minute for quick lookup
const historyMinuteMap = computed(() => {
    const map: Record<string, MonitorHistory> = {};
    props.histories.forEach((h) => {
        const key = new Date(h.created_at).toISOString().slice(0, 16); // YYYY-MM-DDTHH:MM
        map[key] = h;
    });
    return map;
});

// Get status for a specific minute
function getMinuteStatus(date: Date): MonitorHistory | null {
    const key = date.toISOString().slice(0, 16);
    return historyMinuteMap.value[key] || null;
}

const latestHistory = computed(() => {
    // If monitor hasn't been checked yet, return empty array
    if (monitor.value.uptime_status === 'not yet checked') {
        return [];
    }

    // Get the last 100 minutes of history
    const oneHundredMinutesAgo = new Date(Date.now() - 100 * 60 * 1000);
    return props.histories
        .filter((h) => new Date(h.created_at) > oneHundredMinutesAgo)
        .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
        .slice(0, 100); // Limit to 100 entries
});

// Calculate response time stats for last 24 hours
const last24HoursHistories = computed(() => {
    const oneDayAgo = new Date(Date.now() - 24 * 60 * 60 * 1000);
    return props.histories.filter((h) => h.response_time && new Date(h.created_at) > oneDayAgo);
});

const avgResponseTime = computed(() => {
    // If monitor hasn't been checked yet, return 0
    if (monitor.value.uptime_status === 'not yet checked') {
        return 0;
    }

    // Use the responseTimeStats from the server if available, otherwise calculate from histories
    if (props.responseTimeStats?.average) {
        return Math.round(props.responseTimeStats.average);
    }

    const histories = last24HoursHistories.value;
    if (histories.length === 0) return 0;
    const sum = histories.reduce((acc, h) => acc + (h.response_time || 0), 0);
    return Math.round(sum / histories.length);
});

const minResponseTime = computed(() => {
    // If monitor hasn't been checked yet, return 0
    if (monitor.value.uptime_status === 'not yet checked') {
        return 0;
    }

    if (props.responseTimeStats?.min) {
        return Math.round(props.responseTimeStats.min);
    }

    const histories = last24HoursHistories.value;
    if (histories.length === 0) return 0;
    return Math.min(...histories.map((h) => h.response_time || 0));
});

const maxResponseTime = computed(() => {
    // If monitor hasn't been checked yet, return 0
    if (monitor.value.uptime_status === 'not yet checked') {
        return 0;
    }

    if (props.responseTimeStats?.max) {
        return Math.round(props.responseTimeStats.max);
    }

    const histories = last24HoursHistories.value;
    if (histories.length === 0) return 0;
    return Math.max(...histories.map((h) => h.response_time || 0));
});

const getStatusIcon = (status: string): string => {
    switch (status) {
        case 'up':
            return 'checkCircle';
        case 'down':
            return 'xCircle';
        case 'not yet checked':
            return 'clock';
        default:
            return 'alertCircle';
    }
};

const getStatusText = (status: string): string => {
    switch (status) {
        case 'up':
            return 'Operational';
        case 'down':
            return 'Down';
        case 'not yet checked':
            return 'Not Yet Checked';
        default:
            return 'Degraded';
    }
};

const getCertificateIcon = (status: string | null): string => {
    switch (status) {
        case 'valid':
            return 'shieldCheck';
        case 'invalid':
            return 'shieldAlert';
        case 'not yet checked':
            return 'clock';
        case 'not applicable':
            return 'minus-circle';
        default:
            return 'clock';
    }
};

const getCertificateColor = (status: string | null): string => {
    switch (status) {
        case 'valid':
            return 'text-green-600';
        case 'invalid':
            return 'text-red-600';
        case 'not yet checked':
            return 'text-gray-600';
        case 'not applicable':
            return 'text-gray-400';
        default:
            return 'text-gray-600';
    }
};

const getCertificateText = (status: string | null): string => {
    switch (status) {
        case 'valid':
            return 'Valid';
        case 'invalid':
            return 'Invalid';
        case 'not yet checked':
            return 'Not Yet Checked';
        case 'not applicable':
            return 'Not Applicable';
        default:
            return 'Not Yet Checked';
    }
};

const getUptimeColor = (percentage: number): string => {
    if (percentage >= 99.5) return 'text-green-600 dark:text-green-400';
    if (percentage >= 95) return 'text-yellow-600 dark:text-yellow-400';
    return 'text-red-600 dark:text-red-400';
};

const getPeriodLabel = (period: string): string => {
    const labels: Record<string, string> = {
        '24h': 'Last 24 Hours',
        '7d': 'Last 7 Days',
        '30d': 'Last 30 Days',
        '90d': 'Last 90 Days',
    };
    return labels[period] || period;
};

const formatDate = (date: string): string => {
    return new Date(date).toLocaleString();
};

const getDateRange = (): string => {
    const date = new Date();
    date.setDate(date.getDate() - 89);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
};

const getUptimeDays = () => {
    const days = [];
    const today = new Date();

    for (let i = 89; i >= 0; i--) {
        const date = new Date(today);
        date.setDate(date.getDate() - i);
        const dateStr = date.toISOString().split('T')[0];

        const dayData = monitor.value.uptimes_daily?.find((d) => d.date === dateStr);

        days.push({
            date: dateStr,
            uptime: dayData?.uptime_percentage || 0,
        });
    }

    return days;
};
</script>
