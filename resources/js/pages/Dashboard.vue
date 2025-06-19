<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import PublicMonitorsCard from '../components/PublicMonitorsCard.vue';
import { ref, onMounted, computed } from 'vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import type { Monitor } from '@/types/monitor';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const searchQuery = ref('');
const statusFilter = ref<'all' | 'up' | 'down' | 'unsubscribed'>('all');

// Monitor data for counts
const publicMonitors = ref<Monitor[]>([]);
const loadingMonitors = ref(false);
const errorMonitors = ref<string | null>(null);

async function fetchPublicMonitors() {
    loadingMonitors.value = true;
    try {
        const response = await fetch('/public-monitors');
        if (!response.ok) throw new Error('Failed to fetch public monitors');
        publicMonitors.value = await response.json();
        errorMonitors.value = null;
    } catch (err) {
        errorMonitors.value = err instanceof Error ? err.message : 'An error occurred';
    } finally {
        loadingMonitors.value = false;
    }
}

onMounted(() => {
    fetchPublicMonitors();
});

const allCount = computed(() => publicMonitors.value.length);
const onlineCount = computed(() => publicMonitors.value.filter(m => m.uptime_status === 'up').length);
const offlineCount = computed(() => publicMonitors.value.filter(m => m.uptime_status === 'down').length);
const unsubscribedCount = computed(() => publicMonitors.value.filter(m => !m.is_subscribed).length);
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- Status Filter Bar -->
            <div class="flex gap-2 mb-2">
                <Button
                    :variant="statusFilter === 'all' ? 'default' : 'outline'"
                    @click="statusFilter = 'all'"
                >
                    Semua <span v-if="!loadingMonitors" class="ml-1 px-2 py-0.5 rounded-full bg-gray-200 dark:bg-gray-400 text-xs">{{ allCount }}</span>
                </Button>
                <Button
                    :variant="statusFilter === 'up' ? 'default' : 'outline'"
                    @click="statusFilter = 'up'"
                >
                    Online <span v-if="!loadingMonitors" class="ml-1 px-2 py-0.5 rounded-full bg-green-200 dark:bg-green-700 text-xs">{{ onlineCount }}</span>
                </Button>
                <Button
                    :variant="statusFilter === 'down' ? 'default' : 'outline'"
                    @click="statusFilter = 'down'"
                >
                    Offline <span v-if="!loadingMonitors" class="ml-1 px-2 py-0.5 rounded-full bg-red-200 dark:bg-red-700 text-xs">{{ offlineCount }}</span>
                </Button>
                <Button
                    :variant="statusFilter === 'unsubscribed' ? 'default' : 'outline'"
                    @click="statusFilter = 'unsubscribed'"
                >
                    Unsubscribed <span v-if="!loadingMonitors" class="ml-1 px-2 py-0.5 rounded-full bg-yellow-200 dark:bg-yellow-700 text-xs">{{ unsubscribedCount }}</span>
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

            <!-- <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border md:min-h-min"> -->
                <PublicMonitorsCard :search-query="searchQuery" :status-filter="statusFilter" />
            <!-- </div> -->
        </div>
    </AppLayout>
</template>
