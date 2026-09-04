<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
// Impor Link dan usePage dari @inertiajs/vue3
// Penting: Untuk request seperti delete, post, put, kita akan menggunakan 'router'
import type { Monitor, Paginator } from '@/types/monitor';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
// Import Dialog and Button components for modal
import Icon from '@/components/Icon.vue';
import Pagination from '@/components/Pagination.vue';
import Button from '@/components/ui/button/Button.vue';
import Dialog from '@/components/ui/dialog/Dialog.vue';
import DialogContent from '@/components/ui/dialog/DialogContent.vue';
import DialogDescription from '@/components/ui/dialog/DialogDescription.vue';
import DialogFooter from '@/components/ui/dialog/DialogFooter.vue';
import DialogHeader from '@/components/ui/dialog/DialogHeader.vue';
import DialogTitle from '@/components/ui/dialog/DialogTitle.vue';
import { Input, Select } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { ExternalLink, Eye, Pencil, Search, Trash2, X } from 'lucide-vue-next';
import CreateMonitorModal from './partials/CreateMonitorModal.vue';
import DetailMonitorModal from './partials/DetailMonitorModal.vue';
import EditMonitorModal from './partials/EditMonitorModal.vue';

// Pastikan props didefinisikan dengan benar dan diakses di template dengan 'props.' jika perlu
const props = defineProps<{
    monitors: Paginator<Monitor>;
    search?: string;
    statusFilter?: string;
    visibilityFilter?: string;
    tagFilter?: string;
    perPage?: number;
    availableTags?: Array<{ id: number; name: string }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Uptime Monitor',
        href: '/monitors',
    },
];

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
    const updateCountdown = () => {
        const secondsUntilNextMinute = calculateSecondsUntilNextMinute();
        countdown.value = secondsUntilNextMinute;

        // If we're at the start of a minute, trigger a refresh
        if (secondsUntilNextMinute === 60) {
            router.reload({ only: ['monitors'] });
        }
    };

    // Initial update
    updateCountdown();

    // Update every second
    if (countdownInterval) {
        clearInterval(countdownInterval);
    }
    countdownInterval = setInterval(updateCountdown, 1000);
};

// Start countdown when component is mounted
onMounted(() => {
    startCountdown();
});

// Clean up interval when component is unmounted
onUnmounted(() => {
    if (countdownInterval) {
        clearInterval(countdownInterval);
    }
});

// Fungsi untuk menghapus monitor
// Modal state
const isDeleteModalOpen = ref(false);
const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isDetailModalOpen = ref(false);

const monitorToDelete = ref<Monitor | null>(null);
const monitorToEdit = ref<Monitor | null>(null);
const monitorToView = ref<Monitor | null>(null);

const openDeleteModal = (monitor: Monitor) => {
    monitorToDelete.value = monitor;
    isDeleteModalOpen.value = true;
};

const openEditModal = (monitor: Monitor) => {
    monitorToEdit.value = monitor;
    isEditModalOpen.value = true;
};

const openDetailModal = (monitor: Monitor) => {
    monitorToView.value = monitor;
    isDetailModalOpen.value = true;
};

const closeDeleteModal = () => {
    isDeleteModalOpen.value = false;
    monitorToDelete.value = null;
};

const confirmDeleteMonitor = () => {
    if (monitorToDelete.value) {
        router.delete(route('monitors.destroy', monitorToDelete.value.id), {
            onSuccess: () => closeDeleteModal(),
            onFinish: () => closeDeleteModal(),
        });
    }
};

const search = ref(props.search || '');
const statusFilter = ref(props.statusFilter || 'all');
const visibilityFilter = ref(props.visibilityFilter || 'all');
const tagFilter = ref(props.tagFilter || 'all');
const perPage = ref(String(props.perPage || 15));

const statusOptions = [
    { label: 'Semua Status', value: 'all' },
    { label: 'Up', value: 'up' },
    { label: 'Down', value: 'down' },
];

const visibilityOptions = [
    { label: 'Semua Visibilitas', value: 'all' },
    { label: 'Publik', value: 'public' },
    { label: 'Privat', value: 'private' },
];

const tagOptions = computed(() => [
    { label: 'Semua Tag', value: 'all' },
    ...(props.availableTags || []).map((tag) => ({ label: tag.name, value: tag.name })),
]);

const perPageOptions = [
    { label: '5 / halaman', value: '5' },
    { label: '10 / halaman', value: '10' },
    { label: '15 / halaman', value: '15' },
    { label: '20 / halaman', value: '20' },
    { label: '50 / halaman', value: '50' },
    { label: '100 / halaman', value: '100' },
];

function submitSearch() {
    router.get(
        route('monitors.index'),
        {
            search: search.value || undefined,
            status_filter: statusFilter.value !== 'all' ? statusFilter.value : undefined,
            visibility_filter: visibilityFilter.value !== 'all' ? visibilityFilter.value : undefined,
            tag_filter: tagFilter.value && tagFilter.value !== 'all' ? tagFilter.value : undefined,
            per_page: perPage.value,
        },
        { preserveState: true, only: ['monitors', 'search', 'statusFilter', 'perPage', 'visibilityFilter', 'tagFilter'] },
    );
}

function clearSearch() {
    search.value = '';
    submitSearch();
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Uptime Monitor" />

        <div class="py-10">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white p-6 shadow-sm sm:rounded-lg dark:bg-gray-800">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Daftar Monitor</h3>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Next refresh in: {{ countdown }}s</div>
                        <div class="flex items-center gap-2">
                            <Link
                                :href="route('monitors.import.index')"
                                class="rounded-md border border-gray-300 bg-white px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                            >
                                Import
                            </Link>
                            <Button
                                @click="isCreateModalOpen = true"
                                class="rounded-md bg-blue-500 px-4 py-2 text-white hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700"
                            >
                                Tambah Monitor
                            </Button>
                        </div>
                    </div>

                    <!-- Search Bar & Filter Toolbar -->
                    <form @submit.prevent="submitSearch" class="mb-5 flex flex-wrap items-center gap-2.5">
                        <div class="relative min-w-[220px] flex-1 max-w-sm">
                            <Search class="pointer-events-none absolute top-1/2 left-3 z-10 h-4 w-4 -translate-y-1/2 text-gray-400" />
                            <Input
                                v-model="search"
                                type="text"
                                placeholder="Cari monitor (min 3 karakter)..."
                                class="h-9 w-full rounded-md pr-8 pl-9 text-sm"
                            />
                            <button
                                v-if="search"
                                type="button"
                                @click="clearSearch"
                                class="absolute top-1/2 right-2.5 z-10 -translate-y-1/2 cursor-pointer text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                                title="Clear"
                            >
                                <X class="h-3.5 w-3.5" />
                            </button>
                        </div>

                        <Select
                            v-model="statusFilter"
                            :items="statusOptions"
                            @update:model-value="submitSearch"
                            placeholder="Semua Status"
                            class="h-9 w-36 shrink-0"
                        />
                        <Select
                            v-model="visibilityFilter"
                            :items="visibilityOptions"
                            @update:model-value="submitSearch"
                            placeholder="Semua Visibilitas"
                            class="h-9 w-40 shrink-0"
                        />
                        <Select
                            v-if="props.availableTags && props.availableTags.length > 0"
                            v-model="tagFilter"
                            :items="tagOptions"
                            @update:model-value="submitSearch"
                            placeholder="Semua Tag"
                            class="h-9 w-36 shrink-0"
                        />
                        <Select
                            v-model="perPage"
                            :items="perPageOptions"
                            @update:model-value="submitSearch"
                            placeholder="Per Halaman"
                            class="h-9 w-36 shrink-0"
                        />
                        <Button v-if="search || statusFilter !== 'all' || visibilityFilter !== 'all' || tagFilter !== 'all'" type="button" variant="outline" size="sm" @click="() => { search = ''; statusFilter = 'all'; visibilityFilter = 'all'; tagFilter = 'all'; submitSearch(); }" class="h-9">
                            Reset
                        </Button>
                        <Button type="submit" size="sm" class="h-9 gap-1.5 px-4">
                            Cari
                        </Button>
                    </form>

                    <div v-if="props.monitors.data.length === 0" class="text-gray-600 dark:text-gray-400">Belum ada monitor yang terdaftar.</div>

                    <div v-else class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>URL</TableHead>
                                    <TableHead>Tags</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Terakhir Dicek</TableHead>
                                    <TableHead>Uptime Hari Ini</TableHead>
                                    <TableHead>Sertifikat</TableHead>
                                    <TableHead>Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="monitor in props.monitors.data" :key="monitor.id" class="group">
                                    <TableCell class="font-medium">
                                        <div class="flex items-center gap-1.5">
                                            <a :href="monitor.url" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline dark:text-blue-400">
                                                {{ monitor.url }}
                                            </a>
                                            <a :href="monitor.url" target="_blank" rel="noopener noreferrer" class="text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity hover:text-gray-600 dark:hover:text-gray-200">
                                                <ExternalLink class="h-3 w-3" />
                                            </a>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex flex-wrap gap-1">
                                            <span
                                                v-for="tag in monitor.tags || []"
                                                :key="tag.id || tag.name"
                                                class="inline-flex items-center rounded-md border border-gray-200/80 bg-gray-50 px-2 py-0.5 text-xs font-medium text-gray-600 dark:border-gray-700/80 dark:bg-gray-800/80 dark:text-gray-300"
                                            >
                                                {{ tag.name || tag }}
                                            </span>
                                            <span v-if="!monitor.tags || monitor.tags.length === 0" class="text-xs text-gray-400 dark:text-gray-500">
                                                -
                                            </span>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <span
                                            :class="{
                                                'bg-emerald-50 text-emerald-700 border border-emerald-200/60 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800/40': monitor.uptime_status === 'up',
                                                'bg-rose-50 text-rose-700 border border-rose-200/60 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-800/40': monitor.uptime_status === 'down',
                                                'bg-amber-50 text-amber-700 border border-amber-200/60 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800/40': monitor.uptime_status === 'not yet checked',
                                            }"
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap capitalize"
                                        >
                                            <span
                                                class="h-1.5 w-1.5 rounded-full shrink-0"
                                                :class="{
                                                    'bg-emerald-500': monitor.uptime_status === 'up',
                                                    'bg-rose-500': monitor.uptime_status === 'down',
                                                    'bg-amber-500': monitor.uptime_status === 'not yet checked',
                                                }"
                                            />
                                            {{ monitor.uptime_status }}
                                        </span>
                                    </TableCell>
                                    <TableCell class="text-gray-500 whitespace-nowrap text-xs dark:text-gray-400">
                                        {{ monitor.last_check_date ? new Date(monitor.last_check_date).toLocaleString() : '-' }}
                                    </TableCell>
                                    <TableCell class="text-gray-500 whitespace-nowrap text-xs dark:text-gray-400">
                                        {{ monitor.today_uptime_percentage ? monitor.today_uptime_percentage + '%' : '-' }}
                                    </TableCell>
                                    <TableCell class="text-gray-500 dark:text-gray-400">
                                        <template v-if="monitor.certificate_check_enabled">
                                            <span
                                                class="inline-flex items-center gap-1 font-medium text-xs whitespace-nowrap"
                                                :class="{
                                                    'text-emerald-600 dark:text-emerald-400': monitor.certificate_status === 'valid',
                                                    'text-rose-600 dark:text-rose-400': monitor.certificate_status === 'invalid',
                                                    'text-gray-600 dark:text-gray-400': monitor.certificate_status === 'not applicable',
                                                }"
                                            >
                                                <Icon
                                                    :name="monitor.certificate_status === 'valid' ? 'shieldCheck' : 'shieldAlert'"
                                                    class="h-3.5 w-3.5"
                                                />
                                                SSL {{ monitor.certificate_status }}
                                            </span>
                                            <div v-if="monitor.certificate_expiration_date" class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                                Expires: {{ new Date(monitor.certificate_expiration_date).toLocaleDateString() }}
                                            </div>
                                        </template>
                                        <template v-if="monitor.domain_expiration_check_enabled && monitor.domain_expiration_date">
                                            <div class="mt-1 flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                                <Icon name="globe" class="h-3 w-3 text-gray-400" />
                                                Domain: {{ new Date(monitor.domain_expiration_date).toLocaleDateString() }}
                                            </div>
                                        </template>
                                        <span
                                            v-if="!monitor.certificate_check_enabled && !monitor.domain_expiration_check_enabled"
                                            class="text-xs text-gray-400 dark:text-gray-500"
                                            >Tidak dicek</span
                                        >
                                    </TableCell>
                                    <TableCell class="text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1 justify-end">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                @click="openDetailModal(monitor)"
                                                class="h-8 gap-1 rounded-lg px-2 text-xs font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                                                title="Detail monitor"
                                            >
                                                <Eye class="h-3.5 w-3.5" />
                                                <span class="hidden xl:inline">Detail</span>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                @click="openEditModal(monitor)"
                                                class="h-8 gap-1 rounded-lg px-2 text-xs font-medium text-blue-600 hover:bg-blue-50 hover:text-blue-700 dark:text-blue-400 dark:hover:bg-blue-950/40"
                                                title="Edit monitor"
                                            >
                                                <Pencil class="h-3.5 w-3.5" />
                                                <span class="hidden xl:inline">Edit</span>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                @click="openDeleteModal(monitor)"
                                                class="h-8 gap-1 rounded-lg px-2 text-xs font-medium text-rose-600 hover:bg-rose-50 hover:text-rose-700 dark:text-rose-400 dark:hover:bg-rose-950/40"
                                                title="Hapus monitor"
                                            >
                                                <Trash2 class="h-3.5 w-3.5" />
                                                <span class="hidden xl:inline">Hapus</span>
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Pagination Links -->
                    <div class="mt-6">
                        <Pagination :data="props.monitors" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <Dialog v-model:open="isDeleteModalOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Hapus Monitor?</DialogTitle>
                    <DialogDescription>
                        Apakah Anda yakin ingin menghapus monitor ini? Tindakan ini tidak dapat dibatalkan.<br />
                        <span v-if="monitorToDelete" class="mt-2 block text-sm text-gray-700 dark:text-gray-300">
                            <Icon name="alertTriangle" class="mr-1 inline text-red-500" />
                            <b>{{ monitorToDelete.url }}</b>
                        </span>
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="closeDeleteModal">Batal</Button>
                    <Button variant="destructive" @click="confirmDeleteMonitor">Hapus</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Create Monitor Modal -->
        <CreateMonitorModal v-model:open="isCreateModalOpen" />

        <!-- Edit Monitor Modal -->
        <EditMonitorModal v-model:open="isEditModalOpen" :monitor="monitorToEdit" />

        <!-- Detail Monitor Modal -->
        <DetailMonitorModal v-model:open="isDetailModalOpen" :monitor="monitorToView" @edit="openEditModal" />
    </AppLayout>
</template>
