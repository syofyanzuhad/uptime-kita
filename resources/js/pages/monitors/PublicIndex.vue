<template>
    <Head title="Public Monitors - Uptime Kita" />

    <div class="min-h-full bg-gray-50 dark:bg-gray-900">
        <!-- Header -->
        <div class="fixed top-0 z-10 w-full bg-white shadow dark:bg-gray-800">
            <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
                <div class="flex justify-between space-y-4 sm:flex-row sm:items-center sm:space-y-0">
                    <div class="flex items-center space-x-3 sm:space-x-4">
                        <div class="flex h-8 w-8 items-center justify-center rounded bg-blue-100 sm:h-10 sm:w-10 dark:bg-blue-900/30">
                            <Link href="/">
                                <img src="/images/uptime-kita.jpg" alt="Uptime Kita" class="h-6 w-6 rounded object-cover sm:h-10 sm:w-10" />
                            </Link>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h1 class="text-lg font-bold text-gray-900 sm:text-xl lg:text-2xl dark:text-white">Public Monitors</h1>
                            <p class="text-xs text-gray-500 sm:text-sm dark:text-gray-400">Discover and monitor public websites</p>
                        </div>
                    </div>

                    <!-- Theme Toggle & Server Stats -->
                    <div class="flex items-center justify-center space-x-2 sm:justify-end">
                        <ServerStatsBadge />
                        <button
                            @click="toggleTheme"
                            class="cursor-pointer rounded-full bg-gray-100 p-2 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600"
                            :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
                        >
                            <Icon :name="isDark ? 'sun' : 'moon'" class="h-4 w-4 text-gray-600 dark:text-gray-300" />
                        </button>
                        <!-- dashboard button -->
                        <Link
                            href="/dashboard"
                            class="cursor-pointer rounded-full bg-gray-100 p-2 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600"
                            aria-label="Go to dashboard"
                        >
                            <Icon name="home" class="h-4 w-4 text-gray-600 dark:text-gray-300" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="mx-auto mt-24 max-w-7xl px-4 pt-4 sm:px-6 sm:py-6 lg:px-8 lg:pt-8">
            <!-- Stats Overview -->
            <div class="mb-8 grid grid-cols-3 gap-3 sm:gap-4 sm:grid-cols-3 lg:grid-cols-6">
                <Card>
                    <CardContent class="p-1 sm:p-4">
                        <div class="text-center">
                            <div class="text-xl font-bold text-gray-900 dark:text-white sm:text-2xl">{{ stats.total_public }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 sm:text-sm">Total Public</div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-1 sm:p-4">
                        <div class="text-center">
                            <div class="text-xl font-bold text-green-600 dark:text-green-400 sm:text-2xl">{{ stats.up }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 sm:text-sm">Operational</div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-1 sm:p-4">
                        <div class="text-center">
                            <div class="text-xl font-bold text-red-600 dark:text-red-400 sm:text-2xl">{{ stats.down }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 sm:text-sm">Down</div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-1 sm:p-4">
                        <div class="text-center">
                            <div class="text-xl font-bold text-blue-600 dark:text-blue-400 sm:text-2xl">
                                {{ Math.round((stats.up / stats.total_public) * 100) || 0 }}%
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 sm:text-sm">Uptime</div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-1 sm:p-4">
                        <div class="text-center">
                            <div class="text-xl font-bold text-purple-600 dark:text-purple-400 sm:text-2xl" :title="`${(stats.daily_checks || 0).toLocaleString('id-ID')} daily checks`">
                                {{ formatDailyChecks(stats.daily_checks || 0) }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 sm:text-sm">Last 24 H</div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="p-1 sm:p-4">
                        <div class="text-center">
                            <div class="text-xl font-bold text-indigo-600 dark:text-indigo-400 sm:text-2xl" :title="`${(stats.monthly_checks || 0).toLocaleString('id-ID')} monthly checks`">
                                {{ formatChecksCount(stats.monthly_checks || 0) }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 sm:text-sm">This Month</div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters -->
            <Card class="mb-6 p-2">
                <CardContent class="p-4">
                    <div class="flex flex-col gap-4 sm:flex-row">
                        <!-- Search -->
                        <div class="flex-1">
                            <label for="search-monitors" class="sr-only">Search monitors</label>
                            <input
                                id="search-monitors"
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search monitors..."
                                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 placeholder-gray-500 focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 sm:px-3 sm:py-2"
                                @input="debounceSearch"
                            />
                        </div>

                        <div class="grid grid-cols-3 gap-2">
                            <!-- Sort By -->
                            <div class="w-full sm:w-44">
                                <label for="sort-by" class="sr-only">Sort by</label>
                                <select
                                    id="sort-by"
                                    v-model="sortBy"
                                    @change="applyFilters"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:px-3 sm:py-2"
                                >
                                    <option v-for="option in sortOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>

                            <!-- Status Filter -->
                            <div class="w-full sm:w-36">
                                <label for="status-filter" class="sr-only">Filter by status</label>
                                <select
                                    id="status-filter"
                                    v-model="statusFilter"
                                    @change="applyFilters"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:px-3 sm:py-2"
                                >
                                    <option value="all">All Status</option>
                                    <option value="up">Operational</option>
                                    <option value="down">Down</option>
                                </select>
                            </div>

                            <!-- Tag Filter -->
                            <div class="w-full sm:w-36">
                                <label for="tag-filter" class="sr-only">Filter by tag</label>
                                <select
                                    id="tag-filter"
                                    v-model="tagFilter"
                                    @change="applyFilters"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-900 focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:px-3 sm:py-2"
                                >
                                    <option value="">All Tags</option>
                                    <option v-for="tag in props.availableTags" :key="tag.id" :value="tag.name.en">
                                        {{ tag.name.en }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Create Button -->
                        <div class="w-full sm:w-auto">
                            <button
                                @click="createMonitor"
                                class="flex w-full cursor-pointer items-center justify-center space-x-2 rounded-lg bg-blue-600 px-4 py-3 text-sm font-medium text-white transition-colors hover:bg-blue-700 active:scale-[0.98] sm:w-auto sm:px-4 sm:py-2"
                            >
                                <Icon name="plus" class="h-4 w-4" />
                                <span>Create Monitor</span>
                            </button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Monitors Grid -->
            <div v-if="monitorsData.length === 0" class="py-12 text-center">
                <Icon name="search" class="mx-auto mb-4 h-16 w-16 text-gray-400" />
                <h2 class="mb-2 text-lg font-medium text-gray-900 dark:text-white">No monitors found</h2>
                <p class="text-gray-500 dark:text-gray-400">Try adjusting your search or filters</p>
            </div>

            <div v-else class="grid grid-cols-2 gap-4 sm:grid-cols-2 md:grid-cols-2 md:gap-6 lg:grid-cols-3 xl:grid-cols-4">
                <Card
                    v-for="monitor in monitorsData"
                    :key="monitor.id"
                    class="cursor-pointer p-0 transition-all duration-200 hover:shadow-md active:scale-[0.98] md:hover:shadow-lg"
                    @click="viewMonitor(monitor)"
                >
                    <!-- Mobile Compact View -->
                    <CardContent class="p-4 md:hidden">
                        <!-- Header with Favicon and Status -->
                        <div class="mb-3 flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <img
                                    v-if="monitor.favicon"
                                    :src="monitor.favicon"
                                    :alt="`${monitor.name} favicon`"
                                    class="h-5 w-5 flex-shrink-0 rounded drop-shadow-sm dark:drop-shadow-white/30"
                                    @error="(e) => ((e.target as HTMLImageElement).style.display = 'none')"
                                />
                                <div v-else class="flex h-5 w-5 flex-shrink-0 items-center justify-center rounded bg-gray-200 dark:bg-gray-700">
                                    <Icon name="globe" class="h-3 w-3 text-gray-500 dark:text-gray-400" />
                                </div>
                            </div>

                            <!-- Status Indicator -->
                            <span
                                :class="[
                                    'inline-flex items-center justify-center rounded-full p-1.5',
                                    monitor.uptime_status === 'up' ? 'bg-green-500' : monitor.uptime_status === 'down' ? 'bg-red-500' : 'bg-gray-400',
                                ]"
                                :title="getStatusText(monitor.uptime_status)"
                            >
                                <Icon :name="getStatusIcon(monitor.uptime_status)" class="h-3.5 w-3.5 text-white" />
                            </span>
                        </div>

                        <!-- Monitor Name -->
                        <MonitorLink
                            :monitor="monitor"
                            :show-favicon="false"
                            class-name="mb-2"
                            link-class-name="text-base font-semibold text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 line-clamp-2 leading-tight"
                        />

                        <!-- URL -->
                        <p class="mb-3 truncate text-sm text-gray-500 dark:text-gray-400">
                            {{ monitor.url }}
                        </p>

                        <!-- Status and Uptime Row -->
                        <div class="mb-3 flex items-center justify-between">
                            <!-- Status Badge -->
                            <span
                                :class="[
                                    'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium',
                                    monitor.uptime_status === 'up'
                                        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
                                        : monitor.uptime_status === 'down'
                                          ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'
                                          : 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300',
                                ]"
                            >
                                <Icon :name="getStatusIcon(monitor.uptime_status)" class="mr-1.5 inline h-3 w-3" />
                                {{ getStatusText(monitor.uptime_status) }}
                            </span>

                            <div class="flex items-center gap-2">
                                <!-- Page Views -->
                                <span v-if="monitor.page_views_count > 0" class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400" :title="`${monitor.page_views_count.toLocaleString()} views`">
                                    <Icon name="eye" class="h-3 w-3" />
                                    {{ monitor.formatted_page_views }}
                                </span>
                                <!-- Uptime Percentage -->
                                <span v-if="monitor.today_uptime_percentage" class="text-sm font-medium text-gray-600 dark:text-gray-300">
                                    {{ monitor.today_uptime_percentage }}%
                                </span>
                            </div>
                        </div>

                        <!-- Last Check -->
                        <div v-if="monitor.last_check_date_human" class="mb-3 text-sm text-gray-500 dark:text-gray-400">
                            <Icon name="clock" class="mr-1.5 inline h-3.5 w-3.5" />
                            {{ monitor.last_check_date_human }}
                        </div>

                        <!-- Tags (Compact) -->
                        <div v-if="monitor.tags && monitor.tags.length > 0" class="flex flex-wrap gap-2">
                            <span
                                v-for="tag in monitor.tags.slice(0, 3)"
                                :key="tag.id || tag.name"
                                class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300"
                            >
                                {{ getTagDisplayName(tag) }}
                            </span>
                            <span v-if="monitor.tags.length > 3" class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                +{{ monitor.tags.length - 3 }}
                            </span>
                        </div>
                    </CardContent>

                    <!-- Desktop Full View -->
                    <CardContent class="hidden p-4 md:block">
                        <div class="flex items-start space-x-4">
                            <!-- Favicon -->
                            <img
                                v-if="monitor.favicon"
                                :src="monitor.favicon"
                                :alt="`${monitor.name} favicon`"
                                class="h-6 w-6 flex-shrink-0 rounded drop-shadow-md dark:drop-shadow-white/30"
                                @error="(e) => ((e.target as HTMLImageElement).style.display = 'none')"
                            />
                            <div v-else class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded bg-gray-200 dark:bg-gray-700">
                                <Icon name="globe" class="h-4 w-4 text-gray-500 dark:text-gray-400" />
                            </div>

                            <!-- Monitor Info -->
                            <div class="min-w-0 flex-1">
                                <MonitorLink
                                    :monitor="monitor"
                                    :show-favicon="false"
                                    class-name="mb-1"
                                    link-class-name="text-lg font-semibold text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 truncate"
                                />
                                <p class="truncate text-sm text-gray-500 dark:text-gray-400">
                                    {{ monitor.url }}
                                </p>

                                <!-- Status Badge -->
                                <div class="mt-3 flex items-center space-x-2">
                                    <span
                                        :class="[
                                            'rounded-full px-2 py-1 text-xs font-medium',
                                            monitor.uptime_status === 'up'
                                                ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                                                : monitor.uptime_status === 'down'
                                                  ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
                                                  : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300',
                                        ]"
                                    >
                                        <Icon :name="getStatusIcon(monitor.uptime_status)" class="mr-1 inline h-3 w-3" />
                                        {{ getStatusText(monitor.uptime_status) }}
                                    </span>

                                    <span v-if="monitor.today_uptime_percentage" class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ monitor.today_uptime_percentage }}% uptime
                                    </span>

                                    <span v-if="monitor.page_views_count > 0" class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400" :title="`${monitor.page_views_count.toLocaleString()} views`">
                                        <Icon name="eye" class="h-3 w-3" />
                                        {{ monitor.formatted_page_views }} views
                                    </span>
                                </div>

                                <!-- Last Check -->
                                <div v-if="monitor.last_check_date_human" class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    Last checked {{ monitor.last_check_date_human }}
                                </div>

                                <!-- Tags -->
                                <div v-if="monitor.tags && monitor.tags.length > 0" class="mt-2 flex flex-wrap gap-1">
                                    <span
                                        v-for="tag in monitor.tags"
                                        :key="tag.id || tag.name"
                                        class="inline-flex items-center rounded bg-blue-100 px-2 py-0.5 text-xs text-blue-700 dark:bg-blue-900/30 dark:text-blue-300"
                                    >
                                        {{ getTagDisplayName(tag) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Load More Button -->
            <div v-if="currentPage < monitorsMeta.last_page" class="mt-8 text-center">
                <button
                    @click="loadMore"
                    :disabled="isLoading"
                    class="inline-flex w-full cursor-pointer items-center justify-center rounded-lg bg-gray-600 px-6 py-4 text-sm font-medium text-white transition-all duration-200 hover:bg-gray-700 active:scale-[0.98] disabled:bg-gray-400 sm:w-auto sm:py-3"
                >
                    <Icon v-if="isLoading" name="loader" class="mr-2 h-4 w-4 animate-spin" />
                    <span v-else>Load More Monitors</span>
                </button>
            </div>

            <!-- Latest Incidents Section -->
            <div v-if="props.latestIncidents && props.latestIncidents.length > 0" class="mt-8">
                <Card class="max-h-[50vh] overflow-y-auto">
                    <CardContent class="px-4 sm:px-6">
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Latest Incidents</h2>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Last 10 incidents</span>
                        </div>
                        <div class="space-y-3">
                            <div
                                v-for="incident in props.latestIncidents"
                                :key="incident.id"
                                class="flex items-start space-x-3 rounded-lg border border-gray-200 p-3 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800/50"
                            >
                                <div class="flex-shrink-0">
                                    <div
                                        :class="[
                                            'flex h-8 w-8 items-center justify-center rounded-full',
                                            incident.ended_at
                                                ? 'bg-green-100 dark:bg-green-900/30'
                                                : 'bg-red-100 dark:bg-red-900/30',
                                        ]"
                                    >
                                        <Icon
                                            :name="incident.ended_at ? 'checkCircle' : 'alertCircle'"
                                            :class="[
                                                'h-4 w-4',
                                                incident.ended_at
                                                    ? 'text-green-600 dark:text-green-400'
                                                    : 'text-red-600 dark:text-red-400',
                                            ].join(' ')"
                                        />
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ incident.monitor.raw_url }}
                                            </p>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                <span v-if="incident.type">Type: {{ incident.type }} • </span>
                                                <span v-if="incident.status_code">Status: {{ incident.status_code }} • </span>
                                                Started {{ formatRelativeTime(incident.started_at) }}
                                            </p>
                                            <p v-if="incident.reason" class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                                                {{ incident.reason }}
                                            </p>
                                        </div>
                                        <div class="ml-4 flex-shrink-0">
                                            <span
                                                v-if="incident.ended_at"
                                                class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-300"
                                            >
                                                Resolved
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex items-center rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-800 dark:bg-red-900/30 dark:text-red-300"
                                            >
                                                Ongoing
                                            </span>
                                        </div>
                                    </div>
                                    <p v-if="incident.duration_minutes" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Duration: {{ formatDuration(incident.duration_minutes) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Back to Top Button -->
        <button
            v-show="showBackToTop"
            @click="scrollToTop"
            class="fixed right-6 bottom-6 z-50 transform cursor-pointer rounded-full bg-blue-600 p-3 text-white shadow-lg transition-all duration-300 hover:scale-110 hover:bg-blue-700 hover:shadow-xl dark:bg-blue-500 dark:hover:bg-blue-600"
            aria-label="Back to top"
            title="Back to top"
        >
            <Icon name="chevronUp" class="h-5 w-5" />
        </button>

        <!-- Footer -->
        <PublicFooter />
    </div>
</template>

<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import MonitorLink from '@/components/MonitorLink.vue';
import PublicFooter from '@/components/PublicFooter.vue';
import ServerStatsBadge from '@/components/ServerStatsBadge.vue';
import { Card, CardContent } from '@/components/ui/card';
import { Monitor } from '@/types/monitor';
import { Head, Link, router } from '@inertiajs/vue3';
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

interface PaginatorLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Paginator<T> {
    data: T[];
    links: PaginatorLink[];
    meta: {
        current_page: number;
        from: number;
        last_page: number;
        per_page: number;
        to: number;
        total: number;
    };
}

interface Incident {
    id: number;
    monitor_id: number;
    type: string;
    started_at: string;
    ended_at: string | null;
    duration_minutes: number | null;
    reason: string | null;
    status_code: number | null;
    monitor: {
        id: number;
        url: string;
        name: string | null;
        is_public: boolean;
        raw_url: string;
    };
}

interface Props {
    monitors: Paginator<Monitor>;
    filters: {
        search: string | null;
        status_filter: string;
        tag_filter: string | null;
        sort_by: string;
    };
    stats: {
        total: number;
        up: number;
        down: number;
        total_public: number;
        daily_checks?: number;
        monthly_checks?: number;
    };
    availableTags?: Array<{ id: number; name: { en: string } }>;
    latestIncidents?: Incident[];
}

const props = defineProps<Props>();

// Reactive data for monitors
const monitorsData = ref(props.monitors.data || []);

// Clean the initial meta data (handle arrays)
const initialMeta = props.monitors.meta || { current_page: 1, last_page: 1 };
const cleanInitialMeta = {
    current_page: Array.isArray(initialMeta.current_page) ? initialMeta.current_page[0] : initialMeta.current_page,
    last_page: Array.isArray(initialMeta.last_page) ? initialMeta.last_page[0] : initialMeta.last_page,
    per_page: Array.isArray(initialMeta.per_page) ? initialMeta.per_page[0] : initialMeta.per_page,
    total: Array.isArray(initialMeta.total) ? initialMeta.total[0] : initialMeta.total,
    from: Array.isArray(initialMeta.from) ? initialMeta.from[0] : initialMeta.from,
    to: Array.isArray(initialMeta.to) ? initialMeta.to[0] : initialMeta.to,
};

const monitorsMeta = ref(cleanInitialMeta);
const currentPage = ref(monitorsMeta.value.current_page);
const monitorsLinks = ref(props.monitors.links || []);

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

// Search and filter functionality
const searchQuery = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status_filter);
const tagFilter = ref(props.filters.tag_filter || '');
const sortBy = ref(props.filters.sort_by || 'default');
const isLoading = ref(false);

// Sorting options
const sortOptions = [
    { value: 'default', label: 'Default (by ID)', icon: 'hash' },
    { value: 'popular', label: 'Most Popular', icon: 'trendingUp' },
    { value: 'uptime', label: 'Best Uptime', icon: 'arrowUp' },
    { value: 'response_time', label: 'Fastest Response', icon: 'zap' },
    { value: 'newest', label: 'Newest First', icon: 'clock' },
    { value: 'name', label: 'Name (A-Z)', icon: 'arrowDownAZ' },
    { value: 'status', label: 'Status (Down First)', icon: 'alertCircle' },
];

let searchTimeout: number | null = null;

const debounceSearch = () => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    searchTimeout = window.setTimeout(() => {
        applyFilters();
    }, 1000);
};

const applyFilters = () => {
    const params = new URLSearchParams();
    if (searchQuery.value) {
        params.append('search', searchQuery.value);
    }
    if (statusFilter.value !== 'all') {
        params.append('status_filter', statusFilter.value);
    }
    if (tagFilter.value) {
        params.append('tag_filter', tagFilter.value);
    }
    if (sortBy.value !== 'default') {
        params.append('sort_by', sortBy.value);
    }

    router.visit(`/public-monitors?${params.toString()}`, {
        preserveState: true,
        replace: true,
    });
};

// Track active request to prevent duplicates
let activeLoadMoreRequest: AbortController | null = null;
let isLoadingMoreActive = false;

const loadMore = async () => {
    // Prevent multiple concurrent requests
    if (isLoading.value) return;

    // Cancel any pending request
    if (activeLoadMoreRequest) {
        activeLoadMoreRequest.abort();
    }

    console.log('LOAD MORE: Starting with currentPage:', currentPage.value);
    isLoading.value = true;
    isLoadingMoreActive = true;
    const nextPage = currentPage.value + 1;
    console.log('LOAD MORE: Requesting page:', nextPage);

    // Create new AbortController for this request
    activeLoadMoreRequest = new AbortController();

    const params = new URLSearchParams();
    params.append('page', nextPage.toString());
    if (searchQuery.value) {
        params.append('search', searchQuery.value);
    }
    if (statusFilter.value !== 'all') {
        params.append('status_filter', statusFilter.value);
    }
    if (tagFilter.value) {
        params.append('tag_filter', tagFilter.value);
    }
    if (sortBy.value !== 'default') {
        params.append('sort_by', sortBy.value);
    }

    try {
        const response = await fetch(`/public-monitors?${params.toString()}`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            signal: activeLoadMoreRequest.signal,
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        console.log('LOAD MORE: Response received for page:', data.meta.current_page);
        console.log('LOAD MORE: Data count:', data.data.length);

        // Only update if we haven't been aborted
        if (!activeLoadMoreRequest.signal.aborted) {
            // Clean the meta data (handle arrays)
            const cleanMeta = {
                current_page: Array.isArray(data.meta.current_page) ? data.meta.current_page[0] : data.meta.current_page,
                last_page: Array.isArray(data.meta.last_page) ? data.meta.last_page[0] : data.meta.last_page,
                per_page: Array.isArray(data.meta.per_page) ? data.meta.per_page[0] : data.meta.per_page,
                total: Array.isArray(data.meta.total) ? data.meta.total[0] : data.meta.total,
                from: Array.isArray(data.meta.from) ? data.meta.from[0] : data.meta.from,
                to: Array.isArray(data.meta.to) ? data.meta.to[0] : data.meta.to,
            };

            console.log('LOAD MORE: Before append - monitors count:', monitorsData.value.length);

            // Append new monitors to existing data - do this AFTER setting isLoadingMoreActive to false
            // so the watcher won't interfere
            monitorsData.value.push(...data.data);
            monitorsMeta.value = cleanMeta;
            monitorsLinks.value = data.links;
            currentPage.value = nextPage;

            console.log('LOAD MORE: After append - monitors count:', monitorsData.value.length);
            console.log('LOAD MORE: Updated currentPage to:', currentPage.value);

            // Use nextTick to ensure the watcher doesn't interfere
            await nextTick();
            isLoadingMoreActive = false;
        }
    } catch (error) {
        // Ignore abort errors
        if (error instanceof Error && error.name !== 'AbortError') {
            console.error('Error loading more monitors:', error);
        }
        isLoadingMoreActive = false;
    } finally {
        isLoading.value = false;
        activeLoadMoreRequest = null;
    }
};

const viewMonitor = (monitor: Monitor) => {
    const domain = monitor.url.replace('https://', '').replace('http://', '');
    router.visit(`/m/${domain}`);
};

const createMonitor = () => {
    router.visit('/monitor/create');
};

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

const getTagDisplayName = (tag: any): string => {
    const tagName = typeof tag.name === 'string' ? tag.name : tag.name?.en || tag.name || tag;
    return tagName.length > 8 ? tagName.substring(0, 8) + '...' : tagName;
};

const formatDailyChecks = (count: number): string => {
    if (count >= 1000000) {
        return (count / 1000000).toFixed(1) + 'M';
    } else if (count >= 1000) {
        return (count / 1000).toFixed(1) + 'K';
    }
    return count.toString();
};

const formatChecksCount = (count: number): string => {
    if (count >= 1000000000) {
        return (count / 1000000000).toFixed(1) + 'B';
    } else if (count >= 1000000) {
        return (count / 1000000).toFixed(1) + 'M';
    } else if (count >= 1000) {
        return (count / 1000).toFixed(1) + 'K';
    }
    return count.toString();
};

const formatRelativeTime = (dateString: string): string => {
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now.getTime() - date.getTime()) / 1000);

    if (diffInSeconds < 60) {
        return 'just now';
    } else if (diffInSeconds < 3600) {
        const minutes = Math.floor(diffInSeconds / 60);
        return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
    } else if (diffInSeconds < 86400) {
        const hours = Math.floor(diffInSeconds / 3600);
        return `${hours} hour${hours > 1 ? 's' : ''} ago`;
    } else if (diffInSeconds < 604800) {
        const days = Math.floor(diffInSeconds / 86400);
        return `${days} day${days > 1 ? 's' : ''} ago`;
    } else {
        return date.toLocaleDateString();
    }
};

const formatDuration = (minutes: number): string => {
    if (minutes < 60) {
        return `${minutes} minute${minutes !== 1 ? 's' : ''}`;
    }
    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;
    if (hours < 24) {
        return remainingMinutes > 0
            ? `${hours} hour${hours !== 1 ? 's' : ''} ${remainingMinutes} minute${remainingMinutes !== 1 ? 's' : ''}`
            : `${hours} hour${hours !== 1 ? 's' : ''}`;
    }
    const days = Math.floor(hours / 24);
    const remainingHours = hours % 24;
    return remainingHours > 0
        ? `${days} day${days !== 1 ? 's' : ''} ${remainingHours} hour${remainingHours !== 1 ? 's' : ''}`
        : `${days} day${days !== 1 ? 's' : ''}`;
};

// Track if this is the first time we're setting up data
let isInitialSetup = true;

// Watch for changes in props and update reactive data
watch(
    () => props.monitors,
    (newMonitors, oldMonitors) => {
        console.log('WATCHER: Props changed!');
        console.log('WATCHER: isInitialSetup:', isInitialSetup);
        console.log('WATCHER: isLoadingMoreActive:', isLoadingMoreActive);
        console.log('WATCHER: currentPage before:', currentPage.value);

        // Clean the meta data (handle arrays)
        const cleanMeta = {
            current_page: Array.isArray(newMonitors.meta.current_page) ? newMonitors.meta.current_page[0] : newMonitors.meta.current_page,
            last_page: Array.isArray(newMonitors.meta.last_page) ? newMonitors.meta.last_page[0] : newMonitors.meta.last_page,
            per_page: Array.isArray(newMonitors.meta.per_page) ? newMonitors.meta.per_page[0] : newMonitors.meta.per_page,
            total: Array.isArray(newMonitors.meta.total) ? newMonitors.meta.total[0] : newMonitors.meta.total,
            from: Array.isArray(newMonitors.meta.from) ? newMonitors.meta.from[0] : newMonitors.meta.from,
            to: Array.isArray(newMonitors.meta.to) ? newMonitors.meta.to[0] : newMonitors.meta.to,
        };

        console.log('WATCHER: cleanMeta.current_page:', cleanMeta.current_page);

        // Don't interfere with load more operations
        if (isLoadingMoreActive) {
            console.log('WATCHER: Load more is active, skipping data replacement');
            // Always update meta and links from props
            monitorsMeta.value = cleanMeta;
            monitorsLinks.value = newMonitors.links || [];
            return;
        }

        // Only replace monitors data if this is the initial setup or if we got different data
        // (which happens on filter/search changes, but NOT during load more operations)
        if (isInitialSetup || !oldMonitors || cleanMeta.current_page === 1) {
            console.log('WATCHER: Replacing data and resetting currentPage');
            monitorsData.value = newMonitors.data || [];
            currentPage.value = cleanMeta.current_page;
            isInitialSetup = false;
        } else {
            console.log('WATCHER: NOT replacing data, keeping currentPage');
        }

        // Always update meta and links from props
        monitorsMeta.value = cleanMeta;
        monitorsLinks.value = newMonitors.links || [];

        console.log('WATCHER: currentPage after:', currentPage.value);
    },
    { deep: true },
);

// Watch for changes in filters and update local state
watch(
    () => props.filters,
    (newFilters) => {
        searchQuery.value = newFilters.search || '';
        statusFilter.value = newFilters.status_filter;
        tagFilter.value = newFilters.tag_filter || '';
        sortBy.value = newFilters.sort_by || 'default';

        // Reset the initial setup flag when filters change so monitors data gets replaced
        isInitialSetup = true;
    },
    { deep: true },
);

// Back to top functionality
const showBackToTop = ref(false);

const handleScroll = () => {
    showBackToTop.value = window.scrollY > 300;
};

const scrollToTop = () => {
    window.scrollTo({
        top: 0,
        behavior: 'smooth',
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

    // Add scroll event listener for back to top button
    window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
    // Remove scroll event listener
    window.removeEventListener('scroll', handleScroll);
});
</script>
