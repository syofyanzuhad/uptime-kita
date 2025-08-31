<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import MonitorGrid from '@/components/MonitorGrid.vue';
import Button from '@/components/ui/button/Button.vue';
import Dialog from '@/components/ui/dialog/Dialog.vue';
import DialogContent from '@/components/ui/dialog/DialogContent.vue';
import DialogDescription from '@/components/ui/dialog/DialogDescription.vue';
import DialogFooter from '@/components/ui/dialog/DialogFooter.vue';
import DialogHeader from '@/components/ui/dialog/DialogHeader.vue';
import DialogTitle from '@/components/ui/dialog/DialogTitle.vue';
import Input from '@/components/ui/input/Input.vue';
import Select from '@/components/ui/input/Select.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import type { Monitor, Paginator } from '@/types/monitor';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps<{
    monitors: Paginator<Monitor>;
    type: 'pinned' | 'private' | 'public';
    search?: string;
    statusFilter?: string;
    visibilityFilter?: string;
    tagFilter?: string;
    perPage?: number;
    availableTags?: Array<{ id: number; name: string }>;
}>();

// All monitors data (for load more functionality)
const allMonitors = ref<Monitor[]>([...props.monitors.data]);
const hasMorePages = ref(props.monitors.meta.current_page < props.monitors.meta.last_page);
const loadingMore = ref(false);

// Compute title and breadcrumbs based on type
const pageTitle = computed(() => {
    switch (props.type) {
        case 'pinned':
            return 'Pinned Monitors';
        case 'private':
            return 'Private Monitors';
        case 'public':
            return 'Public Monitors';
        default:
            return 'Monitors';
    }
});

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: 'Monitors',
        href: '/monitor',
    },
    {
        title: pageTitle.value,
        href: `/monitors/${props.type}`,
    },
]);

// Search and filter states
const searchQuery = ref(props.search || '');
const statusFilter = ref(props.statusFilter || 'all');
const visibilityFilter = ref(props.visibilityFilter || 'all');
const tagFilter = ref(props.tagFilter || '');
const perPage = ref(props.perPage || 12);

// Delete monitor state
const showDeleteModal = ref(false);
const monitorToDelete = ref<Monitor | null>(null);
const isDeleting = ref(false);

// Monitor action states
const loadingMonitors = ref(new Set<number>());
const togglingMonitors = ref(new Set<number>());
const subscribingMonitors = ref(new Set<number>());
const unsubscribingMonitors = ref(new Set<number>());

// Countdown timer state
const countdown = ref(0);
let countdownInterval: number | null = null;

// Function to calculate seconds until next minute
const calculateSecondsUntilNextMinute = () => {
    const now = new Date();
    return 60 - now.getSeconds();
};

// Function to start countdown
const startCountdown = () => {
    countdown.value = calculateSecondsUntilNextMinute();
    countdownInterval = window.setInterval(() => {
        countdown.value--;
        if (countdown.value <= 0) {
            countdown.value = calculateSecondsUntilNextMinute();
            router.reload({ only: ['monitors'] });
        }
    }, 1000);
};

// Function to stop countdown
const stopCountdown = () => {
    if (countdownInterval) {
        clearInterval(countdownInterval);
        countdownInterval = null;
    }
};

// Apply filters
const applyFilters = () => {
    router.get(
        `/monitors/${props.type}`,
        {
            search: searchQuery.value || undefined,
            status_filter: statusFilter.value !== 'all' ? statusFilter.value : undefined,
            visibility_filter: visibilityFilter.value !== 'all' ? visibilityFilter.value : undefined,
            tag_filter: tagFilter.value || undefined,
            per_page: perPage.value !== 12 ? perPage.value : undefined,
        },
        {
            preserveState: false,
            preserveScroll: false,
            onSuccess: (page) => {
                const newData = (page.props as any).monitors;
                allMonitors.value = [...newData.data];
                hasMorePages.value = newData.meta.current_page < newData.meta.last_page;
            },
        },
    );
};

// Watch for filter changes
watch([statusFilter, visibilityFilter, tagFilter, perPage], () => {
    applyFilters();
});

// Debounced search
let searchTimeout: number;
watch(searchQuery, () => {
    clearTimeout(searchTimeout);
    searchTimeout = window.setTimeout(() => {
        applyFilters();
    }, 300);
});

const deleteMonitor = () => {
    if (!monitorToDelete.value) return;

    isDeleting.value = true;
    router.delete(`/monitor/${monitorToDelete.value.id}`, {
        onSuccess: () => {
            showDeleteModal.value = false;
            monitorToDelete.value = null;
            isDeleting.value = false;
        },
        onError: () => {
            isDeleting.value = false;
        },
    });
};

// Pinned monitors set
const pinnedMonitors = computed(() => {
    const pinned = new Set<number>();
    allMonitors.value.forEach((monitor) => {
        if (monitor.is_pinned) {
            pinned.add(monitor.id);
        }
    });
    return pinned;
});

// Load more monitors
const loadMore = () => {
    if (loadingMore.value || !hasMorePages.value) return;

    loadingMore.value = true;
    const nextPage = props.monitors.meta.current_page + 1;

    router.get(
        `/monitors/${props.type}`,
        {
            page: nextPage,
            search: searchQuery.value || undefined,
            status_filter: statusFilter.value !== 'all' ? statusFilter.value : undefined,
            visibility_filter: visibilityFilter.value !== 'all' ? visibilityFilter.value : undefined,
            tag_filter: tagFilter.value || undefined,
            per_page: perPage.value !== 12 ? perPage.value : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['monitors'],
            onSuccess: (page) => {
                const newData = (page.props as any).monitors;
                allMonitors.value.push(...newData.data);
                hasMorePages.value = newData.meta.current_page < newData.meta.last_page;
                loadingMore.value = false;
            },
            onError: () => {
                loadingMore.value = false;
            },
        },
    );
};

// Toggle pin status
const togglePin = (monitorId: number) => {
    loadingMonitors.value.add(monitorId);
    const isPinned = pinnedMonitors.value.has(monitorId);
    router.post(
        `/monitor/${monitorId}/toggle-pin`,
        {
            is_pinned: !isPinned,
        },
        {
            onFinish: () => {
                loadingMonitors.value.delete(monitorId);
            },
        },
    );
};

// Toggle active status
const toggleActive = (monitorId: number) => {
    togglingMonitors.value.add(monitorId);
    router.post(
        `/monitor/${monitorId}/toggle-active`,
        {
            is_active: false, // This will be handled by the backend
        },
        {
            onFinish: () => {
                togglingMonitors.value.delete(monitorId);
            },
        },
    );
};

// Subscribe monitor
const subscribeMonitor = (monitorId: number) => {
    subscribingMonitors.value.add(monitorId);
    router.post(
        `/monitor/${monitorId}/subscribe`,
        {},
        {
            onFinish: () => {
                subscribingMonitors.value.delete(monitorId);
            },
        },
    );
};

// Unsubscribe monitor
const unsubscribeMonitor = (monitorId: number) => {
    unsubscribingMonitors.value.add(monitorId);
    router.delete(`/monitor/${monitorId}/unsubscribe`, {
        onFinish: () => {
            unsubscribingMonitors.value.delete(monitorId);
        },
    });
};

// Lifecycle hooks
onMounted(() => {
    startCountdown();
});

onUnmounted(() => {
    stopCountdown();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="pageTitle" />

        <div class="container mx-auto px-4 py-8">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">{{ pageTitle }}</h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        <span v-if="type === 'pinned'">Your pinned monitors for quick access</span>
                        <span v-else-if="type === 'private'">Monitors only visible to you</span>
                        <span v-else>Publicly accessible monitors</span>
                    </p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Auto-refresh in {{ countdown }}s</div>
                    <Link href="/monitor/create">
                        <Button>
                            <Icon name="heroicons:plus" class="mr-2" />
                            Add Monitor
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Filters -->
            <div class="mb-6 rounded-lg bg-white p-4 shadow dark:bg-gray-800">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <!-- Search -->
                    <div>
                        <label class="mb-2 block text-sm font-medium">Search</label>
                        <Input v-model="searchQuery" placeholder="Search monitors..." class="w-full" />
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label class="mb-2 block text-sm font-medium">Status</label>
                        <Select
                            v-model="statusFilter"
                            :items="[
                                { label: 'All', value: 'all' },
                                { label: 'Up', value: 'up' },
                                { label: 'Down', value: 'down' },
                                { label: 'Disabled', value: 'disabled' },
                            ]"
                            placeholder="All statuses"
                        />
                    </div>

                    <!-- Visibility Filter (only for non-type-specific views) -->
                    <div v-if="type !== 'private' && type !== 'public'">
                        <label class="mb-2 block text-sm font-medium">Visibility</label>
                        <Select
                            v-model="visibilityFilter"
                            :items="[
                                { label: 'All', value: 'all' },
                                { label: 'Public', value: 'public' },
                                { label: 'Private', value: 'private' },
                            ]"
                            placeholder="All visibility"
                        />
                    </div>

                    <!-- Per Page -->
                    <div>
                        <label class="mb-2 block text-sm font-medium">Per Page</label>
                        <Select
                            v-model="perPage"
                            :items="[
                                { label: '12', value: 12 },
                                { label: '24', value: 24 },
                                { label: '48', value: 48 },
                                { label: '96', value: 96 },
                            ]"
                        />
                    </div>
                </div>
            </div>

            <!-- Monitors Grid -->
            <div class="bg-card rounded-lg border border-gray-200 p-6 shadow dark:border-gray-700">
                <MonitorGrid
                    v-if="allMonitors.length > 0"
                    :monitors="allMonitors"
                    :type="type === 'public' ? 'public' : 'private'"
                    :pinned-monitors="pinnedMonitors"
                    :on-toggle-pin="togglePin"
                    :on-toggle-active="toggleActive"
                    :on-subscribe="subscribeMonitor"
                    :on-unsubscribe="unsubscribeMonitor"
                    :loading-monitors="loadingMonitors"
                    :toggling-monitors="togglingMonitors"
                    :subscribing-monitors="subscribingMonitors"
                    :unsubscribing-monitors="unsubscribingMonitors"
                    :show-subscribe-button="type === 'public'"
                    :show-toggle-button="type !== 'public'"
                    :show-pin-button="true"
                    :show-uptime-percentage="true"
                    :show-certificate-status="true"
                    :show-last-checked="true"
                    grid-cols="grid-cols-1 md:grid-cols-2 xl:grid-cols-3"
                />
                <div v-else class="py-12 text-center text-gray-500 dark:text-gray-400">
                    <Icon name="heroicons:exclamation-triangle" class="mx-auto mb-4 h-16 w-16 text-gray-400" />
                    <h3 class="mb-2 text-lg font-medium">No monitors found</h3>
                    <p class="text-sm">
                        <span v-if="type === 'pinned'">You haven't pinned any monitors yet.</span>
                        <span v-else-if="type === 'private'">You don't have any private monitors.</span>
                        <span v-else-if="type === 'public'">No public monitors are available.</span>
                        <span v-else>No monitors match your current filters.</span>
                    </p>
                    <Link href="/monitor/create" class="mt-4 inline-block">
                        <Button>
                            <Icon name="heroicons:plus" class="mr-2" />
                            Create Monitor
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Load More Button -->
            <div class="mt-6 text-center" v-if="hasMorePages">
                <Button @click="loadMore" :disabled="loadingMore" variant="outline" size="lg">
                    <Icon v-if="loadingMore" name="heroicons:arrow-path" class="mr-2 h-4 w-4 animate-spin" />
                    <Icon v-else name="heroicons:chevron-down" class="mr-2 h-4 w-4" />
                    {{ loadingMore ? 'Loading...' : 'Load More' }}
                </Button>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <Dialog v-model:open="showDeleteModal">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete Monitor</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to delete the monitor for
                        <strong>{{ monitorToDelete?.url }}</strong
                        >? This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="showDeleteModal = false" :disabled="isDeleting"> Cancel </Button>
                    <Button variant="destructive" @click="deleteMonitor" :disabled="isDeleting">
                        {{ isDeleting ? 'Deleting...' : 'Delete' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
