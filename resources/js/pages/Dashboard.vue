<script setup lang="ts">
    import AppLayout from '@/layouts/AppLayout.vue';
    import { type BreadcrumbItem } from '@/types';
    import { Head, usePage } from '@inertiajs/vue3';
    import PublicMonitorsCard from '../components/PublicMonitorsCard.vue';
    import PrivateMonitorsCard from '../components/PrivateMonitorsCard.vue';
    import { ref, onMounted, computed } from 'vue';
    import Icon from '@/components/Icon.vue';
    import { Button } from '@/components/ui/button';

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
    const userCount = ref<number|null>(null);

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
            <div v-if="userId === 1 && userCount !== null" class="rounded-lg border bg-white dark:bg-gray-800 p-4 shadow flex flex-col items-center mb-2">
                <div class="text-lg font-semibold mb-1">Total Users</div>
                <div class="text-3xl font-bold">{{ userCount }}</div>
            </div>
            <!-- Status Filter Bar -->
            <div class="flex gap-2 mb-2 max-w-vw overflow-auto py-3">
                <Button
                    :variant="statusFilter === 'all' ? 'default' : 'outline'"
                    @click="statusFilter = 'all'"
                    class="text-black bg-white dark:bg-gray-800 dark:text-white border-grey-300 shadow-sm hover:bg-gray-100 dark:hover:bg-gray-700 dark:shadow-white"
                >
                    All <span v-if="!loadingMonitors" class="ml-1 px-2 py-0.5 rounded-full bg-gray-200 dark:bg-gray-400 text-xs">{{ allCount }}</span>
                </Button>
                <Button
                    :variant="statusFilter === 'up' ? 'default' : 'outline'"
                    @click="statusFilter = 'up'"
                    class="text-black bg-white dark:bg-gray-800 dark:text-white border-grey-300 shadow-sm hover:bg-gray-100 dark:hover:bg-gray-700 dark:shadow-white"
                >
                    Online <span v-if="!loadingMonitors" class="ml-1 px-2 py-0.5 rounded-full bg-green-200 dark:bg-green-700 text-xs">{{ onlineCount }}</span>
                </Button>
                <Button
                    :variant="statusFilter === 'down' ? 'default' : 'outline'"
                    @click="statusFilter = 'down'"
                    class="text-black bg-white dark:bg-gray-800 dark:text-white border-grey-300 shadow-sm hover:bg-gray-100 dark:hover:bg-gray-700 dark:shadow-white"
                >
                    Offline <span v-if="!loadingMonitors" class="ml-1 px-2 py-0.5 rounded-full bg-red-200 dark:bg-red-700 text-xs">{{ offlineCount }}</span>
                </Button>
                <Button
                    :variant="statusFilter === 'unsubscribed' ? 'default' : 'outline'"
                    @click="statusFilter = 'unsubscribed'"
                    class="text-black bg-white dark:bg-gray-800 dark:text-white border-grey-300 shadow-sm hover:bg-gray-100 dark:hover:bg-gray-700 dark:shadow-white"
                >
                    Unsubscribed <span v-if="!loadingMonitors" class="ml-1 px-2 py-0.5 rounded-full bg-yellow-200 dark:bg-yellow-700 text-xs">{{ unsubscribedCount }}</span>
                </Button>
                <Button
                    :variant="statusFilter === 'globally_enabled' ? 'default' : 'outline'"
                    @click="statusFilter = 'globally_enabled'"
                    class="text-black bg-white dark:bg-gray-800 dark:text-white border-grey-300 shadow-sm hover:bg-gray-100 dark:hover:bg-gray-700 dark:shadow-white"
                >
                    Enabled <span v-if="!loadingMonitors" class="ml-1 px-2 py-0.5 rounded-full bg-blue-200 dark:bg-blue-700 text-xs">{{ enabledCount }}</span>
                </Button>
                <Button
                    :variant="statusFilter === 'globally_disabled' ? 'default' : 'outline'"
                    @click="statusFilter = 'globally_disabled'"
                    class="text-black bg-white dark:bg-gray-800 dark:text-white border-grey-300 shadow-sm hover:bg-gray-100 dark:hover:bg-gray-700 dark:shadow-white"
                >
                    Disabled <span v-if="!loadingMonitors" class="ml-1 px-2 py-0.5 rounded-full bg-gray-300 dark:bg-gray-600 text-xs">{{ disabledCount }}</span>
                </Button>
            </div>
            <!-- Search Bar -->
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <Icon name="search" class="h-5 w-5 text-gray-400" />
                </div>
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Cari monitor berdasarkan domain atau URL..."
                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <div v-if="searchQuery" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                    <button
                        @click="searchQuery = ''"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                    >
                        <Icon name="x" class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <PrivateMonitorsCard v-if="isAuthenticated" :search-query="searchQuery" :status-filter="statusFilter" :all-count="allCount" :online-count="onlineCount" :offline-count="offlineCount" :unsubscribed-count="unsubscribedCount" :disabled-count="disabledCount" :enabled-count="enabledCount" />
            <PublicMonitorsCard :search-query="searchQuery" :status-filter="statusFilter" :all-count="allCount" :online-count="onlineCount" :offline-count="offlineCount" :unsubscribed-count="unsubscribedCount" :disabled-count="disabledCount" :enabled-count="enabledCount" />
        </div>
    </AppLayout>
</template>
