<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import PinnedMonitorsCard from '../components/PinnedMonitorsCard.vue';
import PrivateMonitorsCard from '../components/PrivateMonitorsCard.vue';
import PublicMonitorsCard from '../components/PublicMonitorsCard.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const searchQuery = ref('');
const statusFilter = ref<'all' | 'up' | 'down' | 'unsubscribed' | 'globally_enabled' | 'globally_disabled'>('all');

// Monitor data for counts
const loadingMonitors = ref(false);
const errorMonitors = ref<string | null>(null);
const allCount = ref(0);
const onlineCount = ref(0);
const offlineCount = ref(0);
const unsubscribedCount = ref(0);
const enabledCount = ref(0);
const disabledCount = ref(0);
const userId = computed(() => (page.props as any).auth?.user?.id);
const userCount = ref<number | null>(null);

const page = usePage();
const isAuthenticated = computed(() => !!(page.props as any).auth?.user);

async function fetchMonitorStatistics() {
    loadingMonitors.value = true;
    try {
        const response = await fetch('/statistic-monitor');
        if (!response.ok) throw new Error('Failed to fetch monitor statistics');
        const stats = await response.json();

        if (isAuthenticated.value) {
            allCount.value = stats.total_monitors;
        } else {
            allCount.value = stats.public_monitor_count;
        }
        onlineCount.value = stats.online_monitors;
        offlineCount.value = stats.offline_monitors;
        unsubscribedCount.value = stats.unsubscribed_monitors;
        enabledCount.value = stats.globally_enabled_monitors || 0;
        disabledCount.value = stats.globally_disabled_monitors || 0;
        if ('user_count' in stats) {
            userCount.value = stats.user_count;
        } else {
            userCount.value = null;
        }

        errorMonitors.value = null;
    } catch (err) {
        errorMonitors.value = err instanceof Error ? err.message : 'An error occurred';
    } finally {
        loadingMonitors.value = false;
    }
}

onMounted(() => {
    fetchMonitorStatistics();
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Admin User Count Card -->
            <div
                v-if="userId === 1 && userCount !== null"
                class="mb-1 flex flex-col items-center rounded-lg border bg-white p-4 shadow dark:bg-gray-800"
            >
                <div class="mb-1 text-lg font-semibold">Total Users</div>
                <div class="text-3xl font-bold">{{ userCount }}</div>
            </div>
            <!-- Status Filter Bar -->
            <div class="max-w-vw my-2 flex gap-2 overflow-auto">
                <Button
                    :variant="statusFilter === 'all' ? 'default' : 'outline'"
                    @click="statusFilter = 'all'"
                    class="border-grey-300 bg-white text-black shadow-sm hover:bg-gray-100 dark:bg-gray-800 dark:text-white dark:shadow-white dark:hover:bg-gray-700"
                >
                    All <span v-if="!loadingMonitors" class="ml-1 rounded-full bg-gray-200 px-2 py-0.5 text-xs dark:bg-gray-400">{{ allCount }}</span>
                </Button>
                <Button
                    :variant="statusFilter === 'up' ? 'default' : 'outline'"
                    @click="statusFilter = 'up'"
                    class="border-grey-300 bg-white text-black shadow-sm hover:bg-gray-100 dark:bg-gray-800 dark:text-white dark:shadow-white dark:hover:bg-gray-700"
                >
                    Online
                    <span v-if="!loadingMonitors" class="ml-1 rounded-full bg-green-200 px-2 py-0.5 text-xs dark:bg-green-700">{{
                        onlineCount
                    }}</span>
                </Button>
                <Button
                    :variant="statusFilter === 'down' ? 'default' : 'outline'"
                    @click="statusFilter = 'down'"
                    class="border-grey-300 bg-white text-black shadow-sm hover:bg-gray-100 dark:bg-gray-800 dark:text-white dark:shadow-white dark:hover:bg-gray-700"
                >
                    Offline
                    <span v-if="!loadingMonitors" class="ml-1 rounded-full bg-red-200 px-2 py-0.5 text-xs dark:bg-red-700">{{ offlineCount }}</span>
                </Button>
                <Button
                    :variant="statusFilter === 'unsubscribed' ? 'default' : 'outline'"
                    @click="statusFilter = 'unsubscribed'"
                    class="border-grey-300 bg-white text-black shadow-sm hover:bg-gray-100 dark:bg-gray-800 dark:text-white dark:shadow-white dark:hover:bg-gray-700"
                >
                    Unsubscribed
                    <span v-if="!loadingMonitors" class="ml-1 rounded-full bg-yellow-200 px-2 py-0.5 text-xs dark:bg-yellow-700">{{
                        unsubscribedCount
                    }}</span>
                </Button>
                <Button
                    :variant="statusFilter === 'globally_enabled' ? 'default' : 'outline'"
                    @click="statusFilter = 'globally_enabled'"
                    class="border-grey-300 bg-white text-black shadow-sm hover:bg-gray-100 dark:bg-gray-800 dark:text-white dark:shadow-white dark:hover:bg-gray-700"
                >
                    Enabled
                    <span v-if="!loadingMonitors" class="ml-1 rounded-full bg-blue-200 px-2 py-0.5 text-xs dark:bg-blue-700">{{ enabledCount }}</span>
                </Button>
                <Button
                    :variant="statusFilter === 'globally_disabled' ? 'default' : 'outline'"
                    @click="statusFilter = 'globally_disabled'"
                    class="border-grey-300 bg-white text-black shadow-sm hover:bg-gray-100 dark:bg-gray-800 dark:text-white dark:shadow-white dark:hover:bg-gray-700"
                >
                    Disabled
                    <span v-if="!loadingMonitors" class="ml-1 rounded-full bg-gray-300 px-2 py-0.5 text-xs dark:bg-gray-600">{{
                        disabledCount
                    }}</span>
                </Button>
            </div>
            <!-- Search Bar -->
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <Icon name="search" class="h-5 w-5 text-gray-400" />
                </div>
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Cari monitor berdasarkan domain atau URL..."
                    class="block w-full rounded-lg border border-gray-300 bg-white py-2 pr-3 pl-10 text-gray-900 placeholder-gray-500 focus:border-transparent focus:ring-2 focus:ring-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:placeholder-gray-400"
                />
                <div v-if="searchQuery" class="absolute inset-y-0 right-0 flex items-center pr-3">
                    <button @click="searchQuery = ''" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <Icon name="x" class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <PinnedMonitorsCard
                v-if="isAuthenticated"
                :search-query="searchQuery"
                :status-filter="statusFilter"
                :all-count="allCount"
                :online-count="onlineCount"
                :offline-count="offlineCount"
                :unsubscribed-count="unsubscribedCount"
                :disabled-count="disabledCount"
                :enabled-count="enabledCount"
            />
            <PrivateMonitorsCard
                v-if="isAuthenticated"
                :search-query="searchQuery"
                :status-filter="statusFilter"
                :all-count="allCount"
                :online-count="onlineCount"
                :offline-count="offlineCount"
                :unsubscribed-count="unsubscribedCount"
                :disabled-count="disabledCount"
                :enabled-count="enabledCount"
            />
            <PublicMonitorsCard
                :search-query="searchQuery"
                :status-filter="statusFilter"
                :all-count="allCount"
                :online-count="onlineCount"
                :offline-count="offlineCount"
                :unsubscribed-count="unsubscribedCount"
                :disabled-count="disabledCount"
                :enabled-count="enabledCount"
            />
        </div>
    </AppLayout>
</template>
