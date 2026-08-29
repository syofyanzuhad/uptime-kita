<template>
    <PublicLayout
        :title="pageTitle"
        :description="pageDescription"
        :og-image="`${appUrl || ''}/og/monitor/${encodeURIComponent(monitor?.host || '')}.png`"
        :canonical-url="`${appUrl || ''}/m/${encodeURIComponent(monitor?.host || '')}`"
        :share-url="`${appUrl || ''}/m/${encodeURIComponent(monitor?.host || '')}`"
        :share-text="`${monitor?.host || ''} uptime: ${uptimeStats['24h']}% (${getStatusText(monitor?.uptime_status)})`"
        :show-server-stats="true"
    >
        <template #header-left>
            <div class="flex min-w-0 items-center gap-3">
                <TooltipProvider>
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Link
                                href="/"
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-gray-200/80 bg-white p-2 text-gray-600 shadow-sm transition-all hover:bg-gray-100 hover:text-gray-900 active:scale-95 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white"
                            >
                                <Icon name="arrowLeft" class="h-4 w-4" />
                            </Link>
                        </TooltipTrigger>
                        <TooltipContent>Back to all monitors</TooltipContent>
                    </Tooltip>
                </TooltipProvider>

                <div class="flex min-w-0 items-center gap-2.5">
                    <img
                        v-if="monitor.favicon && !faviconFailed"
                        :src="monitor.favicon"
                        :alt="`${monitor.host} favicon`"
                        class="h-7 w-7 shrink-0 rounded-lg object-contain drop-shadow-sm"
                        @error="faviconFailed = true"
                    />
                    <div
                        v-else
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-blue-50 to-indigo-100 font-mono text-xs font-bold text-blue-700 dark:from-blue-950 dark:to-indigo-900/50 dark:text-blue-300"
                    >
                        {{ (monitor?.host || '').slice(0, 2).toUpperCase() }}
                    </div>

                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h1 class="truncate text-base font-extrabold text-gray-900 sm:text-xl dark:text-white">
                                {{ monitor.host }}
                            </h1>
                        </div>
                        <a
                            :href="monitor.url"
                            target="_blank"
                            rel="noopener"
                            class="flex items-center gap-1 truncate text-xs text-blue-600 hover:underline dark:text-blue-400"
                        >
                            <span>{{ monitor.url }}</span>
                            <Icon name="externalLink" class="h-3 w-3" />
                        </a>
                    </div>
                </div>
            </div>
        </template>

        <template #header-actions>
            <!-- Live Status Pill in Header -->
            <span
                role="status"
                :aria-label="getStatusText(monitor.uptime_status)"
                :class="[
                    'inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold shadow-sm ring-1 transition-colors',
                    monitor.uptime_status === 'up'
                        ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/50 dark:text-emerald-300 dark:ring-emerald-500/30'
                        : monitor.uptime_status === 'down'
                          ? 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-950/50 dark:text-rose-300 dark:ring-rose-500/30'
                          : 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-950/50 dark:text-amber-300 dark:ring-amber-500/30',
                ]"
            >
                <span
                    class="h-2 w-2 rounded-full"
                    :class="[
                        monitor.uptime_status === 'up'
                            ? 'animate-pulse bg-emerald-500'
                            : monitor.uptime_status === 'down'
                              ? 'animate-ping bg-rose-500'
                              : 'bg-amber-500',
                    ]"
                />
                <span>{{ getStatusText(monitor.uptime_status) }}</span>
            </span>
        </template>

        <TooltipProvider>
            <!-- Hero Status Banner -->
            <div
                class="mb-6 overflow-hidden rounded-3xl border p-6 shadow-sm backdrop-blur-md transition-all duration-300 sm:p-8"
                :class="[
                    monitor.uptime_status === 'up'
                        ? 'border-emerald-200/80 bg-gradient-to-r from-emerald-50/70 via-teal-50/40 to-white dark:border-emerald-900/50 dark:from-emerald-950/30 dark:via-gray-900 dark:to-gray-900'
                        : monitor.uptime_status === 'down'
                          ? 'border-rose-200/80 bg-gradient-to-r from-rose-50/70 via-orange-50/40 to-white dark:border-rose-900/50 dark:from-rose-950/30 dark:via-gray-900 dark:to-gray-900'
                          : 'border-amber-200/80 bg-gradient-to-r from-amber-50/70 via-yellow-50/40 to-white dark:border-amber-900/50 dark:from-amber-950/30 dark:via-gray-900 dark:to-gray-900',
                ]"
            >
                <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div
                            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl shadow-md"
                            :class="[
                                monitor.uptime_status === 'up'
                                    ? 'bg-emerald-500 text-white shadow-emerald-500/20'
                                    : monitor.uptime_status === 'down'
                                      ? 'bg-rose-500 text-white shadow-rose-500/20'
                                      : 'bg-amber-500 text-white shadow-amber-500/20',
                            ]"
                        >
                            <Icon :name="getStatusIcon(monitor.uptime_status)" class="h-8 w-8" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-2xl font-black tracking-tight text-gray-900 sm:text-3xl dark:text-white">
                                    {{ getStatusText(monitor.uptime_status) }}
                                </h2>
                            </div>
                            <p class="mt-1 text-xs text-gray-600 sm:text-sm dark:text-gray-300">
                                Checked every {{ monitor.uptime_check_interval }} minutes
                                <span v-if="monitor.last_check_date">• Last checked {{ formatDate(monitor.last_check_date) }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Quick Metrics Chips -->
                    <div class="flex flex-wrap items-center gap-2.5 sm:justify-end">
                        <div
                            class="rounded-2xl border border-gray-200/80 bg-white/90 px-4 py-2.5 text-center shadow-sm backdrop-blur-sm dark:border-gray-800 dark:bg-gray-800/90"
                        >
                            <span class="block text-lg font-extrabold text-gray-900 dark:text-white" :class="getUptimeColor(uptimeStats['24h'])">
                                {{ uptimeStats['24h'] }}%
                            </span>
                            <span class="text-[10px] font-semibold tracking-wider text-gray-400 uppercase">24h Uptime</span>
                        </div>

                        <div
                            class="rounded-2xl border border-gray-200/80 bg-white/90 px-4 py-2.5 text-center shadow-sm backdrop-blur-sm dark:border-gray-800 dark:bg-gray-800/90"
                        >
                            <span class="block text-lg font-extrabold text-gray-900 dark:text-white"> {{ avgResponseTime }}ms </span>
                            <span class="text-[10px] font-semibold tracking-wider text-gray-400 uppercase">Avg Latency</span>
                        </div>

                        <div
                            v-if="monitor.certificate_check_enabled && monitor.certificate_status"
                            class="rounded-2xl border border-gray-200/80 bg-white/90 px-4 py-2.5 text-center shadow-sm backdrop-blur-sm dark:border-gray-800 dark:bg-gray-800/90"
                        >
                            <span
                                class="flex items-center justify-center gap-1 text-base font-extrabold"
                                :class="getCertificateColor(monitor.certificate_status)"
                            >
                                <Icon :name="getCertificateIcon(monitor.certificate_status)" class="h-4 w-4" />
                                <span>{{ getCertificateText(monitor.certificate_status) }}</span>
                            </span>
                            <span class="text-[10px] font-semibold tracking-wider text-gray-400 uppercase">SSL Certificate</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Latest 100 Minutes Timeline Card -->
            <div
                class="mb-8 rounded-3xl border border-gray-200/80 bg-white/80 p-5 shadow-sm backdrop-blur-sm sm:p-6 dark:border-gray-800/80 dark:bg-gray-900/80"
            >
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-2">
                        <div
                            class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400"
                        >
                            <Icon name="activity" class="h-4 w-4" />
                        </div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Real-Time Inspection History (Last 100 Minutes)</h3>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-1 rounded-xl bg-gray-100/80 p-1 dark:bg-gray-800/80">
                            <button
                                @click="setChartViewMode('bar')"
                                :class="[
                                    'flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-semibold transition-all',
                                    chartViewMode === 'bar'
                                        ? 'bg-white text-blue-600 shadow-sm dark:bg-gray-700 dark:text-blue-400'
                                        : 'text-gray-500 hover:text-gray-900 dark:text-gray-400',
                                ]"
                            >
                                <Icon name="chartBar" class="h-3.5 w-3.5" />
                                <span>Status Bars</span>
                            </button>
                            <button
                                @click="setChartViewMode('line')"
                                :class="[
                                    'flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-semibold transition-all',
                                    chartViewMode === 'line'
                                        ? 'bg-white text-blue-600 shadow-sm dark:bg-gray-700 dark:text-blue-400'
                                        : 'text-gray-500 hover:text-gray-900 dark:text-gray-400',
                                ]"
                            >
                                <Icon name="chartLine" class="h-3.5 w-3.5" />
                                <span>Response Time</span>
                            </button>
                        </div>

                        <div class="flex items-center gap-1.5 text-xs text-gray-400">
                            <Icon :name="isRefreshing ? 'loader' : 'refreshCw'" class="h-3.5 w-3.5" :class="isRefreshing ? 'animate-spin' : ''" />
                            <span class="hidden sm:inline">{{ isRefreshing ? 'Refreshing…' : 'Live' }}</span>
                        </div>
                    </div>
                </div>

                <div v-if="monitor.uptime_status === 'not yet checked'" class="py-12 text-center">
                    <Icon name="clock" class="mx-auto mb-2 h-8 w-8 text-gray-400" />
                    <p class="text-sm font-medium text-gray-500">No inspection records logged yet.</p>
                </div>

                <!-- Bar View -->
                <div v-else-if="chartViewMode === 'bar'">
                    <div class="flex items-center gap-1 overflow-x-auto py-2">
                        <Tooltip v-for="(date, i) in last100Minutes" :key="i">
                            <TooltipTrigger as-child>
                                <div
                                    class="h-10 w-2 flex-shrink-0 cursor-pointer rounded-full transition-all hover:scale-125 sm:w-2.5"
                                    :class="[
                                        getMinuteStatus(date)?.uptime_status === 'up'
                                            ? 'bg-emerald-500 hover:bg-emerald-400'
                                            : getMinuteStatus(date)?.uptime_status === 'down'
                                              ? 'bg-rose-500 hover:bg-rose-400'
                                              : 'bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600',
                                    ]"
                                />
                            </TooltipTrigger>
                            <TooltipContent side="top" class="text-xs">
                                <div class="space-y-1">
                                    <p class="font-bold">{{ date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}</p>
                                    <div v-if="getMinuteStatus(date)">
                                        <p>{{ getStatusText(getMinuteStatus(date)!.uptime_status) }}</p>
                                        <p v-if="getMinuteStatus(date)!.response_time" class="font-mono text-blue-400">
                                            ⚡ {{ getMinuteStatus(date)!.response_time }}ms
                                        </p>
                                    </div>
                                    <p v-else class="text-gray-400">No check executed</p>
                                </div>
                            </TooltipContent>
                        </Tooltip>
                    </div>

                    <div class="mt-3 flex items-center justify-between text-xs text-gray-400">
                        <span>{{ last100Minutes[0].toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }} (100m ago)</span>
                        <div class="flex items-center gap-4">
                            <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Operational</span>
                            <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-rose-500"></span>Downtime</span>
                            <span class="inline-flex items-center gap-1.5"
                                ><span class="h-2 w-2 rounded-full bg-gray-300 dark:bg-gray-700"></span>No check</span
                            >
                        </div>
                        <span>Just now</span>
                    </div>
                </div>

                <!-- Line Graph View -->
                <div v-else>
                    <ResponseTimeLineChart :histories="histories" :last100Minutes="last100Minutes" />
                </div>
            </div>

            <!-- Two Column Analytics Grid -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Left Column (2 Cols) -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Uptime Historical Percentages Card -->
                    <Card
                        class="rounded-3xl border border-gray-200/80 bg-white/80 shadow-sm backdrop-blur-sm dark:border-gray-800/80 dark:bg-gray-900/80"
                    >
                        <CardHeader class="pb-2">
                            <CardTitle class="text-base font-bold text-gray-900 dark:text-white">Uptime Availability Rates</CardTitle>
                        </CardHeader>
                        <CardContent class="p-6 pt-2">
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
                                <div
                                    v-for="(value, period) in uptimeStats"
                                    :key="period"
                                    class="rounded-2xl border border-gray-100 bg-gray-50/50 p-4 text-center dark:border-gray-800 dark:bg-gray-800/40"
                                >
                                    <div class="text-2xl font-black sm:text-3xl" :class="getUptimeColor(value)">{{ value }}%</div>
                                    <div class="mt-1 text-xs font-semibold text-gray-500 dark:text-gray-400">
                                        {{ getPeriodLabel(period) }}
                                    </div>
                                    <span
                                        class="mt-1.5 inline-block rounded-full px-2 py-0.5 text-[10px] font-bold"
                                        :class="
                                            value >= 99.5
                                                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                                : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300'
                                        "
                                    >
                                        {{ value >= 99.5 ? 'Optimal' : value >= 95 ? 'Good' : 'Degraded' }}
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Response Time Card -->
                    <Card
                        class="rounded-3xl border border-gray-200/80 bg-white/80 shadow-sm backdrop-blur-sm dark:border-gray-800/80 dark:bg-gray-900/80"
                    >
                        <CardHeader class="pb-2">
                            <CardTitle class="text-base font-bold text-gray-900 dark:text-white">Response Latency (Last 24 Hours)</CardTitle>
                        </CardHeader>
                        <CardContent class="p-6 pt-2">
                            <div class="grid grid-cols-3 gap-3 text-center sm:gap-4">
                                <div class="rounded-2xl border border-gray-100 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-gray-800/40">
                                    <div class="font-mono text-2xl font-black text-blue-600 sm:text-3xl dark:text-blue-400">
                                        {{ avgResponseTime }}ms
                                    </div>
                                    <div class="mt-1 text-xs font-semibold text-gray-500">Average Latency</div>
                                </div>

                                <div class="rounded-2xl border border-gray-100 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-gray-800/40">
                                    <div class="font-mono text-2xl font-black text-emerald-600 sm:text-3xl dark:text-emerald-400">
                                        {{ minResponseTime }}ms
                                    </div>
                                    <div class="mt-1 text-xs font-semibold text-gray-500">Fastest (Min)</div>
                                </div>

                                <div class="rounded-2xl border border-gray-100 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-gray-800/40">
                                    <div class="font-mono text-2xl font-black text-purple-600 sm:text-3xl dark:text-purple-400">
                                        {{ maxResponseTime }}ms
                                    </div>
                                    <div class="mt-1 text-xs font-semibold text-gray-500">Slowest (Max)</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- 90 Days Heatmap Card -->
                    <Card
                        class="rounded-3xl border border-gray-200/80 bg-white/80 shadow-sm backdrop-blur-sm dark:border-gray-800/80 dark:bg-gray-900/80"
                    >
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-base font-bold text-gray-900 dark:text-white">90-Day Uptime Heatmap</CardTitle>
                            <div class="flex items-center gap-1 rounded-xl bg-gray-100/80 p-1 dark:bg-gray-800/80">
                                <button
                                    @click="setUptimeChartViewMode('bar')"
                                    :class="[
                                        'rounded-lg px-2 py-1 text-xs font-semibold transition-all',
                                        uptimeChartViewMode === 'bar'
                                            ? 'bg-white text-blue-600 shadow-sm dark:bg-gray-700 dark:text-blue-400'
                                            : 'text-gray-500',
                                    ]"
                                >
                                    Bars
                                </button>
                                <button
                                    @click="setUptimeChartViewMode('line')"
                                    :class="[
                                        'rounded-lg px-2 py-1 text-xs font-semibold transition-all',
                                        uptimeChartViewMode === 'line'
                                            ? 'bg-white text-blue-600 shadow-sm dark:bg-gray-700 dark:text-blue-400'
                                            : 'text-gray-500',
                                    ]"
                                >
                                    Trend
                                </button>
                            </div>
                        </CardHeader>
                        <CardContent class="p-6 pt-2">
                            <div v-if="uptimeChartViewMode === 'bar'" class="space-y-3">
                                <div class="flex items-center justify-between text-xs font-medium text-gray-400">
                                    <span>{{ getDateRange() }}</span>
                                    <span>Today</span>
                                </div>
                                <div class="flex items-center gap-0.5 overflow-x-auto py-2">
                                    <Tooltip v-for="day in getUptimeDays()" :key="day.date">
                                        <TooltipTrigger as-child>
                                            <div
                                                class="h-12 w-2 flex-shrink-0 cursor-pointer rounded-full transition-all hover:scale-125 sm:w-2.5"
                                                :class="[
                                                    day.uptime === 100
                                                        ? 'bg-emerald-500'
                                                        : day.uptime >= 99.5
                                                          ? 'bg-emerald-400'
                                                          : day.uptime >= 95
                                                            ? 'bg-amber-400'
                                                            : day.uptime > 0
                                                              ? 'bg-rose-500'
                                                              : 'bg-gray-200 dark:bg-gray-700',
                                                ]"
                                            />
                                        </TooltipTrigger>
                                        <TooltipContent side="top" class="text-xs">
                                            <p class="font-bold">{{ day.date }}</p>
                                            <p>{{ day.uptime }}% uptime</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </div>
                            </div>
                            <div v-else>
                                <UptimeLineChart :uptimesDaily="monitor.uptimes_daily || []" />
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Latest Incidents Card -->
                    <Card
                        class="rounded-3xl border border-gray-200/80 bg-white/80 shadow-sm backdrop-blur-sm dark:border-gray-800/80 dark:bg-gray-900/80"
                    >
                        <CardHeader class="pb-2">
                            <CardTitle class="text-base font-bold text-gray-900 dark:text-white">Recent Incidents</CardTitle>
                        </CardHeader>
                        <CardContent class="p-6 pt-2">
                            <div v-if="latestIncidents.length > 0" class="space-y-3">
                                <div
                                    v-for="incident in latestIncidents"
                                    :key="incident.id"
                                    class="rounded-2xl border border-gray-100 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-gray-800/40"
                                >
                                    <div class="flex items-center justify-between">
                                        <span
                                            class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-bold"
                                            :class="
                                                incident.type === 'down'
                                                    ? 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300'
                                                    : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300'
                                            "
                                        >
                                            {{ incident.type === 'down' ? 'Outage' : 'Degraded' }}
                                        </span>
                                        <span class="font-mono text-xs text-gray-400">{{ formatDuration(incident.duration_minutes || 0) }}</span>
                                    </div>
                                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                        Started {{ formatDate(incident.started_at) }}
                                        <span v-if="incident.ended_at"> → Resolved {{ formatDate(incident.ended_at) }}</span>
                                    </p>
                                    <p v-if="incident.reason" class="mt-1 text-xs font-medium text-gray-700 dark:text-gray-300">
                                        {{ incident.reason }}
                                    </p>
                                </div>
                            </div>
                            <div v-else class="py-8 text-center">
                                <Icon name="checkCircle" class="mx-auto mb-2 h-10 w-10 text-emerald-500" />
                                <p class="text-sm font-bold text-gray-900 dark:text-white">No incidents reported</p>
                                <p class="text-xs text-gray-400">This service has been running continuously without outages.</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Right Column (1 Col) -->
                <div class="space-y-6">
                    <!-- Security & Domain Card -->
                    <Card
                        class="rounded-3xl border border-gray-200/80 bg-white/80 shadow-sm backdrop-blur-sm dark:border-gray-800/80 dark:bg-gray-900/80"
                    >
                        <CardHeader class="pb-2">
                            <CardTitle class="text-base font-bold text-gray-900 dark:text-white">Domain & Security</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4 p-6 pt-2">
                            <!-- SSL Info -->
                            <div
                                v-if="monitor.certificate_check_enabled"
                                class="rounded-2xl border border-gray-100 bg-gray-50/50 p-3.5 dark:border-gray-800 dark:bg-gray-800/40"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-semibold text-gray-500">SSL Certificate</span>
                                    <span class="flex items-center gap-1 text-xs font-bold" :class="getCertificateColor(monitor.certificate_status)">
                                        <Icon :name="getCertificateIcon(monitor.certificate_status)" class="h-3.5 w-3.5" />
                                        {{ getCertificateText(monitor.certificate_status) }}
                                    </span>
                                </div>
                                <div v-if="monitor.certificate_expiration_date" class="mt-1.5 text-xs text-gray-500">
                                    Expires: {{ formatDate(monitor.certificate_expiration_date) }}
                                </div>
                            </div>

                            <!-- Domain WHOIS Expiration -->
                            <div
                                v-if="monitor.domain_expiration_check_enabled"
                                class="rounded-2xl border border-gray-100 bg-gray-50/50 p-3.5 dark:border-gray-800 dark:bg-gray-800/40"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-semibold text-gray-500">Domain Expiration</span>
                                    <span
                                        v-if="monitor.domain_expiration_date"
                                        class="text-xs font-bold"
                                        :class="getDomainExpirationColor(monitor.domain_expiration_date)"
                                    >
                                        {{ getDomainDaysLeft(monitor.domain_expiration_date) }}
                                    </span>
                                    <span v-else-if="monitor.domain_expiration_lookup_error" class="text-xs font-semibold text-rose-500">
                                        Lookup error
                                    </span>
                                    <span v-else class="text-xs font-medium text-gray-400"> Checking... </span>
                                </div>
                                <div v-if="monitor.domain_expiration_date" class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                                    Expires: {{ formatDate(monitor.domain_expiration_date) }}
                                </div>
                            </div>

                            <!-- Check Frequency -->
                            <div class="rounded-2xl border border-gray-100 bg-gray-50/50 p-3.5 dark:border-gray-800 dark:bg-gray-800/40">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-semibold text-gray-500">Check Interval</span>
                                    <span class="text-xs font-bold text-gray-900 dark:text-white">Every {{ monitor.uptime_check_interval }} min</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Embed Badge Card -->
                    <Card
                        class="rounded-3xl border border-gray-200/80 bg-white/80 shadow-sm backdrop-blur-sm dark:border-gray-800/80 dark:bg-gray-900/80"
                    >
                        <CardHeader class="pb-2">
                            <CardTitle class="flex items-center gap-2 text-base font-bold text-gray-900 dark:text-white">
                                <Icon name="code" class="h-4 w-4 text-blue-500" />
                                <span>Embed Status Badge</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4 p-6 pt-2">
                            <!-- Live Preview -->
                            <div
                                class="flex flex-col items-center justify-center rounded-2xl border border-gray-100 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-800/60"
                            >
                                <span class="mb-2 text-[10px] font-bold text-gray-400 uppercase">Live Preview</span>
                                <img :src="badgeUrl" :alt="`${monitor.host} uptime badge`" class="h-6 transition-all" />
                            </div>

                            <!-- Customization Controls -->
                            <div class="space-y-2.5">
                                <div>
                                    <label class="mb-1 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-gray-400"
                                        >Time Range</label
                                    >
                                    <div class="grid grid-cols-4 gap-1 rounded-xl bg-gray-100 p-1 dark:bg-gray-800">
                                        <button
                                            v-for="p in ['24h', '7d', '30d', '90d'] as const"
                                            :key="p"
                                            type="button"
                                            @click="badgePeriod = p"
                                            class="rounded-lg py-1 text-xs font-bold transition-all"
                                            :class="
                                                badgePeriod === p
                                                    ? 'bg-white text-blue-600 shadow-sm dark:bg-gray-700 dark:text-white'
                                                    : 'text-gray-500 hover:text-gray-900 dark:text-gray-400'
                                            "
                                        >
                                            {{ p }}
                                        </button>
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-1 block text-[11px] font-bold tracking-wider text-gray-500 uppercase dark:text-gray-400"
                                        >Badge Style</label
                                    >
                                    <div class="grid grid-cols-2 gap-1 rounded-xl bg-gray-100 p-1 sm:grid-cols-4 dark:bg-gray-800">
                                        <button
                                            v-for="s in [
                                                { key: 'flat', label: 'Flat' },
                                                { key: 'flat-square', label: 'Square' },
                                                { key: 'for-the-badge', label: 'Caps' },
                                                { key: 'plastic', label: 'Plastic' },
                                            ] as const"
                                            :key="s.key"
                                            type="button"
                                            @click="badgeStyle = s.key"
                                            class="rounded-lg py-1 text-xs font-bold transition-all"
                                            :class="
                                                badgeStyle === s.key
                                                    ? 'bg-white text-blue-600 shadow-sm dark:bg-gray-700 dark:text-white'
                                                    : 'text-gray-500 hover:text-gray-900 dark:text-gray-400'
                                            "
                                        >
                                            {{ s.label }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-3 pt-1">
                                <div>
                                    <label class="mb-1 block text-xs font-semibold text-gray-500">Markdown (with Status Page Link)</label>
                                    <div class="relative">
                                        <code
                                            class="block overflow-x-auto rounded-xl bg-gray-100 p-2.5 font-mono text-xs break-all dark:bg-gray-800"
                                            >{{ badgeMarkdown }}</code
                                        >
                                        <button
                                            @click="copyToClipboard(badgeMarkdown, 'markdown')"
                                            class="absolute top-1.5 right-1.5 rounded-lg bg-white p-1 text-gray-600 shadow-sm hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300"
                                        >
                                            <Icon :name="copiedType === 'markdown' ? 'check' : 'copy'" class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-1 block text-xs font-semibold text-gray-500">HTML Embed</label>
                                    <div class="relative">
                                        <code
                                            class="block overflow-x-auto rounded-xl bg-gray-100 p-2.5 font-mono text-xs break-all dark:bg-gray-800"
                                            >{{ badgeHtml }}</code
                                        >
                                        <button
                                            @click="copyToClipboard(badgeHtml, 'html')"
                                            class="absolute top-1.5 right-1.5 rounded-lg bg-white p-1 text-gray-600 shadow-sm hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300"
                                        >
                                            <Icon :name="copiedType === 'html' ? 'check' : 'copy'" class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-1 block text-xs font-semibold text-gray-500">Direct SVG URL</label>
                                    <div class="relative">
                                        <code
                                            class="block overflow-x-auto rounded-xl bg-gray-100 p-2.5 font-mono text-xs break-all dark:bg-gray-800"
                                            >{{ badgeUrl }}</code
                                        >
                                        <button
                                            @click="copyToClipboard(badgeUrl, 'url')"
                                            class="absolute top-1.5 right-1.5 rounded-lg bg-white p-1 text-gray-600 shadow-sm hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300"
                                        >
                                            <Icon :name="copiedType === 'url' ? 'check' : 'copy'" class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </TooltipProvider>
    </PublicLayout>
</template>

<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import PublicLayout from '@/components/PublicLayout.vue';
import ResponseTimeLineChart from '@/components/ResponseTimeLineChart.vue';
import UptimeLineChart from '@/components/UptimeLineChart.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { useMonitorStatusStream } from '@/composables/useMonitorStatusStream';
import { globalToasts } from '@/composables/useToastNotifications';
import type { Monitor, MonitorHistory } from '@/types/monitor';
import { Link, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const faviconFailed = ref(false);

interface Props {
    monitor: { data: Monitor };
    histories: MonitorHistory[];
    uptimeStats: { '24h': number; '7d': number; '30d': number; '90d': number };
    responseTimeStats: { average: number; min: number; max: number };
    latestIncidents: any[];
    appUrl: string;
}
const props = defineProps<Props>();
const monitor = computed(() => props.monitor.data);
useMonitorStatusStream({ monitorIds: [props.monitor.data.id], enabled: true, onStatusChange: (change) => globalToasts.addStatusChangeToast(change) });
const appUrl = computed(() => props.appUrl || window.location.origin);
const pageTitle = computed(() => `${monitor.value.host} - ${props.uptimeStats['24h']}% Uptime | Uptime Kita`);
const pageDescription = computed(
    () =>
        `Real-time monitoring for ${monitor.value.host}. Status: ${monitor.value.uptime_status === 'up' ? 'Operational' : 'Down'}. 24h uptime: ${props.uptimeStats['24h']}%.`,
);

// Latest incidents from props (MonitorIncident model)
const latestIncidents = computed(() => props.latestIncidents || []);

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
const badgePeriod = ref<'24h' | '7d' | '30d' | '90d'>('24h');
const badgeStyle = ref<'flat' | 'flat-square' | 'for-the-badge' | 'plastic'>('flat');

const badgeUrl = computed(() => {
    const baseUrl = typeof window !== 'undefined' ? window.location.origin : props.appUrl || 'https://uptime.syofyanzuhad.dev';
    const params = new URLSearchParams();
    if (badgePeriod.value !== '24h') params.append('period', badgePeriod.value);
    if (badgeStyle.value !== 'flat') params.append('style', badgeStyle.value);
    const qs = params.toString();
    return `${baseUrl}/badge/${monitor.value.host}${qs ? `?${qs}` : ''}`;
});

const monitorPageUrl = computed(() => {
    const baseUrl = typeof window !== 'undefined' ? window.location.origin : props.appUrl || 'https://uptime.syofyanzuhad.dev';
    return `${baseUrl}/m/${monitor.value.host}`;
});

const badgeMarkdown = computed(() => {
    return `[![Uptime](${badgeUrl.value})](${monitorPageUrl.value})`;
});

const badgeHtml = computed(() => {
    return `<a href="${monitorPageUrl.value}" target="_blank"><img src="${badgeUrl.value}" alt="${monitor.value.name} Uptime" /></a>`;
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

// Chart view mode: 'bar' or 'line'
type ChartViewMode = 'bar' | 'line';
const chartViewMode = ref<ChartViewMode>('bar');
const uptimeChartViewMode = ref<ChartViewMode>('bar');

const setChartViewMode = (mode: ChartViewMode) => {
    chartViewMode.value = mode;
    localStorage.setItem('chartViewMode', mode);
};

const setUptimeChartViewMode = (mode: ChartViewMode) => {
    uptimeChartViewMode.value = mode;
    localStorage.setItem('uptimeChartViewMode', mode);
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

    // Load saved chart view mode preferences
    const savedChartViewMode = localStorage.getItem('chartViewMode') as ChartViewMode | null;
    if (savedChartViewMode && (savedChartViewMode === 'bar' || savedChartViewMode === 'line')) {
        chartViewMode.value = savedChartViewMode;
    }

    const savedUptimeChartViewMode = localStorage.getItem('uptimeChartViewMode') as ChartViewMode | null;
    if (savedUptimeChartViewMode && (savedUptimeChartViewMode === 'bar' || savedUptimeChartViewMode === 'line')) {
        uptimeChartViewMode.value = savedUptimeChartViewMode;
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

const getDomainExpirationColor = (date: string | null | undefined): string => {
    if (!date) return 'text-gray-600';
    const daysLeft = Math.ceil((new Date(date).getTime() - Date.now()) / 86400000);
    if (daysLeft < 0) return 'text-red-600 dark:text-red-400';
    if (daysLeft <= 30) return 'text-yellow-600 dark:text-yellow-400';
    return 'text-green-600 dark:text-green-400';
};

const getDomainDaysLeft = (date: string | null | undefined): string => {
    if (!date) return '';
    const daysLeft = Math.ceil((new Date(date).getTime() - Date.now()) / 86400000);
    if (daysLeft < 0) return 'Expired';
    if (daysLeft === 0) return 'Expires today';
    if (daysLeft === 1) return 'Expires tomorrow';
    return `${daysLeft} days left`;
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
